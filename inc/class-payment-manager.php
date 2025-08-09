<?php
/**
 * Payment Manager Class
 *
 * Handles Stripe integration, payment processing,
 * refunds, and payment status management.
 *
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

use Stripe\StripeClient;

if (!defined('ABSPATH')) {
    exit;
}

class CRCM_Payment_Manager {

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        add_action('wp_ajax_crcm_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_crcm_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_crcm_process_refund', array($this, 'process_refund'));
        add_action('wp_ajax_crcm_admin_cancel_booking', array($this, 'cancel_booking_admin'));
        add_action('init', array($this, 'handle_stripe_return'));
    }

    /**
     * Get Stripe client instance.
     *
     * @return \Stripe\StripeClient|null
     */
    private function get_stripe_client() {
        $secret_key = crcm()->get_setting('stripe_secret_key');
        if (empty($secret_key)) {
            return null;
        }

        return new StripeClient($secret_key);
    }

    /**
     * Retrieve or create Stripe customer for a user.
     *
     * @param \Stripe\StripeClient $client  Stripe client.
     * @param int                   $user_id WordPress user ID.
     *
     * @return string Stripe customer ID.
     */
    private function get_or_create_customer($client, $user_id) {
        $customer_id = get_user_meta($user_id, '_crcm_stripe_customer_id', true);
        if ($customer_id) {
            return $customer_id;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return '';
        }

        $customer = $client->customers->create(array(
            'email' => $user->user_email,
            'name'  => $user->display_name,
        ));

        update_user_meta($user_id, '_crcm_stripe_customer_id', $customer->id);

        return $customer->id;
    }

    /**
     * Process Stripe payment.
     *
     * @return void
     */
    public function process_payment() {
        check_ajax_referer('crcm_nonce', 'nonce');

        $booking_id = intval($_POST['booking_id'] ?? 0);
        $payment_method_id = sanitize_text_field($_POST['payment_method_id'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);

        if (!$booking_id || !$payment_method_id || $amount <= 0) {
            wp_send_json_error(__('Invalid payment data', 'custom-rental-manager'));
        }

        $stripe_secret = crcm()->get_setting('stripe_secret_key');
        if (empty($stripe_secret)) {
            wp_send_json_error(__('Stripe not configured', 'custom-rental-manager'));
        }

        try {
            // Initialize Stripe (you would include Stripe SDK here)
            // \Stripe\Stripe::setApiKey($stripe_secret);

            // For this example, we'll simulate payment processing
            $payment_intent_id = 'pi_' . uniqid();

            // Update booking payment data
            $payment_data = get_post_meta($booking_id, '_crcm_payment_data', true);
            $payment_data['stripe_payment_intent'] = $payment_intent_id;
            $payment_data['paid_amount'] = $amount;
            $payment_data['payment_status'] = 'completed';
            $payment_data['payment_method'] = 'stripe';

            update_post_meta($booking_id, '_crcm_payment_data', $payment_data);

            // Update booking status
            $old_status = get_post_meta($booking_id, '_crcm_booking_status', true);
            update_post_meta($booking_id, '_crcm_booking_status', 'confirmed');
            do_action('crcm_booking_status_changed', $booking_id, 'confirmed', $old_status);

            wp_send_json_success(array(
                'message' => __('Payment processed successfully', 'custom-rental-manager'),
                'payment_intent_id' => $payment_intent_id,
            ));

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Cancel booking from admin.
     *
     * @return void
     */
    public function cancel_booking_admin() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'custom-rental-manager'));
        }

        $booking_id = intval($_POST['booking_id'] ?? 0);
        if (!$booking_id) {
            wp_send_json_error(__('Invalid booking ID', 'custom-rental-manager'));
        }

        $old_status = get_post_meta($booking_id, '_crcm_booking_status', true);
        update_post_meta($booking_id, '_crcm_booking_status', 'cancelled');
        do_action('crcm_booking_status_changed', $booking_id, 'cancelled', $old_status);

        wp_send_json_success(array(
            'message' => __('Booking cancelled successfully', 'custom-rental-manager'),
        ));
    }

    /**
     * Process refund.
     *
     * @return void
     */
    public function process_refund() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'custom-rental-manager'));
        }

        $booking_id = intval($_POST['booking_id'] ?? 0);
        $refund_amount = floatval($_POST['refund_amount'] ?? 0);
        $refund_reason = sanitize_textarea_field($_POST['refund_reason'] ?? '');

        $payment_data = get_post_meta($booking_id, '_crcm_payment_data', true);

        if (!$payment_data || empty($payment_data['stripe_payment_intent'])) {
            wp_send_json_error(__('No payment found for this booking', 'custom-rental-manager'));
        }

        try {
            // Process refund with Stripe (simulated)
            $refund_id = 're_' . uniqid();

            // Update payment data
            $payment_data['refund_amount'] = $refund_amount;
            $payment_data['refund_reason'] = $refund_reason;
            $payment_data['refund_id'] = $refund_id;

            if ($refund_amount >= ($payment_data['paid_amount'] ?? 0)) {
                $payment_data['payment_status'] = 'refunded';
            } else {
                $payment_data['payment_status'] = 'partial_refund';
            }

            update_post_meta($booking_id, '_crcm_payment_data', $payment_data);

            $old_status = get_post_meta($booking_id, '_crcm_booking_status', true);
            update_post_meta($booking_id, '_crcm_booking_status', 'cancelled');
            do_action('crcm_booking_status_changed', $booking_id, 'cancelled', $old_status);

            $booking = crcm()->booking_manager->get_booking($booking_id);
            if (!is_wp_error($booking) && !empty($booking['customer_data']['email'])) {
                $currency_symbol = crcm_get_setting('currency_symbol', '€');
                $subject = sprintf(__('Refund processed - %s', 'custom-rental-manager'), $booking['booking_number']);
                $message = sprintf(
                    __('A refund of %s has been processed for your booking.', 'custom-rental-manager'),
                    crcm_format_price($refund_amount, $currency_symbol)
                );
                wp_mail(
                    $booking['customer_data']['email'],
                    $subject,
                    $message,
                    array('Content-Type: text/html; charset=UTF-8')
                );
            }

            wp_send_json_success(array(
                'message' => __('Refund processed successfully', 'custom-rental-manager'),
                'refund_id' => $refund_id,
            ));

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Generate Stripe checkout URL for a booking.
     *
     * @param int $booking_id Booking ID.
     * @return string
     */
    public function get_checkout_url($booking_id) {
        $client = $this->get_stripe_client();
        if (!$client) {
            return '';
        }

        $booking = crcm()->booking_manager->get_booking($booking_id);
        if (is_wp_error($booking)) {
            return '';
        }

        $amount = 0;
        if (!empty($booking['pricing_breakdown']['final_total'])) {
            $amount = (float) $booking['pricing_breakdown']['final_total'];
        }

        $user_id     = (int) get_post_meta($booking_id, '_crcm_customer_user_id', true);
        $customer_id = '';
        if ($user_id) {
            $customer_id = $this->get_or_create_customer($client, $user_id);
        }

        try {
            $session = $client->checkout->sessions->create(array(
                'mode'                => 'payment',
                'customer'            => $customer_id ?: null,
                'line_items'          => array(
                    array(
                        'price_data' => array(
                            'currency'     => 'eur',
                            'product_data' => array(
                                'name' => $booking['booking_number'],
                            ),
                            'unit_amount'  => (int) round($amount * 100),
                        ),
                        'quantity'   => 1,
                    ),
                ),
                'payment_intent_data' => array(
                    'setup_future_usage' => 'off_session',
                ),
                'success_url' => add_query_arg(
                    array(
                        'crcm_stripe_return' => 1,
                        'booking_id'        => $booking_id,
                        'session_id'        => '{CHECKOUT_SESSION_ID}',
                    ),
                    home_url('/customer-dashboard/')
                ),
                'cancel_url' => home_url('/customer-dashboard/'),
            ));

            update_post_meta($booking_id, '_crcm_stripe_session_id', $session->id);

            return $session->url;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return '';
        }
    }

    /**
     * Handle return from Stripe and confirm booking.
     *
     * @return void
     */
    public function handle_stripe_return() {
        if (isset($_GET['crcm_stripe_return'], $_GET['booking_id'], $_GET['session_id'])) {
            $booking_id = intval($_GET['booking_id']);
            $session_id = sanitize_text_field($_GET['session_id']);

            $client = $this->get_stripe_client();
            if ($client) {
                try {
                    $session        = $client->checkout->sessions->retrieve($session_id);
                    $intent_id      = $session->payment_intent;
                    $payment_intent = $client->paymentIntents->retrieve($intent_id);
                    $payment_method = $payment_intent->payment_method;
                    $amount_paid    = $payment_intent->amount_received / 100;

                    $payment_data = get_post_meta($booking_id, '_crcm_payment_data', true);
                    if (!is_array($payment_data)) {
                        $payment_data = array();
                    }
                    $payment_data['stripe_payment_intent'] = $intent_id;
                    $payment_data['paid_amount']           = $amount_paid;
                    $payment_data['payment_status']        = 'completed';
                    $payment_data['payment_method']        = $payment_method;
                    update_post_meta($booking_id, '_crcm_payment_data', $payment_data);

                    $user_id = (int) get_post_meta($booking_id, '_crcm_customer_user_id', true);
                    if ($user_id && $payment_method) {
                        update_user_meta($user_id, '_crcm_stripe_payment_method', $payment_method);
                    }

                    $old_status = get_post_meta($booking_id, '_crcm_booking_status', true);
                    update_post_meta($booking_id, '_crcm_booking_status', 'confirmed');
                    do_action('crcm_booking_status_changed', $booking_id, 'confirmed', $old_status);
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }

            wp_safe_redirect(home_url('/customer-dashboard/'));
            exit;
        }
    }

    /**
     * Calculate total booking cost.
     *
     * @param array $booking_data Booking data.
     * @return mixed
     */
    public function calculate_total_cost($booking_data) {
        $booking_manager = crcm()->booking_manager;
        return $booking_manager->calculate_booking_pricing($booking_data);
    }

    /**
     * Get Stripe publishable key.
     *
     * @return string
     */
    public function get_stripe_publishable_key() {
        return crcm()->get_setting('stripe_publishable_key');
    }
}
