<?php
/**
 * Helper Functions - COMPLETELY FIXED VERSION
 * 
 * All deprecated functions removed, error handling improved,
 * WordPress standards compliance, performance optimization.
 * 
 * FIXES APPLIED:
 * ✅ Removed deprecated wp_roles()->reinit()
 * ✅ Added proper null checks for number_format()
 * ✅ Enhanced error handling and validation
 * ✅ WordPress.org coding standards compliance
 * ✅ Performance optimizations with caching
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create custom user roles - FIXED DEPRECATED FUNCTIONS
 */
function crcm_create_custom_user_roles() {
    // Remove existing roles first to avoid conflicts
    remove_role('crcm_customer');
    remove_role('crcm_manager');
    
    // Create Customer role with specific capabilities
    add_role('crcm_customer', __('Rental Customer', 'custom-rental-manager'), array(
        'read' => true,
        'crcm_view_own_bookings' => true,
        'crcm_edit_own_profile' => true,
        'crcm_cancel_bookings' => true,
    ));
    
    // Create Manager role with comprehensive capabilities
    add_role('crcm_manager', __('Rental Manager', 'custom-rental-manager'), array(
        'read' => true,
        'edit_posts' => true,
        'edit_others_posts' => true,
        'publish_posts' => true,
        'delete_posts' => true,
        'delete_others_posts' => true,
        'manage_categories' => true,
        'upload_files' => true,
        
        // Vehicle management
        'crcm_manage_vehicles' => true,
        'crcm_edit_vehicles' => true,
        'crcm_delete_vehicles' => true,
        'crcm_publish_vehicles' => true,
        
        // Booking management
        'crcm_manage_bookings' => true,
        'crcm_edit_bookings' => true,
        'crcm_delete_bookings' => true,
        'crcm_publish_bookings' => true,
        'crcm_view_all_bookings' => true,
        
        // Customer management (LIMITED - removed for managers)
        'crcm_view_customer_data' => true,
        
        // Reports and analytics
        'crcm_view_reports' => true,
        'crcm_export_data' => true,
    ));
    
    // Add capabilities to administrator
    $admin = get_role('administrator');
    if ($admin) {
        $capabilities = array(
            'crcm_manage_vehicles', 'crcm_edit_vehicles', 'crcm_delete_vehicles', 'crcm_publish_vehicles',
            'crcm_manage_bookings', 'crcm_edit_bookings', 'crcm_delete_bookings', 'crcm_publish_bookings',
            'crcm_view_all_bookings', 'crcm_manage_customers', 'crcm_view_customer_data', 
            'crcm_edit_customer_profiles', 'crcm_view_reports', 'crcm_export_data'
        );
        
        foreach ($capabilities as $cap) {
            $admin->add_cap($cap);
        }
    }
    
    // FIXED: Use wp_roles()->for_site() instead of deprecated reinit()
    if (function_exists('wp_roles')) {
        wp_roles()->for_site(get_current_blog_id());
    }
    
    error_log('CRCM: Custom user roles created successfully (fixed deprecated functions)');
}

/**
 * Get next booking number in sequence - ENHANCED WITH CACHING
 */
function crcm_get_next_booking_number() {
    $cache_key = 'crcm_last_booking_number_' . date('ymd');
    $last_number = wp_cache_get($cache_key, 'crcm');
    
    if ($last_number === false) {
        $prefix = 'CBR'; // Costabilerent
        $year = date('y');
        $month = date('m');
        $day = date('d');
        
        global $wpdb;
        
        // Get the last booking number for today
        $last_booking = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} 
             WHERE meta_key = '_crcm_booking_code' 
             AND meta_value LIKE %s 
             ORDER BY meta_value DESC LIMIT 1",
            $prefix . $year . $month . $day . '%'
        ));
        
        if ($last_booking) {
            $sequence = intval(substr($last_booking, -3)) + 1;
        } else {
            $sequence = 1;
        }
        
        $booking_number = $prefix . $year . $month . $day . str_pad($sequence, 3, '0', STR_PAD_LEFT);
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $booking_number, 'crcm', HOUR_IN_SECONDS);
        
        return $booking_number;
    }
    
    return $last_number;
}

/**
 * Get plugin settings - ENHANCED WITH VALIDATION
 */
function crcm_get_setting($key, $default = '') {
    static $settings_cache = null;
    
    if ($settings_cache === null) {
        $settings_cache = get_option('crcm_settings', array());
    }
    
    if (!is_array($settings_cache)) {
        $settings_cache = array();
    }
    
    return isset($settings_cache[$key]) ? $settings_cache[$key] : $default;
}

/**
 * Update plugin setting - ENHANCED WITH VALIDATION
 */
function crcm_update_setting($key, $value) {
    if (empty($key) || !is_string($key)) {
        return false;
    }
    
    $settings = get_option('crcm_settings', array());
    if (!is_array($settings)) {
        $settings = array();
    }
    
    $settings[$key] = $value;
    
    $result = update_option('crcm_settings', $settings);
    
    // Clear cache
    wp_cache_delete('crcm_settings_cache', 'crcm');
    
    return $result;
}

/**
 * Get all plugin settings - CACHED VERSION
 */
function crcm_get_settings() {
    $cache_key = 'crcm_settings_cache';
    $settings = wp_cache_get($cache_key, 'crcm');
    
    if ($settings === false) {
        $settings = get_option('crcm_settings', array());
        if (!is_array($settings)) {
            $settings = array();
        }
        
        wp_cache_set($cache_key, $settings, 'crcm', HOUR_IN_SECONDS);
    }
    
    return $settings;
}

/**
 * Check if user has rental permission - ENHANCED SECURITY
 */
function crcm_user_can_rent($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id || !is_numeric($user_id)) {
        return false;
    }
    
    $user = get_user_by('ID', $user_id);
    if (!$user || !$user->exists()) {
        return false;
    }
    
    $allowed_roles = array('crcm_customer', 'crcm_manager', 'administrator');
    return !empty(array_intersect($allowed_roles, $user->roles));
}

/**
 * Check if user can manage rentals - ENHANCED SECURITY
 */
function crcm_user_can_manage() {
    return current_user_can('crcm_manage_bookings') || current_user_can('manage_options');
}

/**
 * Format price with currency - FIXED NULL HANDLING
 */
function crcm_format_price($amount, $currency = '€') {
    // FIXED: Validate amount before formatting
    if (!is_numeric($amount)) {
        $amount = 0;
    }
    
    $amount = floatval($amount);
    
    // Handle negative amounts
    if ($amount < 0) {
        return '-' . $currency . number_format(abs($amount), 2);
    }
    
    return $currency . number_format($amount, 2);
}

/**
 * Safe number format - PREVENTS NULL ERRORS
 */
function crcm_safe_number_format($number, $decimals = 2, $dec_point = '.', $thousands_sep = ',') {
    if (!is_numeric($number) || $number === null) {
        return number_format(0, $decimals, $dec_point, $thousands_sep);
    }
    
    return number_format(floatval($number), $decimals, $dec_point, $thousands_sep);
}

/**
 * Get vehicle types - CACHED VERSION
 */
function crcm_get_vehicle_types() {
    $cache_key = 'crcm_vehicle_types';
    $types = wp_cache_get($cache_key, 'crcm');
    
    if ($types === false) {
        $types = array(
            'auto' => __('Car', 'custom-rental-manager'),
            'scooter' => __('Scooter', 'custom-rental-manager'),
            'moto' => __('Motorcycle', 'custom-rental-manager'),
            'bicicletta' => __('Bicycle', 'custom-rental-manager'),
        );
        
        wp_cache_set($cache_key, $types, 'crcm', DAY_IN_SECONDS);
    }
    
    return $types;
}

/**
 * Get transmission types - CACHED VERSION
 */
function crcm_get_transmission_types() {
    $cache_key = 'crcm_transmission_types';
    $types = wp_cache_get($cache_key, 'crcm');
    
    if ($types === false) {
        $types = array(
            'manual' => __('Manual', 'custom-rental-manager'),
            'automatic' => __('Automatic', 'custom-rental-manager'),
        );
        
        wp_cache_set($cache_key, $types, 'crcm', DAY_IN_SECONDS);
    }
    
    return $types;
}

/**
 * Get fuel types - CACHED VERSION
 */
function crcm_get_fuel_types() {
    $cache_key = 'crcm_fuel_types';
    $types = wp_cache_get($cache_key, 'crcm');
    
    if ($types === false) {
        $types = array(
            'gasoline' => __('Gasoline', 'custom-rental-manager'),
            'diesel' => __('Diesel', 'custom-rental-manager'),
            'electric' => __('Electric', 'custom-rental-manager'),
            'hybrid' => __('Hybrid', 'custom-rental-manager'),
        );
        
        wp_cache_set($cache_key, $types, 'crcm', DAY_IN_SECONDS);
    }
    
    return $types;
}

/**
 * Get booking statuses - CACHED VERSION
 */
function crcm_get_booking_statuses() {
    $cache_key = 'crcm_booking_statuses';
    $statuses = wp_cache_get($cache_key, 'crcm');
    
    if ($statuses === false) {
        $statuses = array(
            'pending' => __('Pending', 'custom-rental-manager'),
            'confirmed' => __('Confirmed', 'custom-rental-manager'),
            'active' => __('Active', 'custom-rental-manager'),
            'completed' => __('Completed', 'custom-rental-manager'),
            'cancelled' => __('Cancelled', 'custom-rental-manager'),
        );
        
        wp_cache_set($cache_key, $statuses, 'crcm', DAY_IN_SECONDS);
    }
    
    return $statuses;
}

/**
 * Get rental locations - ONLY ISCHIA PORTO AND FORIO
 */
function crcm_get_rental_locations() {
    $cache_key = 'crcm_rental_locations';
    $locations = wp_cache_get($cache_key, 'crcm');
    
    if ($locations === false) {
        $locations = array(
            'ischia_porto' => array(
                'name' => __('Ischia Porto', 'custom-rental-manager'),
                'address' => 'Via Iasolino 94, Ischia',
                'coordinates' => array('lat' => 40.7320, 'lng' => 13.9330),
            ),
            'forio' => array(
                'name' => __('Forio', 'custom-rental-manager'),
                'address' => 'Via Filippo di Lustro 19, Forio',
                'coordinates' => array('lat' => 40.7280, 'lng' => 13.8590),
            ),
        );
        
        wp_cache_set($cache_key, $locations, 'crcm', DAY_IN_SECONDS);
    }
    
    return $locations;
}

/**
 * Calculate date difference in days - ENHANCED VALIDATION
 */
function crcm_calculate_days($start_date, $end_date) {
    try {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        return max(1, $interval->days);
    } catch (Exception $e) {
        error_log('CRCM: Date calculation error: ' . $e->getMessage());
        return 1;
    }
}

/**
 * Check if date is weekend - ENHANCED VALIDATION
 */
function crcm_is_weekend($date) {
    try {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        if ($timestamp === false) {
            return false;
        }
        
        $day_of_week = date('N', $timestamp);
        return $day_of_week >= 6; // Saturday (6) or Sunday (7)
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if date is in range - ENHANCED VALIDATION
 */
function crcm_date_in_range($date, $start_date, $end_date) {
    try {
        $check_date = new DateTime($date);
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        return $check_date >= $start && $check_date <= $end;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate random booking reference - ENHANCED UNIQUENESS
 */
function crcm_generate_booking_reference() {
    $prefix = 'CBR';
    $date_part = date('ymd');
    $random_part = strtoupper(wp_generate_password(4, false));
    $timestamp_part = substr(time(), -3);
    
    return $prefix . '-' . $date_part . '-' . $random_part . $timestamp_part;
}

/**
 * Log debug message - ENHANCED LOGGING
 */
function crcm_log($message, $level = 'info', $context = array()) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $log_levels = array('debug', 'info', 'warning', 'error', 'critical');
    if (!in_array($level, $log_levels)) {
        $level = 'info';
    }
    
    $log_message = sprintf(
        'CRCM [%s]: %s',
        strtoupper($level),
        $message
    );
    
    if (!empty($context)) {
        $log_message .= ' | Context: ' . wp_json_encode($context);
    }
    
    error_log($log_message);
}

/**
 * Send email notification - ENHANCED WITH VALIDATION
 */
function crcm_send_email($to, $subject, $message, $headers = array()) {
    // Validate email address
    if (!is_email($to)) {
        crcm_log('Invalid email address: ' . $to, 'error');
        return false;
    }
    
    // Sanitize subject and message
    $subject = wp_strip_all_tags($subject);
    
    $default_headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . crcm_get_setting('company_name', 'Costabilerent') . ' <' . crcm_get_setting('company_email', get_option('admin_email')) . '>',
    );
    
    $headers = array_merge($default_headers, $headers);
    
    // Log email attempt
    crcm_log('Sending email to: ' . $to . ' - Subject: ' . $subject, 'info');
    
    $result = wp_mail($to, $subject, $message, $headers);
    
    if (!$result) {
        crcm_log('Failed to send email to: ' . $to, 'error');
    }
    
    return $result;
}

/**
 * Get vehicle availability for a date range - OPTIMIZED QUERY
 */
function crcm_check_vehicle_availability($vehicle_id, $start_date, $end_date) {
    if (!is_numeric($vehicle_id) || $vehicle_id <= 0) {
        return 0;
    }
    
    $cache_key = 'crcm_availability_' . $vehicle_id . '_' . md5($start_date . $end_date);
    $availability = wp_cache_get($cache_key, 'crcm');
    
    if ($availability !== false) {
        return $availability;
    }
    
    // Get vehicle quantity
    $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
    $total_quantity = isset($vehicle_data['quantity']) ? intval($vehicle_data['quantity']) : 1;
    
    if ($total_quantity <= 0) {
        wp_cache_set($cache_key, 0, 'crcm', 15 * MINUTE_IN_SECONDS);
        return 0;
    }
    
    global $wpdb;
    
    // Count overlapping bookings - OPTIMIZED QUERY
    $overlapping_bookings = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT p.ID)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm_vehicle ON p.ID = pm_vehicle.post_id AND pm_vehicle.meta_key = '_crcm_booking_data'
        INNER JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = '_crcm_booking_status'
        WHERE p.post_type = 'crcm_booking'
        AND p.post_status = 'publish'
        AND pm_status.meta_value IN ('confirmed', 'active')
        AND JSON_EXTRACT(pm_vehicle.meta_value, '$.vehicle_id') = %s
        AND JSON_EXTRACT(pm_vehicle.meta_value, '$.pickup_date') <= %s
        AND JSON_EXTRACT(pm_vehicle.meta_value, '$.return_date') >= %s
    ", $vehicle_id, $end_date, $start_date));
    
    $available_quantity = max(0, $total_quantity - intval($overlapping_bookings));
    
    // Cache for 15 minutes
    wp_cache_set($cache_key, $available_quantity, 'crcm', 15 * MINUTE_IN_SECONDS);
    
    return $available_quantity;
}

/**
 * Get customer booking history - OPTIMIZED WITH CACHING
 */
function crcm_get_customer_bookings($customer_id, $limit = 10) {
    if (!is_numeric($customer_id) || $customer_id <= 0) {
        return array();
    }
    
    $cache_key = 'crcm_customer_bookings_' . $customer_id . '_' . $limit;
    $bookings = wp_cache_get($cache_key, 'crcm');
    
    if ($bookings !== false) {
        return $bookings;
    }
    
    $bookings = get_posts(array(
        'post_type' => 'crcm_booking',
        'posts_per_page' => intval($limit),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_crcm_booking_data',
                'value' => '"customer_id":"' . $customer_id . '"',
                'compare' => 'LIKE'
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    // Cache for 30 minutes
    wp_cache_set($cache_key, $bookings, 'crcm', 30 * MINUTE_IN_SECONDS);
    
    return $bookings;
}

/**
 * Calculate booking total - ENHANCED WITH VALIDATION
 */
function crcm_calculate_booking_total($vehicle_id, $start_date, $end_date, $extras = array(), $insurance = 'basic') {
    if (!is_numeric($vehicle_id) || $vehicle_id <= 0) {
        return 0;
    }
    
    try {
        $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
        $extras_data = get_post_meta($vehicle_id, '_crcm_extras_data', true);
        $insurance_data = get_post_meta($vehicle_id, '_crcm_insurance_data', true);
        
        $days = crcm_calculate_days($start_date, $end_date);
        $base_rate = isset($pricing_data['daily_rate']) ? floatval($pricing_data['daily_rate']) : 0;
        
        if ($base_rate <= 0) {
            return 0;
        }
        
        $total = $base_rate * $days;
        
        // Add extras
        if (!empty($extras) && is_array($extras) && !empty($extras_data) && is_array($extras_data)) {
            foreach ($extras as $extra_index) {
                if (isset($extras_data[$extra_index]) && isset($extras_data[$extra_index]['daily_rate'])) {
                    $total += floatval($extras_data[$extra_index]['daily_rate']) * $days;
                }
            }
        }
        
        // Add insurance
        if ($insurance === 'premium' && !empty($insurance_data['premium']['enabled']) && isset($insurance_data['premium']['daily_rate'])) {
            $total += floatval($insurance_data['premium']['daily_rate']) * $days;
        }
        
        return max(0, $total);
        
    } catch (Exception $e) {
        crcm_log('Error calculating booking total: ' . $e->getMessage(), 'error');
        return 0;
    }
}

/**
 * Sanitize booking data - ENHANCED VALIDATION
 */
function crcm_sanitize_booking_data($data) {
    if (!is_array($data)) {
        return array();
    }
    
    $sanitized = array();
    
    $fields = array(
        'customer_id' => 'intval',
        'vehicle_id' => 'intval',
        'pickup_date' => 'sanitize_text_field',
        'return_date' => 'sanitize_text_field',
        'pickup_time' => 'sanitize_text_field',
        'return_time' => 'sanitize_text_field',
        'pickup_location' => 'sanitize_text_field',
        'return_location' => 'sanitize_text_field',
        'rental_days' => 'intval',
    );
    
    foreach ($fields as $field => $sanitizer) {
        if (isset($data[$field])) {
            $sanitized[$field] = call_user_func($sanitizer, $data[$field]);
        }
    }
    
    return $sanitized;
}

/**
 * Validate booking data - COMPREHENSIVE VALIDATION
 */
function crcm_validate_booking_data($data) {
    $errors = array();
    
    if (!is_array($data)) {
        $errors[] = __('Invalid booking data format', 'custom-rental-manager');
        return $errors;
    }
    
    // Required fields
    $required_fields = array('customer_id', 'vehicle_id', 'pickup_date', 'return_date');
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = sprintf(__('Field %s is required', 'custom-rental-manager'), $field);
        }
    }
    
    // Stop validation if required fields are missing
    if (!empty($errors)) {
        return $errors;
    }
    
    // Date validation
    try {
        $pickup = new DateTime($data['pickup_date']);
        $return = new DateTime($data['return_date']);
        $today = new DateTime('today');
        
        if ($return <= $pickup) {
            $errors[] = __('Return date must be after pickup date', 'custom-rental-manager');
        }
        
        if ($pickup < $today) {
            $errors[] = __('Pickup date cannot be in the past', 'custom-rental-manager');
        }
        
        // Check maximum booking period (e.g., 1 year)
        $max_days = apply_filters('crcm_max_booking_days', 365);
        $booking_days = $pickup->diff($return)->days;
        if ($booking_days > $max_days) {
            $errors[] = sprintf(__('Booking period cannot exceed %d days', 'custom-rental-manager'), $max_days);
        }
        
    } catch (Exception $e) {
        $errors[] = __('Invalid date format', 'custom-rental-manager');
    }
    
    // Vehicle existence and availability
    if (!empty($data['vehicle_id'])) {
        $vehicle = get_post($data['vehicle_id']);
        if (!$vehicle || $vehicle->post_type !== 'crcm_vehicle' || $vehicle->post_status !== 'publish') {
            $errors[] = __('Invalid or unavailable vehicle selected', 'custom-rental-manager');
        } else {
            // Check availability
            $availability = crcm_check_vehicle_availability($data['vehicle_id'], $data['pickup_date'], $data['return_date']);
            if ($availability <= 0) {
                $errors[] = __('Selected vehicle is not available for the chosen dates', 'custom-rental-manager');
            }
        }
    }
    
    // Customer existence and permissions
    if (!empty($data['customer_id'])) {
        $customer = get_user_by('ID', $data['customer_id']);
        if (!$customer || !in_array('crcm_customer', $customer->roles)) {
            $errors[] = __('Invalid customer selected', 'custom-rental-manager');
        }
    }
    
    // Location validation
    $valid_locations = array_keys(crcm_get_rental_locations());
    if (!empty($data['pickup_location']) && !in_array($data['pickup_location'], $valid_locations)) {
        $errors[] = __('Invalid pickup location', 'custom-rental-manager');
    }
    if (!empty($data['return_location']) && !in_array($data['return_location'], $valid_locations)) {
        $errors[] = __('Invalid return location', 'custom-rental-manager');
    }
    
    return $errors;
}

/**
 * Create default vehicle meta structure - COMPLETE STRUCTURE
 */
function crcm_get_default_vehicle_meta() {
    return array(
        'vehicle_data' => array(
            'vehicle_type' => 'auto',
            'seats' => 5,
            'transmission' => 'manual',
            'fuel_type' => 'gasoline',
            'engine_size' => '',
            'year' => date('Y'),
            'quantity' => 1,
        ),
        'pricing_data' => array(
            'daily_rate' => 0,
            'custom_rates' => array(),
        ),
        'extras_data' => array(),
        'insurance_data' => array(
            'basic' => array(
                'enabled' => true,
                'description' => 'RCA - Civil Liability',
            ),
            'premium' => array(
                'enabled' => false,
                'daily_rate' => 0,
                'deductible' => 500,
                'description' => 'RCA + Theft & Fire + Accidental Damage',
            ),
        ),
        'misc_data' => array(
            'min_rental_days' => 1,
            'max_rental_days' => 30,
            'cancellation_enabled' => true,
            'cancellation_days' => 5,
            'late_return_rule' => false,
            'late_return_time' => '10:00',
            'featured_vehicle' => false,
        ),
    );
}

/**
 * Create default booking meta structure - COMPLETE STRUCTURE
 */
function crcm_get_default_booking_meta() {
    return array(
        'booking_data' => array(
            'customer_id' => 0,
            'vehicle_id' => 0,
            'pickup_date' => date('Y-m-d'),
            'return_date' => date('Y-m-d', strtotime('+1 day')),
            'pickup_time' => '09:00',
            'return_time' => '18:00',
            'pickup_location' => 'ischia_porto',
            'return_location' => 'ischia_porto',
            'rental_days' => 1,
        ),
        'pricing_breakdown' => array(
            'base_total' => 0,
            'custom_rates_total' => 0,
            'extras_total' => 0,
            'insurance_total' => 0,
            'late_return_penalty' => 0,
            'discount_total' => 0,
            'final_total' => 0,
            'selected_extras' => array(),
            'selected_insurance' => 'basic',
        ),
        'booking_status' => 'pending',
        'booking_notes' => '',
        'internal_notes' => '',
    );
}

/**
 * Clean up plugin data on uninstall - COMPLETE CLEANUP
 */
function crcm_cleanup_plugin_data() {
    if (!current_user_can('activate_plugins')) {
        return;
    }
    
    global $wpdb;
    
    // Remove custom post types
    $post_types = array('crcm_vehicle', 'crcm_booking');
    foreach ($post_types as $post_type) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
    }
    
    // Remove custom roles
    remove_role('crcm_customer');
    remove_role('crcm_manager');
    
    // Remove plugin options
    $options = array('crcm_settings', 'crcm_plugin_activated', 'crcm_activation_time');
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Remove capabilities from administrator
    $admin = get_role('administrator');
    if ($admin) {
        $capabilities = array(
            'crcm_manage_vehicles', 'crcm_edit_vehicles', 'crcm_delete_vehicles', 'crcm_publish_vehicles',
            'crcm_manage_bookings', 'crcm_edit_bookings', 'crcm_delete_bookings', 'crcm_publish_bookings',
            'crcm_view_all_bookings', 'crcm_manage_customers', 'crcm_view_customer_data', 
            'crcm_edit_customer_profiles', 'crcm_view_reports', 'crcm_export_data'
        );
        
        foreach ($capabilities as $cap) {
            $admin->remove_cap($cap);
        }
    }
    
    // Clear all caches
    wp_cache_flush_group('crcm');
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    crcm_log('Plugin data cleanup completed', 'info');
}

/**
 * Debug function to display system info - ENHANCED
 */
function crcm_debug_info() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Access denied', 'custom-rental-manager'));
    }
    
    $info = array(
        'PHP Version' => PHP_VERSION,
        'WordPress Version' => get_bloginfo('version'),
        'Plugin Version' => defined('CRCM_VERSION') ? CRCM_VERSION : 'Unknown',
        'Customer Role Exists' => get_role('crcm_customer') ? 'Yes' : 'No',
        'Manager Role Exists' => get_role('crcm_manager') ? 'Yes' : 'No',
        'Vehicle Count' => wp_count_posts('crcm_vehicle')->publish ?? 0,
        'Booking Count' => wp_count_posts('crcm_booking')->publish ?? 0,
        'Memory Limit' => ini_get('memory_limit'),
        'Max Execution Time' => ini_get('max_execution_time'),
        'Object Cache' => wp_using_ext_object_cache() ? 'Enabled' : 'Disabled',
        'WP Debug' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
    );
    
    echo '<div class="wrap">';
    echo '<h1>' . __('CRCM System Information', 'custom-rental-manager') . '</h1>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Setting</th><th>Value</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($info as $key => $value) {
        echo '<tr>';
        echo '<td>' . esc_html($key) . '</td>';
        echo '<td>' . esc_html($value) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
