<?php
/**
 * Updated Functions File - WITH CUSTOM USER ROLES & HARDCODED SETTINGS
 * 
 * Collection of utility functions with enhanced user role management
 * and hardcoded vehicle types and locations for Costabilerent.
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
        return '<span class="crcm-status-badge" style="background: #ccc;">' . ucfirst($status) . '</span>';
    }

    return sprintf(
        '<span class="crcm-status-badge" style="background: %s; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">%s</span>',
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

    // Customer count (users with crcm_customer role)
    $stats['total_customers'] = $wpdb->get_var(
        "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
         WHERE meta_key = '{$wpdb->prefix}capabilities' 
         AND meta_value LIKE '%crcm_customer%'"
    );

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
 * HARDCODED: Get vehicle types for Costabilerent (no more taxonomy)
 */
function crcm_get_vehicle_types() {
    return array(
        (object) array(
            'term_id' => 1,
            'name' => 'Auto',
            'slug' => 'auto',
            'description' => 'Automobili per noleggio',
        ),
        (object) array(
            'term_id' => 2,
            'name' => 'Scooter',
            'slug' => 'scooter', 
            'description' => 'Scooter e motocicli per noleggio',
        ),
    );
}

/**
 * HARDCODED: Get locations for Ischia (no more taxonomy)
 */
function crcm_get_locations() {
    return array(
        (object) array(
            'term_id' => 1,
            'name' => 'Ischia Porto',
            'slug' => 'ischia-porto',
            'description' => 'Porto principale di Ischia',
        ),
        (object) array(
            'term_id' => 2,
            'name' => 'Ischia Centro',
            'slug' => 'ischia-centro',
            'description' => 'Centro di Ischia',
        ),
        (object) array(
            'term_id' => 3,
            'name' => 'Casamicciola Terme',
            'slug' => 'casamicciola-terme',
            'description' => 'Casamicciola Terme',
        ),
        (object) array(
            'term_id' => 4,
            'name' => 'Lacco Ameno',
            'slug' => 'lacco-ameno',
            'description' => 'Lacco Ameno',
        ),
        (object) array(
            'term_id' => 5,
            'name' => 'Forio',
            'slug' => 'forio',
            'description' => 'Forio d\'Ischia',
        ),
        (object) array(
            'term_id' => 6,
            'name' => 'Serrara Fontana',
            'slug' => 'serrara-fontana',
            'description' => 'Serrara Fontana',
        ),
        (object) array(
            'term_id' => 7,
            'name' => 'Barano d\'Ischia',
            'slug' => 'barano-dischia',
            'description' => 'Barano d\'Ischia',
        ),
        (object) array(
            'term_id' => 8,
            'name' => 'Sant\'Angelo',
            'slug' => 'sant-angelo',
            'description' => 'Sant\'Angelo',
        ),
    );
}

/**
 * Check if user has rental management capabilities
 */
function crcm_user_can_manage_rentals() {
    return current_user_can('manage_options') || current_user_can('crcm_manage_vehicles');
}

/**
 * Check if user is a rental customer
 */
function crcm_user_is_customer() {
    $user = wp_get_current_user();
    return in_array('crcm_customer', $user->roles);
}

/**
 * Check if user is a rental manager
 */
function crcm_user_is_manager() {
    $user = wp_get_current_user();
    return in_array('crcm_manager', $user->roles);
}

/**
 * Get plugin settings
 */
function crcm_get_setting($key, $default = '') {
    if (function_exists('crcm')) {
        return crcm()->get_setting($key, $default);
    }
    
    $settings = get_option('crcm_settings', array());
    return isset($settings[$key]) ? $settings[$key] : $default;
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
 * Get customer bookings
 */
function crcm_get_customer_bookings($customer_user_id) {
    $args = array(
        'post_type' => 'crcm_booking',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_crcm_customer_user_id',
                'value' => $customer_user_id,
                'compare' => '='
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    );

    return get_posts($args);
}

/**
 * Get rental customers (users with crcm_customer role)
 */
function crcm_get_rental_customers($args = array()) {
    $default_args = array(
        'role' => 'crcm_customer',
        'orderby' => 'display_name',
        'order' => 'ASC',
    );
    
    $args = wp_parse_args($args, $default_args);
    return get_users($args);
}

/**
 * Get rental managers (users with crcm_manager role)
 */
function crcm_get_rental_managers($args = array()) {
    $default_args = array(
        'role' => 'crcm_manager',
        'orderby' => 'display_name',
        'order' => 'ASC',
    );
    
    $args = wp_parse_args($args, $default_args);
    return get_users($args);
}

/**
 * Create customer account with proper role
 */
function crcm_create_customer_account($customer_data) {
    if (email_exists($customer_data['email'])) {
        return false;
    }

    $username = sanitize_user($customer_data['email']);
    if (username_exists($username)) {
        $username = sanitize_user($customer_data['first_name'] . '.' . $customer_data['last_name']);
        if (username_exists($username)) {
            $username = sanitize_user($customer_data['email'] . '.' . time());
        }
    }
    
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

    // Set customer role
    $user = new WP_User($user_id);
    $user->set_role('crcm_customer');

    // Save additional customer data
    if (isset($customer_data['phone'])) {
        update_user_meta($user_id, 'phone', $customer_data['phone']);
    }
    if (isset($customer_data['date_of_birth'])) {
        update_user_meta($user_id, 'date_of_birth', $customer_data['date_of_birth']);
    }
    if (isset($customer_data['address'])) {
        update_user_meta($user_id, 'address', $customer_data['address']);
    }
    if (isset($customer_data['license_number'])) {
        update_user_meta($user_id, 'license_number', $customer_data['license_number']);
    }

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
 * Get vehicle availability for specific dates
 */
function crcm_get_vehicle_availability($vehicle_id, $pickup_date, $return_date) {
    if (!function_exists('crcm') || !crcm()->vehicle_manager) {
        return 0;
    }
    
    return crcm()->vehicle_manager->check_availability($vehicle_id, $pickup_date, $return_date);
}

/**
 * Calculate pricing with custom rates
 */
function crcm_calculate_vehicle_pricing($vehicle_id, $pickup_date, $return_date) {
    $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
    
    if (empty($pricing_data) || !isset($pricing_data['daily_rate'])) {
        return 0;
    }
    
    $days = crcm_calculate_rental_days($pickup_date, $return_date);
    $base_total = $pricing_data['daily_rate'] * $days;
    $extra_total = 0;
    
    // Apply custom rates if available
    if (!empty($pricing_data['custom_rates'])) {
        foreach ($pricing_data['custom_rates'] as $rate) {
            if (empty($rate['extra_rate'])) continue;
            
            $applies = false;
            
            switch ($rate['type']) {
                case 'weekends':
                    // Check if any day in the range is weekend
                    $current_date = new DateTime($pickup_date);
                    $end_date = new DateTime($return_date);
                    
                    while ($current_date < $end_date) {
                        $day_of_week = $current_date->format('N');
                        if ($day_of_week >= 6) { // Saturday (6) or Sunday (7)
                            $applies = true;
                            break;
                        }
                        $current_date->add(new DateInterval('P1D'));
                    }
                    break;
                    
                case 'date_range':
                    if (!empty($rate['start_date']) && !empty($rate['end_date'])) {
                        $rate_start = new DateTime($rate['start_date']);
                        $rate_end = new DateTime($rate['end_date']);
                        $pickup = new DateTime($pickup_date);
                        $return = new DateTime($return_date);
                        
                        // Check if rental period overlaps with rate period
                        if ($pickup < $rate_end && $return > $rate_start) {
                            $applies = true;
                        }
                    }
                    break;
                    
                case 'specific_days':
                    // Could be implemented for specific dates
                    break;
            }
            
            if ($applies) {
                $extra_total += $rate['extra_rate'] * $days;
            }
        }
    }
    
    return $base_total + $extra_total;
}

/**
 * Get customer display name from booking
 */
function crcm_get_booking_customer_name($booking_id) {
    $customer_data = get_post_meta($booking_id, '_crcm_customer_data', true);
    
    if (!empty($customer_data['first_name']) && !empty($customer_data['last_name'])) {
        return $customer_data['first_name'] . ' ' . $customer_data['last_name'];
    }
    
    return __('Unknown Customer', 'custom-rental-manager');
}

/**
 * Check if booking belongs to current user
 */
function crcm_user_owns_booking($booking_id) {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $current_user_id = get_current_user_id();
    $booking_customer_id = get_post_meta($booking_id, '_crcm_customer_user_id', true);
    
    return $current_user_id == $booking_customer_id;
}

/**
 * Send booking confirmation email
 */
function crcm_send_booking_confirmation($booking_id) {
    if (!function_exists('crcm') || !crcm()->email_manager) {
        return false;
    }
    
    return crcm()->email_manager->send_booking_confirmation($booking_id);
}

/**
 * Get vehicle type from vehicle data
 */
function crcm_get_vehicle_type($vehicle_id) {
    $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
    return isset($vehicle_data['vehicle_type']) ? $vehicle_data['vehicle_type'] : 'auto';
}

/**
 * Format location name
 */
function crcm_get_location_name($location_key) {
    $locations = array(
        'ischia_porto' => 'Ischia Porto',
        'ischia_centro' => 'Ischia Centro',
        'casamicciola' => 'Casamicciola Terme',
        'lacco_ameno' => 'Lacco Ameno',
        'forio' => 'Forio',
        'serrara' => 'Serrara Fontana',
        'barano' => 'Barano d\'Ischia',
        'sant_angelo' => 'Sant\'Angelo',
    );
    
    return isset($locations[$location_key]) ? $locations[$location_key] : $location_key;
}

/**
 * Get next available booking number
 */
function crcm_get_next_booking_number() {
    $prefix = 'CBR'; // Costabilerent
    $year = date('y');
    $month = date('m');
    
    global $wpdb;
    $last_number = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} 
         WHERE meta_key = '_crcm_booking_number' 
         AND meta_value LIKE %s 
         ORDER BY meta_value DESC 
         LIMIT 1",
        $prefix . $year . $month . '%'
    ));
    
    if ($last_number) {
        $sequence = intval(substr($last_number, -4)) + 1;
    } else {
        $sequence = 1;
    }
    
    return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}
