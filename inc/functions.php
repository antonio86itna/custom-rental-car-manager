<?php
/**
 * Helper Functions
 * 
 * Collection of utility functions used throughout the plugin.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Format price with currency symbol
 */
function crcm_format_price($amount, $currency_symbol = '€') {
    return $currency_symbol . number_format($amount, 2);
}

/**
 * Format date according to settings
 */
function crcm_format_date($date, $format = 'd/m/Y') {
    if (empty($date)) {
        return '';
    }

    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Get booking status badge HTML
 */
function crcm_get_status_badge($status) {
    $statuses = array(
        'pending' => array('label' => __('Pending', 'custom-rental-manager'), 'color' => '#f0ad4e'),
        'confirmed' => array('label' => __('Confirmed', 'custom-rental-manager'), 'color' => '#5bc0de'),
        'active' => array('label' => __('Active', 'custom-rental-manager'), 'color' => '#5cb85c'),
        'completed' => array('label' => __('Completed', 'custom-rental-manager'), 'color' => '#777'),
        'cancelled' => array('label' => __('Cancelled', 'custom-rental-manager'), 'color' => '#d9534f'),
    );

    if (!isset($statuses[$status])) {
        return '<span class="crcm-status-badge">' . ucfirst($status) . '</span>';
    }

    return sprintf(
        '<span class="crcm-status-badge" style="background: %s; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px;">%s</span>',
        $statuses[$status]['color'],
        $statuses[$status]['label']
    );
}

/**
 * OPTIMIZED: Get dashboard statistics without loading all posts
 */
function crcm_get_dashboard_stats() {
    global $wpdb;

    // Get counts using direct SQL for better performance
    $stats = array();

    // Vehicle count
    $stats['total_vehicles'] = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'crcm_vehicle' AND post_status = 'publish'"
    );

    // Booking counts by status
    $booking_counts = $wpdb->get_results(
        "SELECT pm.meta_value as status, COUNT(*) as count 
         FROM {$wpdb->posts} p 
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
         WHERE p.post_type = 'crcm_booking' 
         AND p.post_status = 'publish' 
         AND pm.meta_key = '_crcm_booking_status' 
         GROUP BY pm.meta_value",
        ARRAY_A
    );

    // Initialize booking stats
    $stats['total_bookings'] = 0;
    $stats['pending_bookings'] = 0;
    $stats['confirmed_bookings'] = 0;
    $stats['active_bookings'] = 0;
    $stats['completed_bookings'] = 0;

    foreach ($booking_counts as $count) {
        $stats['total_bookings'] += $count['count'];
        $stats[$count['status'] . '_bookings'] = $count['count'];
    }

    // Today's activity (optimized queries)
    $today = date('Y-m-d');

    // Today's pickups
    $stats['todays_pickups'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} p 
         INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
         INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id 
         WHERE p.post_type = 'crcm_booking' 
         AND p.post_status = 'publish'
         AND pm1.meta_key = '_crcm_booking_data' 
         AND pm1.meta_value LIKE %s
         AND pm2.meta_key = '_crcm_booking_status' 
         AND pm2.meta_value IN ('confirmed', 'active')",
        '%pickup_date";s:10:"' . $today . '"%'
    ));

    // Today's returns
    $stats['todays_returns'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} p 
         INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
         INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id 
         WHERE p.post_type = 'crcm_booking' 
         AND p.post_status = 'publish'
         AND pm1.meta_key = '_crcm_booking_data' 
         AND pm1.meta_value LIKE %s
         AND pm2.meta_key = '_crcm_booking_status' 
         AND pm2.meta_value = 'active'",
        '%return_date";s:10:"' . $today . '"%'
    ));

    // This month's revenue (simple calculation)
    $this_month = date('Y-m-01');
    $next_month = date('Y-m-01', strtotime('+1 month'));

    $stats['monthly_revenue'] = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(CAST(pm.meta_value as DECIMAL(10,2))) 
         FROM {$wpdb->posts} p 
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
         WHERE p.post_type = 'crcm_booking' 
         AND p.post_status = 'publish'
         AND p.post_date >= %s 
         AND p.post_date < %s
         AND pm.meta_key = '_crcm_payment_total'",
        $this_month,
        $next_month
    ));

    $stats['monthly_revenue'] = $stats['monthly_revenue'] ?: 0;

    return $stats;
}

/**
 * Get upcoming bookings (optimized)
 */
function crcm_get_upcoming_bookings($limit = 5) {
    global $wpdb;

    $today = date('Y-m-d');
    $week_ahead = date('Y-m-d', strtotime('+7 days'));

    $bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title, pm1.meta_value as booking_data, pm2.meta_value as status
         FROM {$wpdb->posts} p 
         INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
         INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id 
         WHERE p.post_type = 'crcm_booking' 
         AND p.post_status = 'publish'
         AND pm1.meta_key = '_crcm_booking_data' 
         AND (pm1.meta_value LIKE %s OR pm1.meta_value LIKE %s OR pm1.meta_value LIKE %s)
         AND pm2.meta_key = '_crcm_booking_status' 
         AND pm2.meta_value IN ('confirmed', 'active')
         ORDER BY p.post_date DESC 
         LIMIT %d",
        '%pickup_date%' . $today . '%',
        '%pickup_date%' . date('Y-m-d', strtotime('+1 day')) . '%',
        '%pickup_date%' . date('Y-m-d', strtotime('+2 days')) . '%',
        $limit
    ), ARRAY_A);

    return $bookings;
}

/**
 * Calculate rental days between two dates
 */
function crcm_calculate_rental_days($pickup_date, $return_date) {
    $pickup = new DateTime($pickup_date);
    $return = new DateTime($return_date);
    $interval = $pickup->diff($return);

    return max(1, $interval->days); // Minimum 1 day
}

/**
 * Get country options for forms
 */
function crcm_get_country_options() {
    return array(
        'IT' => __('Italy', 'custom-rental-manager'),
        'US' => __('United States', 'custom-rental-manager'),
        'GB' => __('United Kingdom', 'custom-rental-manager'),
        'DE' => __('Germany', 'custom-rental-manager'),
        'FR' => __('France', 'custom-rental-manager'),
        'ES' => __('Spain', 'custom-rental-manager'),
        'NL' => __('Netherlands', 'custom-rental-manager'),
        'BE' => __('Belgium', 'custom-rental-manager'),
        'AT' => __('Austria', 'custom-rental-manager'),
        'CH' => __('Switzerland', 'custom-rental-manager'),
    );
}

/**
 * Create customer account
 */
function crcm_create_customer_account($customer_data) {
    if (email_exists($customer_data['email'])) {
        return false;
    }

    $username = sanitize_user($customer_data['email']);
    $password = wp_generate_password(12, false);

    $user_id = wp_create_user($username, $password, $customer_data['email']);

    if (is_wp_error($user_id)) {
        return false;
    }

    // Update user meta
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $customer_data['first_name'],
        'last_name' => $customer_data['last_name'],
        'display_name' => $customer_data['first_name'] . ' ' . $customer_data['last_name'],
    ));

    // Add customer role
    $user = new WP_User($user_id);
    $user->set_role('customer');

    // Send password reset email
    wp_send_new_user_notifications($user_id, 'user');

    return $user_id;
}

/**
 * Log function for debugging
 */
function crcm_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[CRCM] ' . $level . ': ' . $message);
    }
}

/**
 * Check if user has rental manager capability
 */
function crcm_user_can_manage_rentals() {
    return current_user_can('manage_options');
}

/**
 * Get plugin settings
 */
function crcm_get_setting($key, $default = '') {
    return crcm()->get_setting($key, $default);
}

/**
 * Sanitize booking data
 */
function crcm_sanitize_booking_data($data) {
    $sanitized = array();

    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = array_map('sanitize_text_field', $value);
        } else {
            $sanitized[$key] = sanitize_text_field($value);
        }
    }

    return $sanitized;
}

/**
 * Get available vehicle types
 */
function crcm_get_vehicle_types() {
    return get_terms(array(
        'taxonomy' => 'crcm_vehicle_type',
        'hide_empty' => false,
    ));
}

/**
 * Get available locations
 */
function crcm_get_locations() {
    return get_terms(array(
        'taxonomy' => 'crcm_location',
        'hide_empty' => false,
    ));
}
