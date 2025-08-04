<?php
/**
 * Helper Functions for Custom Rental Car Manager
 * 
 * COMPLETE ECOSYSTEM FUNCTIONS with proper user role management
 * and utility functions for the rental system.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create custom user roles for the rental system
 */
function crcm_create_custom_user_roles() {
    // Remove existing roles first to ensure clean setup
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
        
        // Customer management
        'crcm_manage_customers' => true,
        'crcm_view_customer_data' => true,
        'crcm_edit_customer_profiles' => true,
        
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
    
    // Flush roles cache
    wp_roles()->reinit();
    
    error_log('CRCM: Custom user roles created successfully');
}

/**
 * Get next booking number in sequence
 */
function crcm_get_next_booking_number() {
    $prefix = 'CBR'; // Costabilerent
    $year = date('y');
    $month = date('m');
    $day = date('d');
    
    global $wpdb;
    
    // Get the last booking number for today
    $last_booking = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} 
             WHERE meta_key = '_crcm_booking_code' 
             AND meta_value LIKE %s 
             ORDER BY meta_value DESC LIMIT 1",
            $prefix . $year . $month . $day . '%'
        )
    );
    
    if ($last_booking) {
        $sequence = intval(substr($last_booking, -3)) + 1;
    } else {
        $sequence = 1;
    }
    
    return $prefix . $year . $month . $day . str_pad($sequence, 3, '0', STR_PAD_LEFT);
}

/**
 * Get plugin settings
 */
function crcm_get_setting($key, $default = '') {
    $settings = get_option('crcm_settings', array());
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Update plugin setting
 */
function crcm_update_setting($key, $value) {
    $settings = get_option('crcm_settings', array());
    $settings[$key] = $value;
    return update_option('crcm_settings', $settings);
}

/**
 * Get all plugin settings
 */
function crcm_get_settings() {
    return get_option('crcm_settings', array());
}

/**
 * Check if user has rental permission
 */
function crcm_user_can_rent($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $user = get_user_by('ID', $user_id);
    
    if (!$user) {
        return false;
    }
    
    return in_array('crcm_customer', $user->roles) || in_array('crcm_manager', $user->roles) || in_array('administrator', $user->roles);
}

/**
 * Check if user can manage rentals
 */
function crcm_user_can_manage() {
    return current_user_can('crcm_manage_bookings') || current_user_can('manage_options');
}

/**
 * Format price with currency
 */
function crcm_format_price($amount, $currency = '€') {
    return $currency . number_format($amount, 2);
}

/**
 * Get vehicle types
 */
function crcm_get_vehicle_types() {
    return array(
        'auto' => __('Car', 'custom-rental-manager'),
        'scooter' => __('Scooter', 'custom-rental-manager'),
        'moto' => __('Motorcycle', 'custom-rental-manager'),
        'bicicletta' => __('Bicycle', 'custom-rental-manager'),
    );
}

/**
 * Get transmission types
 */
function crcm_get_transmission_types() {
    return array(
        'manual' => __('Manual', 'custom-rental-manager'),
        'automatic' => __('Automatic', 'custom-rental-manager'),
    );
}

/**
 * Get fuel types
 */
function crcm_get_fuel_types() {
    return array(
        'gasoline' => __('Gasoline', 'custom-rental-manager'),
        'diesel' => __('Diesel', 'custom-rental-manager'),
        'electric' => __('Electric', 'custom-rental-manager'),
        'hybrid' => __('Hybrid', 'custom-rental-manager'),
    );
}

/**
 * Get booking statuses
 */
function crcm_get_booking_statuses() {
    return array(
        'pending' => __('Pending', 'custom-rental-manager'),
        'confirmed' => __('Confirmed', 'custom-rental-manager'),
        'active' => __('Active', 'custom-rental-manager'),
        'completed' => __('Completed', 'custom-rental-manager'),
        'cancelled' => __('Cancelled', 'custom-rental-manager'),
    );
}

/**
 * Get rental locations
 */
function crcm_get_rental_locations() {
    return array(
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
        'casamicciola' => array(
            'name' => __('Casamicciola Terme', 'custom-rental-manager'),
            'address' => 'Casamicciola Terme',
            'coordinates' => array('lat' => 40.7470, 'lng' => 13.9060),
        ),
        'lacco_ameno' => array(
            'name' => __('Lacco Ameno', 'custom-rental-manager'),
            'address' => 'Lacco Ameno',
            'coordinates' => array('lat' => 40.7520, 'lng' => 13.8830),
        ),
    );
}

/**
 * Calculate date difference in days
 */
function crcm_calculate_days($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    return max(1, $interval->days);
}

/**
 * Check if date is weekend
 */
function crcm_is_weekend($date) {
    $day_of_week = date('N', strtotime($date));
    return $day_of_week >= 6; // Saturday (6) or Sunday (7)
}

/**
 * Check if date is in range
 */
function crcm_date_in_range($date, $start_date, $end_date) {
    $check_date = new DateTime($date);
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    return $check_date >= $start && $check_date <= $end;
}

/**
 * Generate random booking reference
 */
function crcm_generate_booking_reference() {
    return 'CBR-' . date('ymd') . '-' . strtoupper(wp_generate_password(4, false));
}

/**
 * Log debug message
 */
function crcm_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("CRCM [{$level}]: " . $message);
    }
}

/**
 * Send email notification
 */
function crcm_send_email($to, $subject, $message, $headers = array()) {
    $default_headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . crcm_get_setting('company_name', 'Costabilerent') . ' <' . crcm_get_setting('company_email', get_option('admin_email')) . '>',
    );
    
    $headers = array_merge($default_headers, $headers);
    
    return wp_mail($to, $subject, $message, $headers);
}

/**
 * Get vehicle availability for a date range
 */
function crcm_check_vehicle_availability($vehicle_id, $start_date, $end_date) {
    global $wpdb;
    
    // Get vehicle quantity
    $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
    $total_quantity = isset($vehicle_data['quantity']) ? intval($vehicle_data['quantity']) : 1;
    
    // Count overlapping bookings
    $overlapping_bookings = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->postmeta} pm1 
        INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id 
        INNER JOIN {$wpdb->postmeta} pm3 ON pm1.post_id = pm3.post_id 
        INNER JOIN {$wpdb->postmeta} pm4 ON pm1.post_id = pm4.post_id 
        INNER JOIN {$wpdb->posts} p ON pm1.post_id = p.ID 
        WHERE pm1.meta_key = '_crcm_booking_data' 
        AND pm2.meta_key = '_crcm_booking_data' 
        AND pm3.meta_key = '_crcm_booking_data' 
        AND pm4.meta_key = '_crcm_booking_data' 
        AND p.post_type = 'crcm_booking' 
        AND p.post_status = 'publish' 
        AND JSON_EXTRACT(pm1.meta_value, '$.vehicle_id') = %s 
        AND JSON_EXTRACT(pm2.meta_value, '$.pickup_date') <= %s 
        AND JSON_EXTRACT(pm3.meta_value, '$.return_date') >= %s
    ", $vehicle_id, $end_date, $start_date));
    
    $available_quantity = $total_quantity - $overlapping_bookings;
    
    return max(0, $available_quantity);
}

/**
 * Get customer booking history
 */
function crcm_get_customer_bookings($customer_id, $limit = 10) {
    $bookings = get_posts(array(
        'post_type' => 'crcm_booking',
        'posts_per_page' => $limit,
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
    
    return $bookings;
}

/**
 * Calculate booking total
 */
function crcm_calculate_booking_total($vehicle_id, $start_date, $end_date, $extras = array(), $insurance = 'basic') {
    $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
    $extras_data = get_post_meta($vehicle_id, '_crcm_extras_data', true);
    $insurance_data = get_post_meta($vehicle_id, '_crcm_insurance_data', true);
    
    $days = crcm_calculate_days($start_date, $end_date);
    $base_rate = floatval($pricing_data['daily_rate'] ?? 0);
    $total = $base_rate * $days;
    
    // Add extras
    if (!empty($extras) && !empty($extras_data)) {
        foreach ($extras as $extra_index) {
            if (isset($extras_data[$extra_index])) {
                $total += floatval($extras_data[$extra_index]['daily_rate']) * $days;
            }
        }
    }
    
    // Add insurance
    if ($insurance === 'premium' && !empty($insurance_data['premium']['enabled'])) {
        $total += floatval($insurance_data['premium']['daily_rate']) * $days;
    }
    
    return $total;
}

/**
 * Sanitize booking data
 */
function crcm_sanitize_booking_data($data) {
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
 * Validate booking data
 */
function crcm_validate_booking_data($data) {
    $errors = array();
    
    // Required fields
    $required_fields = array('customer_id', 'vehicle_id', 'pickup_date', 'return_date');
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = sprintf(__('Field %s is required', 'custom-rental-manager'), $field);
        }
    }
    
    // Date validation
    if (!empty($data['pickup_date']) && !empty($data['return_date'])) {
        $pickup = new DateTime($data['pickup_date']);
        $return = new DateTime($data['return_date']);
        
        if ($return <= $pickup) {
            $errors[] = __('Return date must be after pickup date', 'custom-rental-manager');
        }
        
        if ($pickup < new DateTime('today')) {
            $errors[] = __('Pickup date cannot be in the past', 'custom-rental-manager');
        }
    }
    
    // Vehicle existence
    if (!empty($data['vehicle_id'])) {
        $vehicle = get_post($data['vehicle_id']);
        if (!$vehicle || $vehicle->post_type !== 'crcm_vehicle') {
            $errors[] = __('Invalid vehicle selected', 'custom-rental-manager');
        }
    }
    
    // Customer existence
    if (!empty($data['customer_id'])) {
        $customer = get_user_by('ID', $data['customer_id']);
        if (!$customer || !in_array('crcm_customer', $customer->roles)) {
            $errors[] = __('Invalid customer selected', 'custom-rental-manager');
        }
    }
    
    return $errors;
}

/**
 * Create default vehicle meta structure
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
 * Create default booking meta structure
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
 * Clean up plugin data on uninstall
 */
function crcm_cleanup_plugin_data() {
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
    delete_option('crcm_settings');
    delete_option('crcm_plugin_activated');
    delete_option('crcm_activation_time');
    
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
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Debug function to display system info
 */
function crcm_debug_info() {
    if (!current_user_can('manage_options')) {
        return;
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
    );
    
    echo '<div class="crcm-debug-info">';
    echo '<h3>CRCM Debug Information</h3>';
    echo '<table class="widefat">';
    foreach ($info as $key => $value) {
        echo '<tr><td><strong>' . esc_html($key) . '</strong></td><td>' . esc_html($value) . '</td></tr>';
    }
    echo '</table>';
    echo '</div>';
}
