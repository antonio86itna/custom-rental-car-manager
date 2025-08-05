<?php
/**
 * Enhanced Functions File - WITH COMPLETE USER ROLE MANAGEMENT
 * 
 * Updated functions with integrated user role management system,
 * AJAX handlers, and complete customer management capabilities.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ===============================================
// ENHANCED USER ROLE MANAGEMENT SYSTEM
// ===============================================

/**
 * Create and manage custom user roles for rental system
 * Called on plugin activation or via repair tool
 */
function crcm_create_custom_user_roles() {
    // Remove existing roles to ensure clean setup
    remove_role('crcm_customer');
    remove_role('crcm_manager');
    
    // Create Rental Customer Role
    add_role('crcm_customer', __('Rental Customer', 'custom-rental-manager'), array(
        'read' => true,
        
        // Booking capabilities
        'crcm_view_own_bookings' => true,
        'crcm_create_booking_request' => true,
        'crcm_cancel_own_bookings' => true,
        
        // Profile management
        'crcm_edit_own_profile' => true,
        'crcm_view_own_rental_history' => true,
        
        // Frontend interactions
        'crcm_submit_feedback' => true,
        'crcm_contact_support' => true,
    ));
    
    // Create Rental Manager Role
    add_role('crcm_manager', __('Rental Manager', 'custom-rental-manager'), array(
        'read' => true,
        'edit_posts' => true,
        'edit_others_posts' => true,
        'publish_posts' => true,
        'delete_posts' => true,
        'delete_others_posts' => true,
        'manage_categories' => true,
        'upload_files' => true,
        'edit_files' => true,
        'moderate_comments' => true,
        
        // Vehicle Management
        'crcm_manage_vehicles' => true,
        'crcm_edit_vehicles' => true,
        'crcm_edit_others_vehicles' => true,
        'crcm_publish_vehicles' => true,
        'crcm_delete_vehicles' => true,
        'crcm_delete_others_vehicles' => true,
        'crcm_read_private_vehicles' => true,
        
        // Booking Management
        'crcm_manage_bookings' => true,
        'crcm_edit_booking' => true,
        'crcm_read_booking' => true,
        'crcm_delete_booking' => true,
        'crcm_edit_bookings' => true,
        'crcm_edit_others_bookings' => true,
        'crcm_publish_bookings' => true,
        'crcm_delete_bookings' => true,
        'crcm_delete_others_bookings' => true,
        'crcm_read_private_bookings' => true,
        'crcm_edit_private_bookings' => true,
        'crcm_edit_published_bookings' => true,
        'crcm_delete_private_bookings' => true,
        'crcm_delete_published_bookings' => true,
        'crcm_view_all_bookings' => true,
        'crcm_confirm_bookings' => true,
        'crcm_cancel_bookings' => true,
        
        // Customer Management
        'crcm_manage_customers' => true,
        'crcm_view_customer_data' => true,
        'crcm_edit_customer_profiles' => true,
        'crcm_view_customer_bookings' => true,
        'crcm_create_customer_accounts' => true,
        
        // Financial Management
        'crcm_manage_pricing' => true,
        'crcm_apply_discounts' => true,
        'crcm_view_revenue_reports' => true,
        'crcm_manage_payments' => true,
        
        // Reports and Analytics
        'crcm_view_reports' => true,
        'crcm_export_data' => true,
        'crcm_view_analytics' => true,
        
        // System Management
        'crcm_manage_locations' => true,
        'crcm_manage_extras' => true,
        'crcm_manage_insurance' => true,
        'crcm_configure_settings' => true,
    ));
    
    // Add capabilities to Administrator
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_capabilities = array(
            // Vehicle capabilities
            'crcm_manage_vehicles', 'crcm_edit_vehicles', 'crcm_edit_others_vehicles',
            'crcm_publish_vehicles', 'crcm_delete_vehicles', 'crcm_delete_others_vehicles',
            'crcm_read_private_vehicles',
            
            // Booking capabilities
            'crcm_manage_bookings', 'crcm_edit_booking', 'crcm_read_booking',
            'crcm_delete_booking', 'crcm_edit_bookings', 'crcm_edit_others_bookings',
            'crcm_publish_bookings', 'crcm_delete_bookings', 'crcm_delete_others_bookings',
            'crcm_read_private_bookings', 'crcm_edit_private_bookings',
            'crcm_edit_published_bookings', 'crcm_delete_private_bookings',
            'crcm_delete_published_bookings', 'crcm_view_all_bookings',
            'crcm_confirm_bookings', 'crcm_cancel_bookings',
            
            // Customer capabilities
            'crcm_manage_customers', 'crcm_view_customer_data', 'crcm_edit_customer_profiles',
            'crcm_view_customer_bookings', 'crcm_create_customer_accounts',
            
            // Financial capabilities
            'crcm_manage_pricing', 'crcm_apply_discounts', 'crcm_view_revenue_reports',
            'crcm_manage_payments',
            
            // Reporting capabilities
            'crcm_view_reports', 'crcm_export_data', 'crcm_view_analytics',
            
            // System capabilities
            'crcm_manage_locations', 'crcm_manage_extras', 'crcm_manage_insurance',
            'crcm_configure_settings', 'crcm_manage_roles',
        );
        
        foreach ($admin_capabilities as $cap) {
            $admin_role->add_cap($cap);
        }
    }
    
    // Refresh roles for the current site
    if ( method_exists( wp_roles(), 'for_site' ) ) {
        wp_roles()->for_site();
    }
    
    // Set option to track that roles have been created
    update_option('crcm_roles_created', true);
    update_option('crcm_roles_version', '1.0.0');
}

// Role creation is triggered on plugin activation or through the repair tool.

/**
 * Assign default customer role to new users
 */
function crcm_assign_default_customer_role($user_id) {
    $user = get_user_by('ID', $user_id);
    
    // If user has no role or only 'subscriber', assign customer role
    if (empty($user->roles) || (count($user->roles) === 1 && in_array('subscriber', $user->roles))) {
        $user->set_role('crcm_customer');
        
        // Set default meta values for rental customers
        update_user_meta($user_id, 'crcm_customer_status', 'active');
        update_user_meta($user_id, 'crcm_registration_date', current_time('mysql'));
        update_user_meta($user_id, 'crcm_total_bookings', 0);
    }
}
add_action('user_register', 'crcm_assign_default_customer_role');

/**
 * Add rental role column to users table
 */
function crcm_add_rental_role_column($columns) {
    $columns['crcm_rental_role'] = __('Rental Role', 'custom-rental-manager');
    return $columns;
}
add_filter('manage_users_columns', 'crcm_add_rental_role_column');

/**
 * Show rental role in users table
 */
function crcm_show_rental_role_column($value, $column_name, $user_id) {
    if ($column_name === 'crcm_rental_role') {
        $user = get_user_by('ID', $user_id);
        $roles = $user->roles;
        
        if (in_array('crcm_customer', $roles)) {
            $total_bookings = get_user_meta($user_id, 'crcm_total_bookings', true) ?: 0;
            $status = get_user_meta($user_id, 'crcm_customer_status', true) ?: 'active';
            
            $badge_class = $status === 'active' ? 'customer-active' : 'customer-inactive';
            
            return sprintf(
                '<span class="crcm-role-badge %s">🙋‍♂️ Customer</span><br><small>%d bookings</small>',
                $badge_class,
                $total_bookings
            );
        } elseif (in_array('crcm_manager', $roles)) {
            return '<span class="crcm-role-badge manager">👨‍💼 Manager</span>';
        } elseif (in_array('administrator', $roles)) {
            return '<span class="crcm-role-badge admin">👑 Admin</span>';
        }
        
        return '<span class="crcm-role-badge other">👤 ' . ucfirst(reset($roles)) . '</span>';
    }
    
    return $value;
}
add_action('manage_users_custom_column', 'crcm_show_rental_role_column', 10, 3);

/**
 * Add role filter links to users page
 */
function crcm_add_role_filter_links($views) {
    $user_counts = count_users();
    $customer_count = $user_counts['avail_roles']['crcm_customer'] ?? 0;
    $manager_count = $user_counts['avail_roles']['crcm_manager'] ?? 0;
    
    $current_role = isset($_GET['role']) ? $_GET['role'] : '';
    
    $views['crcm_customer'] = sprintf(
        '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
        add_query_arg('role', 'crcm_customer', admin_url('users.php')),
        $current_role === 'crcm_customer' ? 'current' : '',
        __('Rental Customers', 'custom-rental-manager'),
        $customer_count
    );
    
    $views['crcm_manager'] = sprintf(
        '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
        add_query_arg('role', 'crcm_manager', admin_url('users.php')),
        $current_role === 'crcm_manager' ? 'current' : '',
        __('Rental Managers', 'custom-rental-manager'),
        $manager_count
    );
    
    return $views;
}
add_filter('views_users', 'crcm_add_role_filter_links');

/**
 * Add rental fields to user profile
 */
function crcm_add_rental_fields_to_profile($user) {
    $user_roles = $user->roles;
    
    if (in_array('crcm_customer', $user_roles) || in_array('crcm_manager', $user_roles)) {
        ?>
        <h3><?php _e('Rental System Information', 'custom-rental-manager'); ?></h3>
        <table class="form-table">
            <?php if (in_array('crcm_customer', $user_roles)): ?>
                <tr>
                    <th><label for="phone"><?php _e('Phone Number', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <input type="tel" id="phone" name="phone" value="<?php echo esc_attr(get_user_meta($user->ID, 'phone', true)); ?>" class="regular-text" />
                        <p class="description"><?php _e('Contact phone number', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="address"><?php _e('Address', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <textarea id="address" name="address" rows="3" class="regular-text"><?php echo esc_textarea(get_user_meta($user->ID, 'address', true)); ?></textarea>
                        <p class="description"><?php _e('Full address for delivery/documentation', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="license_number"><?php _e('License Number', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <input type="text" id="license_number" name="license_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'license_number', true)); ?>" class="regular-text" />
                        <p class="description"><?php _e('Driving license number', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="emergency_contact"><?php _e('Emergency Contact', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <input type="text" id="emergency_contact" name="emergency_contact" value="<?php echo esc_attr(get_user_meta($user->ID, 'emergency_contact', true)); ?>" class="regular-text" />
                        <p class="description"><?php _e('Emergency contact person and phone', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="crcm_customer_status"><?php _e('Customer Status', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <?php $status = get_user_meta($user->ID, 'crcm_customer_status', true) ?: 'active'; ?>
                        <select id="crcm_customer_status" name="crcm_customer_status">
                            <option value="active" <?php selected($status, 'active'); ?>><?php _e('Active', 'custom-rental-manager'); ?></option>
                            <option value="suspended" <?php selected($status, 'suspended'); ?>><?php _e('Suspended', 'custom-rental-manager'); ?></option>
                            <option value="blacklisted" <?php selected($status, 'blacklisted'); ?>><?php _e('Blacklisted', 'custom-rental-manager'); ?></option>
                        </select>
                        <p class="description"><?php _e('Customer account status', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
            <?php endif; ?>
            
            <tr>
                <th><?php _e('Rental Statistics', 'custom-rental-manager'); ?></th>
                <td>
                    <?php if (in_array('crcm_customer', $user_roles)): ?>
                        <?php
                        $total_bookings = get_user_meta($user->ID, 'crcm_total_bookings', true) ?: 0;
                        $registration_date = get_user_meta($user->ID, 'crcm_registration_date', true);
                        $last_booking = get_user_meta($user->ID, 'crcm_last_booking_date', true);
                        ?>
                        <p><strong><?php _e('Total Bookings:', 'custom-rental-manager'); ?></strong> <?php echo $total_bookings; ?></p>
                        <?php if ($registration_date): ?>
                            <p><strong><?php _e('Customer Since:', 'custom-rental-manager'); ?></strong> <?php echo date('d/m/Y', strtotime($registration_date)); ?></p>
                        <?php endif; ?>
                        <?php if ($last_booking): ?>
                            <p><strong><?php _e('Last Booking:', 'custom-rental-manager'); ?></strong> <?php echo date('d/m/Y', strtotime($last_booking)); ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><?php _e('Manager account - no customer statistics', 'custom-rental-manager'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }
}
add_action('show_user_profile', 'crcm_add_rental_fields_to_profile');
add_action('edit_user_profile', 'crcm_add_rental_fields_to_profile');

/**
 * Save rental profile fields
 */
function crcm_save_rental_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    // Save rental-specific fields
    $rental_fields = array(
        'phone', 'address', 'license_number', 'emergency_contact', 'crcm_customer_status'
    );
    
    foreach ($rental_fields as $field) {
        if (isset($_POST[$field])) {
            update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('personal_options_update', 'crcm_save_rental_profile_fields');
add_action('edit_user_profile_update', 'crcm_save_rental_profile_fields');

/**
 * AJAX: Search customers with role filter
 */
function crcm_ajax_search_customers() {
    check_ajax_referer('crcm_admin_nonce', 'nonce');

    if (!current_user_can('crcm_manage_customers') && !current_user_can('manage_options')) {
        wp_send_json_error(__('You are not allowed to search customers.', 'custom-rental-manager'));
    }

    $query = sanitize_text_field($_POST['query'] ?? '');
    
    if (strlen($query) < 2) {
        wp_send_json_error('Query too short');
    }
    
    // Search users with customer role
    $users = get_users(array(
        'role' => 'crcm_customer',
        'search' => '*' . $query . '*',
        'search_columns' => array('user_login', 'user_email', 'display_name'),
        'number' => 10,
        'orderby' => 'display_name',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'crcm_customer_status',
                'value' => 'active',
                'compare' => '='
            )
        )
    ));
    
    $results = array();
    foreach ($users as $user) {
        $results[] = array(
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'phone' => get_user_meta($user->ID, 'phone', true),
        );
    }
    
    wp_send_json_success($results);
}
add_action('wp_ajax_crcm_search_customers', 'crcm_ajax_search_customers');

/**
 * AJAX: Create customer account
 */
function crcm_ajax_create_customer() {
    check_ajax_referer('crcm_admin_nonce', 'nonce');
    
    if (!current_user_can('create_users')) {
        wp_send_json_error('Permission denied');
    }
    
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    
    if (!$name || !$email) {
        wp_send_json_error('Name and email are required');
    }
    
    // Create user
    $user_id = wp_create_user($email, wp_generate_password(), $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error($user_id->get_error_message());
    }
    
    // Set user data
    wp_update_user(array(
        'ID' => $user_id,
        'display_name' => $name,
        'first_name' => explode(' ', $name)[0],
        'last_name' => isset(explode(' ', $name)[1]) ? explode(' ', $name)[1] : '',
    ));
    
    // Set role and meta
    $user = new WP_User($user_id);
    $user->set_role('crcm_customer');
    
    update_user_meta($user_id, 'phone', $phone);
    update_user_meta($user_id, 'crcm_customer_status', 'active');
    update_user_meta($user_id, 'crcm_registration_date', current_time('mysql'));
    update_user_meta($user_id, 'crcm_total_bookings', 0);
    
    wp_send_json_success(array(
        'user_id' => $user_id,
        'name' => $name,
        'email' => $email,
        'edit_link' => get_edit_user_link($user_id)
    ));
}
add_action('wp_ajax_crcm_create_customer', 'crcm_ajax_create_customer');

// ===============================================
// EXISTING UTILITY FUNCTIONS (UNCHANGED)
// ===============================================

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
 * Calculate rental days between two dates and times.
 *
 * @param string   $pickup_date  Pickup date (Y-m-d).
 * @param string   $return_date  Return date (Y-m-d).
 * @param string   $pickup_time  Optional pickup time (H:i).
 * @param string   $return_time  Optional return time (H:i).
 * @param int|null $vehicle_id   Optional vehicle ID to evaluate late return rules.
 *
 * @return int Number of rental days (minimum 1).
 */
function crcm_calculate_rental_days($pickup_date, $return_date, $pickup_time = '', $return_time = '', $vehicle_id = 0) {
    $pickup = new DateTime(trim($pickup_date . ' ' . ($pickup_time ?: '00:00')));
    $return = new DateTime(trim($return_date . ' ' . ($return_time ?: '00:00')));
    $interval = $pickup->diff($return);

    $days = max(1, $interval->days); // Minimum 1 day

    if ($vehicle_id) {
        $misc_data = get_post_meta($vehicle_id, '_crcm_misc_data', true);
        if (!empty($misc_data['late_return_rule']) && !empty($return_time) && !empty($misc_data['late_return_time'])) {
            if (strtotime($return_time) > strtotime($misc_data['late_return_time'])) {
                $days++;
            }
        }
    }

    return $days;
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
        'meta_query' => array(
            array(
                'key' => 'crcm_customer_status',
                'value' => 'active',
                'compare' => '='
            )
        )
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
 * Create customer account with proper role - ENHANCED VERSION
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
    if (isset($customer_data['emergency_contact'])) {
        update_user_meta($user_id, 'emergency_contact', $customer_data['emergency_contact']);
    }

    // Set customer status and registration data
    update_user_meta($user_id, 'crcm_customer_status', 'active');
    update_user_meta($user_id, 'crcm_registration_date', current_time('mysql'));
    update_user_meta($user_id, 'crcm_total_bookings', 0);

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
/**
 * Calculate vehicle pricing including custom rates for each day.
 *
 * @param int    $vehicle_id  Vehicle post ID.
 * @param string $pickup_date Rental start date (Y-m-d).
 * @param string $return_date Rental end date (Y-m-d).
 *
 * @return float Total base cost including custom rate adjustments.
 */
function crcm_calculate_vehicle_pricing($vehicle_id, $pickup_date, $return_date) {
    $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);

    if (empty($pricing_data) || !isset($pricing_data['daily_rate'])) {
        return 0;
    }

    $base_rate   = floatval($pricing_data['daily_rate']);
    $base_total  = 0;

    $current_date = new DateTime($pickup_date);
    $end_date     = new DateTime($return_date);

    while ($current_date < $end_date) {
        $base_total += $base_rate;

        if (!empty($pricing_data['custom_rates'])) {
            foreach ($pricing_data['custom_rates'] as $rate) {
                if (empty($rate['extra_rate'])) {
                    continue;
                }

                switch ($rate['type']) {
                    case 'weekends':
                        $day_of_week = $current_date->format('N');
                        if ($day_of_week >= 6) {
                            $base_total += floatval($rate['extra_rate']);
                        }
                        break;

                    case 'date_range':
                        if (!empty($rate['start_date']) && !empty($rate['end_date'])) {
                            $rate_start = new DateTime($rate['start_date']);
                            $rate_end   = new DateTime($rate['end_date']);
                            if ($current_date >= $rate_start && $current_date <= $rate_end) {
                                $base_total += floatval($rate['extra_rate']);
                            }
                        }
                        break;

                    case 'specific_days':
                        if (!empty($rate['dates']) && is_array($rate['dates'])) {
                            $current_str = $current_date->format('Y-m-d');
                            if (in_array($current_str, $rate['dates'], true)) {
                                $base_total += floatval($rate['extra_rate']);
                            }
                        }
                        break;
                }
            }
        }

        $current_date->add(new DateInterval('P1D'));
    }

    return $base_total;
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

// ===============================================
// ADMIN STYLES FOR ROLE MANAGEMENT
// ===============================================

/**
 * Add admin styles for role badges and interface
 */
function crcm_add_admin_role_styles() {
    ?>
    <style>
    /* Role badge styles */
    .crcm-role-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        line-height: 1.4;
    }
    
    .crcm-role-badge.customer-active {
        background: #d4edda;
        color: #155724;
    }
    
    .crcm-role-badge.customer-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .crcm-role-badge.manager {
        background: #cce5ff;
        color: #004085;
    }
    
    .crcm-role-badge.admin {
        background: #fff3cd;
        color: #856404;
    }
    
    .crcm-role-badge.other {
        background: #e2e3e5;
        color: #383d41;
    }
    
    /* Users table improvements */
    .users-php .column-crcm_rental_role {
        width: 150px;
    }
    
    /* User form styles */
    #crcm-customer-fields .form-table {
        margin-top: 0;
    }
    
    #crcm-customer-fields th {
        width: 200px;
    }
    </style>
    <?php
}
add_action('admin_head', 'crcm_add_admin_role_styles');
