<?php
/**
 * Dashboard Helper Functions - MISSING FUNCTIONS ADDED
 * 
 * These functions were missing and causing fatal errors in the dashboard template.
 * Now properly implemented with full statistics and data handling.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get dashboard statistics - MISSING FUNCTION FIXED
 */
function crcm_get_dashboard_stats() {
    $stats = array(
        'total_vehicles' => 0,
        'total_bookings' => 0,
        'pending_bookings' => 0,
        'confirmed_bookings' => 0,
        'active_bookings' => 0,
        'completed_bookings' => 0,
        'cancelled_bookings' => 0,
        'revenue_today' => 0,
        'revenue_week' => 0,
        'revenue_month' => 0,
        'revenue_year' => 0,
        'total_customers' => 0,
        'new_customers_month' => 0
    );
    
    // Count vehicles
    $vehicle_count = wp_count_posts('crcm_vehicle');
    $stats['total_vehicles'] = $vehicle_count->publish ?? 0;
    
    // Count total bookings
    $booking_count = wp_count_posts('crcm_booking');
    $stats['total_bookings'] = $booking_count->publish ?? 0;
    
    // Get all bookings for detailed analysis
    $bookings = get_posts(array(
        'post_type' => 'crcm_booking',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_key' => '_crcm_booking_status'
    ));
    
    $today = date('Y-m-d');
    $week_start = date('Y-m-d', strtotime('-7 days'));
    $month_start = date('Y-m-01');
    $year_start = date('Y-01-01');
    
    foreach ($bookings as $booking) {
        $status = get_post_meta($booking->ID, '_crcm_booking_status', true);
        $pricing = get_post_meta($booking->ID, '_crcm_pricing_breakdown', true);
        $booking_date = get_the_date('Y-m-d', $booking->ID);
        
        // Count by status
        switch ($status) {
            case 'pending':
                $stats['pending_bookings']++;
                break;
            case 'confirmed':
                $stats['confirmed_bookings']++;
                break;
            case 'active':
                $stats['active_bookings']++;
                break;
            case 'completed':
                $stats['completed_bookings']++;
                break;
            case 'cancelled':
                $stats['cancelled_bookings']++;
                break;
        }
        
        // Calculate revenue (only for completed bookings)
        if ($status === 'completed' && isset($pricing['final_total'])) {
            $amount = floatval($pricing['final_total']);
            
            if ($booking_date === $today) {
                $stats['revenue_today'] += $amount;
            }
            
            if ($booking_date >= $week_start) {
                $stats['revenue_week'] += $amount;
            }
            
            if ($booking_date >= $month_start) {
                $stats['revenue_month'] += $amount;
            }
            
            if ($booking_date >= $year_start) {
                $stats['revenue_year'] += $amount;
            }
        }
    }
    
    // Count customers
    $customers = get_users(array('role' => 'crcm_customer'));
    $stats['total_customers'] = count($customers);
    
    // Count new customers this month
    $new_customers = get_users(array(
        'role' => 'crcm_customer',
        'date_query' => array(
            array(
                'after' => $month_start
            )
        )
    ));
    $stats['new_customers_month'] = count($new_customers);
    
    return $stats;
}

/**
 * Get recent activity for dashboard
 */
function crcm_get_recent_activity($limit = 10) {
    $activity = array();
    
    // Recent bookings
    $recent_bookings = get_posts(array(
        'post_type' => 'crcm_booking',
        'posts_per_page' => $limit,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    foreach ($recent_bookings as $booking) {
        $booking_data = get_post_meta($booking->ID, '_crcm_booking_data', true);
        $booking_status = get_post_meta($booking->ID, '_crcm_booking_status', true);
        $customer = null;
        
        if (isset($booking_data['customer_id'])) {
            $customer = get_user_by('ID', $booking_data['customer_id']);
        }
        
        $vehicle = null;
        if (isset($booking_data['vehicle_id'])) {
            $vehicle = get_post($booking_data['vehicle_id']);
        }
        
        $activity[] = array(
            'type' => 'booking',
            'title' => $booking->post_title,
            'customer' => $customer ? $customer->display_name : __('Unknown Customer', 'custom-rental-manager'),
            'vehicle' => $vehicle ? $vehicle->post_title : __('Unknown Vehicle', 'custom-rental-manager'),
            'status' => $booking_status ?: 'pending',
            'date' => get_the_date('d/m/Y H:i', $booking->ID),
            'link' => get_edit_post_link($booking->ID)
        );
    }
    
    return $activity;
}

/**
 * Get vehicles needing attention
 */
function crcm_get_vehicles_attention() {
    $vehicles = get_posts(array(
        'post_type' => 'crcm_vehicle',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    $attention = array();
    
    foreach ($vehicles as $vehicle) {
        $vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
        $availability = intval($vehicle_data['quantity'] ?? 0);
        
        if ($availability <= 1) {
            $priority = $availability === 0 ? 'high' : 'medium';
            $icon = $availability === 0 ? '🔴' : '🟡';
            
            $attention[] = array(
                'vehicle' => $vehicle->post_title,
                'issue' => sprintf(__('Low availability: %d units', 'custom-rental-manager'), $availability),
                'priority' => $priority,
                'icon' => $icon,
                'link' => get_edit_post_link($vehicle->ID)
            );
        }
        
        // Check for missing pricing
        $pricing_data = get_post_meta($vehicle->ID, '_crcm_pricing_data', true);
        if (empty($pricing_data['daily_rate']) || $pricing_data['daily_rate'] <= 0) {
            $attention[] = array(
                'vehicle' => $vehicle->post_title,
                'issue' => __('Missing or invalid daily rate', 'custom-rental-manager'),
                'priority' => 'medium',
                'icon' => '💰',
                'link' => get_edit_post_link($vehicle->ID)
            );
        }
        
        // Check for missing images
        if (!has_post_thumbnail($vehicle->ID)) {
            $attention[] = array(
                'vehicle' => $vehicle->post_title,
                'issue' => __('Missing featured image', 'custom-rental-manager'),
                'priority' => 'low',
                'icon' => '📸',
                'link' => get_edit_post_link($vehicle->ID)
            );
        }
    }
    
    // Sort by priority
    usort($attention, function($a, $b) {
        $priority_order = array('high' => 3, 'medium' => 2, 'low' => 1);
        return $priority_order[$b['priority']] - $priority_order[$a['priority']];
    });
    
    return $attention;
}

/**
 * Get upcoming bookings
 */
function crcm_get_upcoming_bookings($days = 7, $limit = 10) {
    $today = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+{$days} days"));
    
    global $wpdb;
    
    // Query bookings with pickup dates in the next X days
    $booking_ids = $wpdb->get_col($wpdb->prepare("
        SELECT post_id 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_crcm_booking_data' 
        AND JSON_EXTRACT(meta_value, '$.pickup_date') BETWEEN %s AND %s
        ORDER BY JSON_EXTRACT(meta_value, '$.pickup_date') ASC
        LIMIT %d
    ", $today, $end_date, $limit));
    
    $upcoming = array();
    
    foreach ($booking_ids as $booking_id) {
        $booking = get_post($booking_id);
        if (!$booking || $booking->post_type !== 'crcm_booking') {
            continue;
        }
        
        $booking_data = get_post_meta($booking_id, '_crcm_booking_data', true);
        $booking_status = get_post_meta($booking_id, '_crcm_booking_status', true);
        
        $customer = null;
        if (isset($booking_data['customer_id'])) {
            $customer = get_user_by('ID', $booking_data['customer_id']);
        }
        
        $vehicle = null;
        if (isset($booking_data['vehicle_id'])) {
            $vehicle = get_post($booking_data['vehicle_id']);
        }
        
        $upcoming[] = array(
            'id' => $booking_id,
            'title' => $booking->post_title,
            'customer' => $customer ? $customer->display_name : __('Unknown Customer', 'custom-rental-manager'),
            'vehicle' => $vehicle ? $vehicle->post_title : __('Unknown Vehicle', 'custom-rental-manager'),
            'pickup_date' => $booking_data['pickup_date'] ?? '',
            'pickup_time' => $booking_data['pickup_time'] ?? '',
            'return_date' => $booking_data['return_date'] ?? '',
            'return_time' => $booking_data['return_time'] ?? '',
            'status' => $booking_status ?: 'pending',
            'link' => get_edit_post_link($booking_id)
        );
    }
    
    return $upcoming;
}

/**
 * Get monthly revenue chart data
 */
function crcm_get_monthly_revenue_chart($months = 12) {
    $chart_data = array();
    
    for ($i = $months - 1; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-{$i} months"));
        $month_end = date('Y-m-t', strtotime("-{$i} months"));
        $month_name = date('M Y', strtotime("-{$i} months"));
        
        // Get completed bookings for this month
        $bookings = get_posts(array(
            'post_type' => 'crcm_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_crcm_booking_status',
                    'value' => 'completed',
                    'compare' => '='
                )
            ),
            'date_query' => array(
                array(
                    'after' => $month_start,
                    'before' => $month_end,
                    'inclusive' => true
                )
            )
        ));
        
        $revenue = 0;
        foreach ($bookings as $booking) {
            $pricing = get_post_meta($booking->ID, '_crcm_pricing_breakdown', true);
            if (isset($pricing['final_total'])) {
                $revenue += floatval($pricing['final_total']);
            }
        }
        
        $chart_data[] = array(
            'month' => $month_name,
            'revenue' => $revenue,
            'bookings' => count($bookings)
        );
    }
    
    return $chart_data;
}

/**
 * Get top performing vehicles
 */
function crcm_get_top_vehicles($limit = 5) {
    $vehicles = get_posts(array(
        'post_type' => 'crcm_vehicle',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    $vehicle_stats = array();
    
    foreach ($vehicles as $vehicle) {
        // Count bookings for this vehicle
        $bookings = get_posts(array(
            'post_type' => 'crcm_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_crcm_booking_data',
                    'value' => '"vehicle_id":"' . $vehicle->ID . '"',
                    'compare' => 'LIKE'
                )
            )
        ));
        
        $total_revenue = 0;
        $completed_bookings = 0;
        
        foreach ($bookings as $booking) {
            $status = get_post_meta($booking->ID, '_crcm_booking_status', true);
            if ($status === 'completed') {
                $completed_bookings++;
                $pricing = get_post_meta($booking->ID, '_crcm_pricing_breakdown', true);
                if (isset($pricing['final_total'])) {
                    $total_revenue += floatval($pricing['final_total']);
                }
            }
        }
        
        if (count($bookings) > 0) {
            $vehicle_stats[] = array(
                'id' => $vehicle->ID,
                'title' => $vehicle->post_title,
                'total_bookings' => count($bookings),
                'completed_bookings' => $completed_bookings,
                'total_revenue' => $total_revenue,
                'average_revenue' => $completed_bookings > 0 ? $total_revenue / $completed_bookings : 0,
                'link' => get_edit_post_link($vehicle->ID)
            );
        }
    }
    
    // Sort by total revenue
    usort($vehicle_stats, function($a, $b) {
        return $b['total_revenue'] - $a['total_revenue'];
    });
    
    return array_slice($vehicle_stats, 0, $limit);
}

/**
 * Get booking status distribution
 */
function crcm_get_booking_status_distribution() {
    $statuses = array(
        'pending' => array('count' => 0, 'label' => __('Pending', 'custom-rental-manager'), 'color' => '#ffa500'),
        'confirmed' => array('count' => 0, 'label' => __('Confirmed', 'custom-rental-manager'), 'color' => '#0073aa'),
        'active' => array('count' => 0, 'label' => __('Active', 'custom-rental-manager'), 'color' => '#00a32a'),
        'completed' => array('count' => 0, 'label' => __('Completed', 'custom-rental-manager'), 'color' => '#135e96'),
        'cancelled' => array('count' => 0, 'label' => __('Cancelled', 'custom-rental-manager'), 'color' => '#d63638')
    );
    
    $bookings = get_posts(array(
        'post_type' => 'crcm_booking',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    foreach ($bookings as $booking) {
        $status = get_post_meta($booking->ID, '_crcm_booking_status', true) ?: 'pending';
        if (isset($statuses[$status])) {
            $statuses[$status]['count']++;
        }
    }
    
    return $statuses;
}

/**
 * Get system health check
 */
function crcm_get_system_health() {
    $health = array(
        'status' => 'good', // good, warning, critical
        'issues' => array(),
        'checks' => array()
    );
    
    // Check if user roles exist
    $customer_role = get_role('crcm_customer');
    $manager_role = get_role('crcm_manager');
    
    if (!$customer_role) {
        $health['issues'][] = __('Customer role is missing', 'custom-rental-manager');
        $health['status'] = 'warning';
    }
    
    if (!$manager_role) {
        $health['issues'][] = __('Manager role is missing', 'custom-rental-manager');
        $health['status'] = 'warning';
    }
    
    // Check for vehicles without pricing
    $vehicles_without_pricing = get_posts(array(
        'post_type' => 'crcm_vehicle',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_crcm_pricing_data',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => '_crcm_pricing_data',
                'value' => '"daily_rate":"0"',
                'compare' => 'LIKE'
            )
        )
    ));
    
    if (!empty($vehicles_without_pricing)) {
        $health['issues'][] = sprintf(__('%d vehicles have missing or zero pricing', 'custom-rental-manager'), count($vehicles_without_pricing));
        $health['status'] = 'warning';
    }
    
    // Check for PHP version
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        $health['issues'][] = sprintf(__('PHP version %s is outdated. PHP 8.0+ recommended.', 'custom-rental-manager'), PHP_VERSION);
        $health['status'] = 'warning';
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, '5.6', '<')) {
        $health['issues'][] = sprintf(__('WordPress version %s is outdated. 5.6+ recommended.', 'custom-rental-manager'), $wp_version);
        $health['status'] = 'warning';
    }
    
    // Success checks
    $health['checks'] = array(
        'php_version' => PHP_VERSION,
        'wp_version' => $wp_version,
        'plugin_version' => CRCM_VERSION,
        'customer_role_exists' => $customer_role ? 'Yes' : 'No',
        'manager_role_exists' => $manager_role ? 'Yes' : 'No',
        'total_vehicles' => wp_count_posts('crcm_vehicle')->publish ?? 0,
        'total_bookings' => wp_count_posts('crcm_booking')->publish ?? 0
    );
    
    return $health;
}
