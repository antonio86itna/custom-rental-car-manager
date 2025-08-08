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
        add_action('init', array($this, 'handle_stripe_return'));
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

            if ($refund_amount >= $payment_data['paid_amount']) {
                $payment_data['payment_status'] = 'refunded';
                $old_status = get_post_meta($booking_id, '_crcm_booking_status', true);
                update_post_meta($booking_id, '_crcm_booking_status', 'refunded');
                do_action('crcm_booking_status_changed', $booking_id, 'refunded', $old_status);
            } else {
                $payment_data['payment_status'] = 'partial_refund';
            }

            update_post_meta($booking_id, '_crcm_payment_data', $payment_data);

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
        $success_url = add_query_arg(
            array(
                'crcm_stripe_return' => 1,
                'booking_id'        => $booking_id,
            ),
            home_url('/customer-dashboard/')
        );

        $cancel_url = home_url('/customer-dashboard/');

        // Normally a Stripe Checkout session would be created here.
        return add_query_arg(
            array(
                'success_url' => rawurlencode($success_url),
                'cancel_url'  => rawurlencode($cancel_url),
            ),
            'https://example.com/stripe-checkout'
        );
    }

    /**
     * Handle return from Stripe and confirm booking.
     *
     * @return void
     */
    public function handle_stripe_return() {
        if (isset($_GET['crcm_stripe_return'], $_GET['booking_id'])) {
            $booking_id = intval($_GET['booking_id']);
            $old_status = get_post_meta($booking_id, '_crcm_booking_status', true);
            update_post_meta($booking_id, '_crcm_booking_status', 'confirmed');
            do_action('crcm_booking_status_changed', $booking_id, 'confirmed', $old_status);

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
