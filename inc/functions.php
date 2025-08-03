<?php
/**
 * Helper Functions
 * 
 * Contains utility functions used throughout the plugin.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get formatted price
 */
function crcm_format_price($amount, $currency_symbol = null) {
    if ($currency_symbol === null) {
        $currency_symbol = crcm()->get_setting('currency_symbol', '€');
    }

    return $currency_symbol . number_format($amount, 2);
}

/**
 * Get vehicle types
 */
function crcm_get_vehicle_types() {
    return get_terms(array(
        'taxonomy' => 'crcm_vehicle_type',
        'hide_empty' => false,
    ));
}

/**
 * Get locations
 */
function crcm_get_locations() {
    return get_terms(array(
        'taxonomy' => 'crcm_location',
        'hide_empty' => false,
    ));
}

/**
 * Format date for display
 */
function crcm_format_date($date, $format = null) {
    if ($format === null) {
        $format = get_option('date_format');
    }

    return date_i18n($format, strtotime($date));
}

/**
 * Get booking status badge HTML
 */
function crcm_get_status_badge($status) {
    $labels = array(
        'pending' => __('Pending', 'custom-rental-manager'),
        'confirmed' => __('Confirmed', 'custom-rental-manager'),
        'active' => __('Active', 'custom-rental-manager'),
        'completed' => __('Completed', 'custom-rental-manager'),
        'cancelled' => __('Cancelled', 'custom-rental-manager'),
        'refunded' => __('Refunded', 'custom-rental-manager'),
    );

    $label = $labels[$status] ?? $status;

    return '<span class="crcm-status-badge crcm-status-' . esc_attr($status) . '">' . esc_html($label) . '</span>';
}

/**
 * Calculate rental days
 */
function crcm_calculate_rental_days($pickup_date, $return_date) {
    $pickup_timestamp = strtotime($pickup_date);
    $return_timestamp = strtotime($return_date);

    if ($pickup_timestamp >= $return_timestamp) {
        return 0;
    }

    return ceil(($return_timestamp - $pickup_timestamp) / DAY_IN_SECONDS);
}

/**
 * Check if user is rental customer
 */
function crcm_is_rental_customer($user_id = null) {
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $user = get_user_by('id', $user_id);
    return $user && in_array('crcm_customer', $user->roles);
}

/**
 * Get dashboard stats
 */
function crcm_get_dashboard_stats() {
    $stats = array();

    // Total vehicles
    $stats['total_vehicles'] = wp_count_posts('crcm_vehicle')->publish;

    // Total bookings this month
    $first_day_month = date('Y-m-01');
    $last_day_month = date('Y-m-t');

    $bookings_this_month = get_posts(array(
        'post_type' => 'crcm_booking',
        'post_status' => array('publish', 'private'),
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_crcm_pickup_date',
                'value' => array($first_day_month, $last_day_month),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ),
        ),
    ));

    $stats['bookings_this_month'] = count($bookings_this_month);

    // Revenue this month
    $revenue = 0;
    foreach ($bookings_this_month as $booking) {
        $payment_data = get_post_meta($booking->ID, '_crcm_payment_data', true);
        if ($payment_data && isset($payment_data['paid_amount'])) {
            $revenue += $payment_data['paid_amount'];
        }
    }
    $stats['revenue_this_month'] = $revenue;

    // Active bookings
    $active_bookings = get_posts(array(
        'post_type' => 'crcm_booking',
        'post_status' => array('publish', 'private'),
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_crcm_booking_status',
                'value' => 'active',
                'compare' => '=',
            ),
        ),
    ));

    $stats['active_bookings'] = count($active_bookings);

    return $stats;
}

/**
 * Create rental customer account
 */
function crcm_create_customer_account($customer_data) {
    $username = $customer_data['email'];
    $password = wp_generate_password();

    $user_id = wp_create_user($username, $password, $customer_data['email']);

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    // Set user role
    $user = new WP_User($user_id);
    $user->set_role('crcm_customer');

    // Update user meta
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $customer_data['first_name'],
        'last_name' => $customer_data['last_name'],
        'display_name' => $customer_data['first_name'] . ' ' . $customer_data['last_name'],
    ));

    // Store additional profile data
    update_user_meta($user_id, 'crcm_profile_data', $customer_data);

    // Send welcome email with login details
    wp_new_user_notification($user_id, null, 'user');

    return $user_id;
}

/**
 * Get plugin assets URL
 */
function crcm_get_assets_url($file = '') {
    return CRCM_PLUGIN_URL . 'assets/' . $file;
}

/**
 * Get template part
 */
function crcm_get_template_part($slug, $name = null, $vars = array()) {
    $templates = array();

    if ($name) {
        $templates[] = "{$slug}-{$name}.php";
    }
    $templates[] = "{$slug}.php";

    $template = locate_template($templates);

    if (!$template) {
        foreach ($templates as $template_name) {
            $file = CRCM_PLUGIN_PATH . 'templates/' . $template_name;
            if (file_exists($file)) {
                $template = $file;
                break;
            }
        }
    }

    if ($template && $vars) {
        extract($vars);
    }

    if ($template) {
        include $template;
    }
}

/**
 * Check if current page is rental-related
 */
function crcm_is_rental_page() {
    global $post;

    if (!$post) {
        return false;
    }

    // Check if page contains rental shortcodes
    $shortcodes = array('crcm_search_form', 'crcm_vehicle_list', 'crcm_booking_form', 'crcm_customer_dashboard');

    foreach ($shortcodes as $shortcode) {
        if (has_shortcode($post->post_content, $shortcode)) {
            return true;
        }
    }

    return false;
}

/**
 * Log plugin activity
 */
function crcm_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[CRCM ' . strtoupper($level) . '] ' . $message);
    }
}

/**
 * Get country options
 */
function crcm_get_country_options() {
    return array(
        'IT' => __('Italy', 'custom-rental-manager'),
        'DE' => __('Germany', 'custom-rental-manager'),
        'FR' => __('France', 'custom-rental-manager'),
        'ES' => __('Spain', 'custom-rental-manager'),
        'UK' => __('United Kingdom', 'custom-rental-manager'),
        'US' => __('United States', 'custom-rental-manager'),
        'CA' => __('Canada', 'custom-rental-manager'),
        'AU' => __('Australia', 'custom-rental-manager'),
        'NL' => __('Netherlands', 'custom-rental-manager'),
        'BE' => __('Belgium', 'custom-rental-manager'),
        'CH' => __('Switzerland', 'custom-rental-manager'),
        'AT' => __('Austria', 'custom-rental-manager'),
        'OTHER' => __('Other', 'custom-rental-manager'),
    );
}
