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
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_crcm_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_crcm_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_crcm_process_refund', array($this, 'process_refund'));
    }

    /**
     * Process Stripe payment
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
            update_post_meta($booking_id, '_crcm_booking_status', 'confirmed');

            wp_send_json_success(array(
                'message' => __('Payment processed successfully', 'custom-rental-manager'),
                'payment_intent_id' => $payment_intent_id,
            ));

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Process refund
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
                update_post_meta($booking_id, '_crcm_booking_status', 'refunded');
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
     * Calculate total booking cost
     */
    public function calculate_total_cost($booking_data) {
        $booking_manager = crcm()->booking_manager;
        return $booking_manager->calculate_booking_pricing($booking_data);
    }

    /**
     * Get Stripe publishable key
     */
    public function get_stripe_publishable_key() {
        return crcm()->get_setting('stripe_publishable_key');
    }
}
