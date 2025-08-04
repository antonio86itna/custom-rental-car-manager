<?php
/**
 * Plugin Name: Custom Rental Car Manager
 * Plugin URI: https://totaliweb.com/plugins/custom-rental-car-manager
 * Description: Sistema completo per la gestione autonoleggio di auto e scooter per Costabilerent
 * Version: 1.0.0
 * Author: Totaliweb
 * Author URI: https://totaliweb.com
 * Text Domain: custom-rental-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * License: GPL v2 or later
 */

// CRITICAL: Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CRCM_VERSION', '1.0.0');
define('CRCM_PLUGIN_FILE', __FILE__);
define('CRCM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CRCM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRCM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Define brand constants
define('CRCM_BRAND_URL', 'https://totaliweb.com');
define('CRCM_BRAND_NAME', 'Totaliweb');

/**
 * SIMPLIFIED Main Plugin Class - WORKING VERSION
 * 
 * This version focuses on core functionality without complex dependencies
 * to ensure custom post types and menus are registered correctly.
 */
class CRCM_Plugin {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks - SIMPLIFIED AND WORKING
     */
    private function init_hooks() {
        // Load text domain
        add_action('init', array($this, 'load_textdomain'), 1);
        
        // CRITICAL: Register post types early
        add_action('init', array($this, 'register_post_types'), 5);
        
        // Create user roles
        add_action('init', array($this, 'create_user_roles'), 8);
        
        // Admin menu
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // Meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save post data
        add_action('save_post', array($this, 'save_post_data'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        
        // Activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Debug logging
        add_action('init', array($this, 'debug_log'), 999);
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('custom-rental-manager', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * CRITICAL: Register custom post types - SIMPLIFIED VERSION
     */
    public function register_post_types() {
        // Vehicle post type
        register_post_type('crcm_vehicle', array(
            'labels' => array(
                'name' => __('Vehicles', 'custom-rental-manager'),
                'singular_name' => __('Vehicle', 'custom-rental-manager'),
                'add_new' => __('Add New Vehicle', 'custom-rental-manager'),
                'add_new_item' => __('Add New Vehicle', 'custom-rental-manager'),
                'edit_item' => __('Edit Vehicle', 'custom-rental-manager'),
                'new_item' => __('New Vehicle', 'custom-rental-manager'),
                'view_item' => __('View Vehicle', 'custom-rental-manager'),
                'search_items' => __('Search Vehicles', 'custom-rental-manager'),
                'not_found' => __('No vehicles found', 'custom-rental-manager'),
                'not_found_in_trash' => __('No vehicles found in trash', 'custom-rental-manager'),
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-car',
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'show_in_menu' => false, // We'll handle menu manually
        ));
        
        // Booking post type
        register_post_type('crcm_booking', array(
            'labels' => array(
                'name' => __('Bookings', 'custom-rental-manager'),
                'singular_name' => __('Booking', 'custom-rental-manager'),
                'add_new' => __('Add New Booking', 'custom-rental-manager'),
                'add_new_item' => __('Add New Booking', 'custom-rental-manager'),
                'edit_item' => __('Edit Booking', 'custom-rental-manager'),
                'new_item' => __('New Booking', 'custom-rental-manager'),
                'view_item' => __('View Booking', 'custom-rental-manager'),
                'search_items' => __('Search Bookings', 'custom-rental-manager'),
                'not_found' => __('No bookings found', 'custom-rental-manager'),
                'not_found_in_trash' => __('No bookings found in trash', 'custom-rental-manager'),
            ),
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'show_in_menu' => false, // We'll handle menu manually
        ));
        
        // Flush rewrite rules on first run
        if (get_option('crcm_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('crcm_flush_rewrite_rules');
        }
        
        error_log('CRCM: Custom post types registered successfully');
    }
    
    /**
     * Create user roles
     */
    public function create_user_roles() {
        // Customer role
        if (!get_role('crcm_customer')) {
            add_role('crcm_customer', __('Rental Customer', 'custom-rental-manager'), array(
                'read' => true,
                'crcm_view_bookings' => true,
            ));
        }
        
        // Manager role
        if (!get_role('crcm_manager')) {
            add_role('crcm_manager', __('Rental Manager', 'custom-rental-manager'), array(
                'read' => true,
                'edit_posts' => true,
                'edit_others_posts' => true,
                'publish_posts' => true,
                'delete_posts' => true,
                'manage_categories' => true,
                'upload_files' => true,
            ));
        }
        
        // Add capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('edit_posts');
            $admin->add_cap('edit_others_posts');
            $admin->add_cap('publish_posts');
            $admin->add_cap('delete_posts');
        }
    }
    
    /**
     * CRITICAL: Admin menu - SIMPLIFIED VERSION
     */
    public function admin_menu() {
        // Main menu page
        add_menu_page(
            __('Costabilerent', 'custom-rental-manager'),
            __('Costabilerent', 'custom-rental-manager'),
            'edit_posts',
            'crcm-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-car',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Dashboard', 'custom-rental-manager'),
            __('Dashboard', 'custom-rental-manager'),
            'edit_posts',
            'crcm-dashboard',
            array($this, 'dashboard_page')
        );
        
        // Vehicles submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Vehicles', 'custom-rental-manager'),
            __('Vehicles', 'custom-rental-manager'),
            'edit_posts',
            'edit.php?post_type=crcm_vehicle'
        );
        
        // Add Vehicle submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Add Vehicle', 'custom-rental-manager'),
            __('Add Vehicle', 'custom-rental-manager'),
            'edit_posts',
            'post-new.php?post_type=crcm_vehicle'
        );
        
        // Bookings submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Bookings', 'custom-rental-manager'),
            __('Bookings', 'custom-rental-manager'),
            'edit_posts',
            'edit.php?post_type=crcm_booking'
        );
        
        // Add Booking submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Add Booking', 'custom-rental-manager'),
            __('Add Booking', 'custom-rental-manager'),
            'edit_posts',
            'post-new.php?post_type=crcm_booking'
        );
        
        // Calendar submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Calendar', 'custom-rental-manager'),
            __('Calendar', 'custom-rental-manager'),
            'edit_posts',
            'crcm-calendar',
            array($this, 'calendar_page')
        );
        
        // Customers submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Customers', 'custom-rental-manager'),
            __('Customers', 'custom-rental-manager'),
            'edit_posts',
            'crcm-customers',
            array($this, 'customers_page')
        );
        
        // Reports submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Reports', 'custom-rental-manager'),
            __('Reports', 'custom-rental-manager'),
            'edit_posts',
            'crcm-reports',
            array($this, 'reports_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Settings', 'custom-rental-manager'),
            __('Settings', 'custom-rental-manager'),
            'manage_options',
            'crcm-settings',
            array($this, 'settings_page')
        );
        
        error_log('CRCM: Admin menu registered successfully');
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Costabilerent Dashboard', 'custom-rental-manager') . '</h1>';
        
        // Basic stats
        $vehicle_count = wp_count_posts('crcm_vehicle');
        $booking_count = wp_count_posts('crcm_booking');
        
        echo '<div class="crcm-stats" style="display: flex; gap: 20px; margin: 20px 0;">';
        
        echo '<div class="crcm-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; min-width: 200px;">';
        echo '<h3>' . __('Total Vehicles', 'custom-rental-manager') . '</h3>';
        echo '<p style="font-size: 24px; font-weight: bold; color: #0073aa;">' . (isset($vehicle_count->publish) ? $vehicle_count->publish : 0) . '</p>';
        echo '<p><a href="' . admin_url('post-new.php?post_type=crcm_vehicle') . '" class="button button-primary">Add New Vehicle</a></p>';
        echo '</div>';
        
        echo '<div class="crcm-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; min-width: 200px;">';
        echo '<h3>' . __('Total Bookings', 'custom-rental-manager') . '</h3>';
        echo '<p style="font-size: 24px; font-weight: bold; color: #0073aa;">' . (isset($booking_count->publish) ? $booking_count->publish : 0) . '</p>';
        echo '<p><a href="' . admin_url('post-new.php?post_type=crcm_booking') . '" class="button button-primary">Add New Booking</a></p>';
        echo '</div>';
        
        echo '</div>';
        
        // Recent activity
        echo '<h2>' . __('Recent Activity', 'custom-rental-manager') . '</h2>';
        
        // Get recent vehicles
        $recent_vehicles = get_posts(array(
            'post_type' => 'crcm_vehicle',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (!empty($recent_vehicles)) {
            echo '<h3>' . __('Recent Vehicles', 'custom-rental-manager') . '</h3>';
            echo '<ul>';
            foreach ($recent_vehicles as $vehicle) {
                echo '<li><a href="' . get_edit_post_link($vehicle->ID) . '">' . esc_html($vehicle->post_title) . '</a> - ' . get_the_date('d/m/Y H:i', $vehicle->ID) . '</li>';
            }
            echo '</ul>';
        }
        
        // Get recent bookings
        $recent_bookings = get_posts(array(
            'post_type' => 'crcm_booking',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (!empty($recent_bookings)) {
            echo '<h3>' . __('Recent Bookings', 'custom-rental-manager') . '</h3>';
            echo '<ul>';
            foreach ($recent_bookings as $booking) {
                echo '<li><a href="' . get_edit_post_link($booking->ID) . '">' . esc_html($booking->post_title) . '</a> - ' . get_the_date('d/m/Y H:i', $booking->ID) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
    }
    
    /**
     * Calendar page
     */
    public function calendar_page() {
        $template_path = CRCM_PLUGIN_PATH . 'templates/admin/calendar.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Calendar', 'custom-rental-manager') . '</h1>';
            echo '<p>' . __('Calendar functionality will be available in future updates.', 'custom-rental-manager') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Customers page
     */
    public function customers_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Customers', 'custom-rental-manager') . '</h1>';
        
        $customers = get_users(array(
            'role' => 'crcm_customer',
            'number' => 50
        ));
        
        if (!empty($customers)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Name', 'custom-rental-manager') . '</th>';
            echo '<th>' . __('Email', 'custom-rental-manager') . '</th>';
            echo '<th>' . __('Registered', 'custom-rental-manager') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($customers as $customer) {
                echo '<tr>';
                echo '<td>' . esc_html($customer->display_name) . '</td>';
                echo '<td>' . esc_html($customer->user_email) . '</td>';
                echo '<td>' . date('d/m/Y', strtotime($customer->user_registered)) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No customers found.', 'custom-rental-manager') . '</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Reports page
     */
    public function reports_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Reports', 'custom-rental-manager') . '</h1>';
        echo '<p>' . __('Reports functionality will be available in future updates.', 'custom-rental-manager') . '</p>';
        echo '</div>';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $template_path = CRCM_PLUGIN_PATH . 'templates/admin/settings.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Settings', 'custom-rental-manager') . '</h1>';
            echo '<p>' . __('Settings functionality will be available in future updates.', 'custom-rental-manager') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Vehicle meta boxes
        add_meta_box(
            'crcm_vehicle_details',
            __('Vehicle Details', 'custom-rental-manager'),
            array($this, 'vehicle_details_meta_box'),
            'crcm_vehicle',
            'normal',
            'high'
        );
        
        // Booking meta boxes
        add_meta_box(
            'crcm_booking_details',
            __('Booking Details', 'custom-rental-manager'),
            array($this, 'booking_details_meta_box'),
            'crcm_booking',
            'normal',
            'high'
        );
    }
    
    /**
     * Vehicle details meta box
     */
    public function vehicle_details_meta_box($post) {
        wp_nonce_field('crcm_vehicle_meta', 'crcm_vehicle_meta_nonce');
        
        $vehicle_type = get_post_meta($post->ID, '_crcm_vehicle_type', true);
        $daily_rate = get_post_meta($post->ID, '_crcm_daily_rate', true);
        $quantity = get_post_meta($post->ID, '_crcm_quantity', true);
        
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th><label for="vehicle_type">' . __('Vehicle Type', 'custom-rental-manager') . '</label></th>';
        echo '<td>';
        echo '<select name="vehicle_type" id="vehicle_type" class="regular-text">';
        echo '<option value="auto"' . selected($vehicle_type, 'auto', false) . '>Auto</option>';
        echo '<option value="scooter"' . selected($vehicle_type, 'scooter', false) . '>Scooter</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="daily_rate">' . __('Daily Rate (€)', 'custom-rental-manager') . '</label></th>';
        echo '<td><input type="number" name="daily_rate" id="daily_rate" value="' . esc_attr($daily_rate) . '" step="0.01" min="0" class="regular-text"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="quantity">' . __('Quantity Available', 'custom-rental-manager') . '</label></th>';
        echo '<td><input type="number" name="quantity" id="quantity" value="' . esc_attr($quantity ?: 1) . '" min="1" class="regular-text"></td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    /**
     * Booking details meta box
     */
    public function booking_details_meta_box($post) {
        wp_nonce_field('crcm_booking_meta', 'crcm_booking_meta_nonce');
        
        $customer_name = get_post_meta($post->ID, '_crcm_customer_name', true);
        $customer_email = get_post_meta($post->ID, '_crcm_customer_email', true);
        $pickup_date = get_post_meta($post->ID, '_crcm_pickup_date', true);
        $return_date = get_post_meta($post->ID, '_crcm_return_date', true);
        $total_amount = get_post_meta($post->ID, '_crcm_total_amount', true);
        
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th><label for="customer_name">' . __('Customer Name', 'custom-rental-manager') . '</label></th>';
        echo '<td><input type="text" name="customer_name" id="customer_name" value="' . esc_attr($customer_name) . '" class="regular-text"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="customer_email">' . __('Customer Email', 'custom-rental-manager') . '</label></th>';
        echo '<td><input type="email" name="customer_email" id="customer_email" value="' . esc_attr($customer_email) . '" class="regular-text"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="pickup_date">' . __('Pickup Date', 'custom-rental-manager') . '</label></th>';
        echo '<td><input type="date" name="pickup_date" id="pickup_date" value="' . esc_attr($pickup_date) . '" class="regular-text"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="return_date">' . __('Return Date', 'custom-rental-manager') . '</label></th>';
        echo '<td><input type="date" name="return_date" id="return_date" value="' . esc_attr($return_date) . '" class="regular-text"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="total_amount">' . __('Total Amount (€)', 'custom-rental-manager') . '</label></th>';
        echo '<td><input type="number" name="total_amount" id="total_amount" value="' . esc_attr($total_amount) . '" step="0.01" min="0" class="regular-text"></td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    /**
     * Save post data
     */
    public function save_post_data($post_id) {
        // Skip if doing autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Skip if revision
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save vehicle data
        if (get_post_type($post_id) === 'crcm_vehicle') {
            if (isset($_POST['crcm_vehicle_meta_nonce']) && wp_verify_nonce($_POST['crcm_vehicle_meta_nonce'], 'crcm_vehicle_meta')) {
                if (isset($_POST['vehicle_type'])) {
                    update_post_meta($post_id, '_crcm_vehicle_type', sanitize_text_field($_POST['vehicle_type']));
                }
                if (isset($_POST['daily_rate'])) {
                    update_post_meta($post_id, '_crcm_daily_rate', floatval($_POST['daily_rate']));
                }
                if (isset($_POST['quantity'])) {
                    update_post_meta($post_id, '_crcm_quantity', intval($_POST['quantity']));
                }
            }
        }
        
        // Save booking data
        if (get_post_type($post_id) === 'crcm_booking') {
            if (isset($_POST['crcm_booking_meta_nonce']) && wp_verify_nonce($_POST['crcm_booking_meta_nonce'], 'crcm_booking_meta')) {
                if (isset($_POST['customer_name'])) {
                    update_post_meta($post_id, '_crcm_customer_name', sanitize_text_field($_POST['customer_name']));
                }
                if (isset($_POST['customer_email'])) {
                    update_post_meta($post_id, '_crcm_customer_email', sanitize_email($_POST['customer_email']));
                }
                if (isset($_POST['pickup_date'])) {
                    update_post_meta($post_id, '_crcm_pickup_date', sanitize_text_field($_POST['pickup_date']));
                }
                if (isset($_POST['return_date'])) {
                    update_post_meta($post_id, '_crcm_return_date', sanitize_text_field($_POST['return_date']));
                }
                if (isset($_POST['total_amount'])) {
                    update_post_meta($post_id, '_crcm_total_amount', floatval($_POST['total_amount']));
                }
            }
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function admin_assets($hook) {
        // Only on our plugin pages
        if (strpos($hook, 'crcm') === false && !in_array(get_current_screen()->post_type, array('crcm_vehicle', 'crcm_booking'))) {
            return;
        }
        
        wp_enqueue_style('crcm-admin-css', CRCM_PLUGIN_URL . 'assets/css/admin.css', array(), CRCM_VERSION);
        wp_enqueue_script('crcm-admin-js', CRCM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CRCM_VERSION, true);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set flag to flush rewrite rules
        update_option('crcm_flush_rewrite_rules', true);
        
        // Create user roles
        $this->create_user_roles();
        
        // Register post types
        $this->register_post_types();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        error_log('CRCM: Plugin activated successfully');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
        error_log('CRCM: Plugin deactivated');
    }
    
    /**
     * Debug logging
     */
    public function debug_log() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $post_types = get_post_types(array('public' => false, 'show_ui' => true), 'names');
            error_log('CRCM Debug: Available post types: ' . print_r($post_types, true));
            
            if (post_type_exists('crcm_vehicle')) {
                error_log('CRCM Debug: crcm_vehicle post type exists');
            } else {
                error_log('CRCM Debug: crcm_vehicle post type NOT found');
            }
            
            if (post_type_exists('crcm_booking')) {
                error_log('CRCM Debug: crcm_booking post type exists');
            } else {
                error_log('CRCM Debug: crcm_booking post type NOT found');
            }
        }
    }
}

// Initialize the plugin
function crcm_init() {
    return CRCM_Plugin::get_instance();
}

// Hook into WordPress
add_action('plugins_loaded', 'crcm_init');

// Activation notice
add_action('admin_notices', function() {
    if (get_transient('crcm_activation_notice')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Custom Rental Car Manager:</strong> Plugin activated successfully! Custom post types and menu items are now available.</p>';
        echo '</div>';
        delete_transient('crcm_activation_notice');
    }
});

// Set activation notice
register_activation_hook(__FILE__, function() {
    set_transient('crcm_activation_notice', true, 5);
});

// Debug info for admins
if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
    add_action('wp_footer', function() {
        echo '<!-- CRCM Debug: Plugin loaded, version ' . CRCM_VERSION . ' -->';
    });
}
