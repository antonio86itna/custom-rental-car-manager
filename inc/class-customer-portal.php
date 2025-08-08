<?php
/**
 * Customer Portal Class
 * 
 * Handles customer dashboard, account management,
 * booking history, and profile updates.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRCM_Customer_Portal {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_crcm_get_customer_bookings', array($this, 'get_customer_bookings'));
        add_action('wp_ajax_crcm_cancel_booking', array($this, 'cancel_booking'));
        add_action('wp_ajax_crcm_update_profile', array($this, 'update_profile'));
        add_action('wp_login', array($this, 'after_login'), 10, 2);
    }

    /**
     * Get customer bookings via AJAX
     */
    public function get_customer_bookings() {
        check_ajax_referer('crcm_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to view your bookings', 'custom-rental-manager'));
        }

        $current_user = wp_get_current_user();
        $bookings = $this->get_bookings_by_user($current_user->ID);

        wp_send_json_success($bookings);
    }

    /**
     * Cancel booking via AJAX
     */
    public function cancel_booking() {
        check_ajax_referer('crcm_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to cancel bookings', 'custom-rental-manager'));
        }

        $booking_id = intval($_POST['booking_id'] ?? 0);
        $current_user = wp_get_current_user();

        // Verify this is the customer's booking
        $booking_customer_id = (int) get_post_meta($booking_id, '_crcm_customer_user_id', true);
        if ($booking_customer_id !== (int) $current_user->ID) {
            wp_send_json_error(__('You can only cancel your own bookings', 'custom-rental-manager'));
        }

        // Check if booking can be cancelled
        $booking_data = get_post_meta($booking_id, '_crcm_booking_data', true);
        $pickup_date = $booking_data['pickup_date'];
        $free_cancellation_days = crcm()->get_setting('free_cancellation_days', 3);

        $days_until_pickup = ceil((strtotime($pickup_date) - time()) / DAY_IN_SECONDS);

        if ($days_until_pickup < $free_cancellation_days) {
            wp_send_json_error(sprintf(__('Bookings can only be cancelled at least %d days before pickup', 'custom-rental-manager'), $free_cancellation_days));
        }

        // Update booking status
        $old_status = get_post_meta($booking_id, '_crcm_booking_status', true);
        update_post_meta($booking_id, '_crcm_booking_status', 'cancelled');
        do_action('crcm_booking_status_changed', $booking_id, 'cancelled', $old_status);

        wp_send_json_success(__('Booking cancelled successfully', 'custom-rental-manager'));
    }

    /**
     * Update customer profile
     */
    public function update_profile() {
        check_ajax_referer('crcm_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in to update your profile', 'custom-rental-manager'));
        }

        $current_user_id = get_current_user_id();

        $profile_data = array(
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'date_of_birth' => sanitize_text_field($_POST['date_of_birth'] ?? ''),
            'license_number' => sanitize_text_field($_POST['license_number'] ?? ''),
            'license_country' => sanitize_text_field($_POST['license_country'] ?? ''),
            'address' => sanitize_text_field($_POST['address'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'postal_code' => sanitize_text_field($_POST['postal_code'] ?? ''),
            'country' => sanitize_text_field($_POST['country'] ?? ''),
        );

        // Update WordPress user meta
        update_user_meta($current_user_id, 'first_name', $profile_data['first_name']);
        update_user_meta($current_user_id, 'last_name', $profile_data['last_name']);

        // Update custom profile data
        update_user_meta($current_user_id, 'crcm_profile_data', $profile_data);

        wp_send_json_success(__('Profile updated successfully', 'custom-rental-manager'));
    }

    /**
     * Get bookings by customer user ID.
     *
     * @param int $user_id Customer user ID.
     * @return array
     */
    public function get_bookings_by_user($user_id) {
        $bookings = array();
        $booking_manager = crcm()->booking_manager;
        $booking_posts   = crcm_get_customer_bookings($user_id);

        foreach ($booking_posts as $booking_post) {
            $booking = $booking_manager->get_booking($booking_post->ID);
            if (!is_wp_error($booking)) {
                $vehicle = get_post($booking['booking_data']['vehicle_id']);
                $booking['vehicle_name']  = $vehicle ? $vehicle->post_title : '';
                $booking['vehicle_image'] = get_the_post_thumbnail_url($booking['booking_data']['vehicle_id'], 'medium');
                $bookings[]               = $booking;
            }
        }

        return $bookings;
    }

    /**
     * Get customer profile data
     */
    public function get_customer_profile($user_id) {
        $profile_data = get_user_meta($user_id, 'crcm_profile_data', true);

        if (!$profile_data) {
            $user = get_user_by('id', $user_id);
            $profile_data = array(
                'first_name' => get_user_meta($user_id, 'first_name', true),
                'last_name' => get_user_meta($user_id, 'last_name', true),
                'email' => $user->user_email,
                'phone' => '',
                'date_of_birth' => '',
                'license_number' => '',
                'license_country' => '',
                'address' => '',
                'city' => '',
                'postal_code' => '',
                'country' => '',
            );
        }

        return $profile_data;
    }

    /**
     * After login redirect for rental customers
     */
    public function after_login($user_login, $user) {
        if (in_array('crcm_customer', $user->roles)) {
            // Redirect to customer dashboard if they came from rental pages
            $redirect_to = wp_get_referer();
            if ($redirect_to && (strpos($redirect_to, 'rental') !== false || strpos($redirect_to, 'booking') !== false)) {
                wp_redirect(add_query_arg('customer_dashboard', '1', $redirect_to));
                exit;
            }
        }
    }

    /**
     * Check if user can cancel booking.
     *
     * @param int      $booking_id Booking ID.
     * @param int|null $user_id    Optional user ID.
     * @return bool
     */
    public function can_cancel_booking($booking_id, $user_id = null) {
        if (!$user_id && is_user_logged_in()) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Check if this is the customer's booking
        $booking_customer_id = (int) get_post_meta($booking_id, '_crcm_customer_user_id', true);
        if ($booking_customer_id !== (int) $user_id) {
            return false;
        }

        // Check booking status
        $booking_status = get_post_meta($booking_id, '_crcm_booking_status', true);
        if (!in_array($booking_status, array('pending', 'confirmed'))) {
            return false;
        }

        // Check cancellation period
        $booking_data = get_post_meta($booking_id, '_crcm_booking_data', true);
        $pickup_date = $booking_data['pickup_date'];
        $free_cancellation_days = crcm()->get_setting('free_cancellation_days', 3);

        $days_until_pickup = ceil((strtotime($pickup_date) - time()) / DAY_IN_SECONDS);

        return ($days_until_pickup >= $free_cancellation_days);
    }

    /**
     * Get booking cancellation deadline
     */
    public function get_cancellation_deadline($booking_id) {
        $booking_data = get_post_meta($booking_id, '_crcm_booking_data', true);
        if (!$booking_data) {
            return null;
        }

        $pickup_date = $booking_data['pickup_date'];
        $free_cancellation_days = crcm()->get_setting('free_cancellation_days', 3);

        $deadline = date('Y-m-d H:i:s', strtotime($pickup_date . ' -' . $free_cancellation_days . ' days'));

        return $deadline;
    }
}
