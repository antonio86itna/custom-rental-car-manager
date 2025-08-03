<?php
/**
 * Plugin Name: Custom Rental Car Manager
 * Plugin URI: https://www.totaliweb.com/plugins/custom-rental-car-manager
 * Description: Complete rental car and scooter management system for Costabilerent, Ischia. Features vehicle management, bookings, calendar, automated emails, Stripe integration, and customer portal. Powered by Totaliweb.
 * Version: 1.0.0
 * Author: Totaliweb
 * Author URI: https://www.totaliweb.com
 * Text Domain: custom-rental-manager
 * Domain Path: /languages
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Requires PHP: 8.0
 * Network: false
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CRCM_VERSION', '1.0.0');
define('CRCM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRCM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CRCM_BRAND_URL', 'https://www.totaliweb.com');

/**
 * Main Custom Rental Car Manager Class
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */
class CustomRentalCarManager {

    /**
     * The single instance of the class
     */
    private static $_instance = null;

    /**
     * Plugin settings
     */
    private $settings;

    /**
     * Main Custom Rental Car Manager Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->init_includes();
        $this->init_settings();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init_post_types'));
        add_action('init', array($this, 'init_user_roles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('rest_api_init', array($this, 'init_rest_api'));

        // Plugin activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        add_filter('plugin_row_meta', array($this, 'add_row_meta'), 10, 2);

        // AJAX handlers
        add_action('wp_ajax_crcm_search_vehicles', array($this, 'ajax_search_vehicles'));
        add_action('wp_ajax_nopriv_crcm_search_vehicles', array($this, 'ajax_search_vehicles'));
        add_action('wp_ajax_crcm_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_nopriv_crcm_create_booking', array($this, 'ajax_create_booking'));

        // Shortcodes
        add_shortcode('crcm_search_form', array($this, 'search_form_shortcode'));
        add_shortcode('crcm_vehicle_list', array($this, 'vehicle_list_shortcode'));
        add_shortcode('crcm_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('crcm_customer_dashboard', array($this, 'customer_dashboard_shortcode'));
    }

    /**
     * Include required files
     */
    private function init_includes() {
        require_once CRCM_PLUGIN_PATH . 'inc/class-vehicle-manager.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-booking-manager.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-calendar-manager.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-email-manager.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-payment-manager.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-api-endpoints.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-customer-portal.php';
        require_once CRCM_PLUGIN_PATH . 'inc/functions.php';
    }

    /**
     * Initialize plugin settings
     */
    private function init_settings() {
        $this->settings = get_option('crcm_settings', array(
            'company_name' => 'Costabilerent',
            'locations' => array(
                array('name' => 'Ischia Porto', 'address' => 'Via Roma 1, 80077 Ischia Porto NA'),
                array('name' => 'Forio', 'address' => 'Via Marina 1, 80075 Forio NA')
            ),
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'default_wpm' => 200,
            'free_cancellation_days' => 3,
            'late_return_extra_day' => true,
            'late_return_time' => '10:00',
            'stripe_publishable_key' => '',
            'stripe_secret_key' => '',
            'email_from_name' => 'Costabilerent',
            'email_from_email' => 'info@costabilerent.com',
            'show_totaliweb_credit' => true
        ));
    }

    /**
     * Add plugin action links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=crcm-settings') . '">' . __('Settings', 'custom-rental-manager') . '</a>';
        $dashboard_link = '<a href="' . admin_url('admin.php?page=crcm-dashboard') . '">' . __('Dashboard', 'custom-rental-manager') . '</a>';
        $pro_link = '<a href="' . CRCM_BRAND_URL . '/plugins/custom-rental-manager-pro" target="_blank" style="color: #d63384; font-weight: bold;">' . __('Go Pro', 'custom-rental-manager') . '</a>';

        array_unshift($links, $settings_link, $dashboard_link, $pro_link);
        return $links;
    }

    /**
     * Add plugin row meta
     */
    public function add_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $row_meta = array(
                'support' => '<a href="' . CRCM_BRAND_URL . '/support" target="_blank">' . __('Support', 'custom-rental-manager') . '</a>',
                'docs' => '<a href="' . CRCM_BRAND_URL . '/docs/custom-rental-manager" target="_blank">' . __('Documentation', 'custom-rental-manager') . '</a>',
                'rate' => '<a href="https://wordpress.org/plugins/custom-rental-manager/#reviews" target="_blank">' . __('Rate Plugin', 'custom-rental-manager') . '</a>'
            );
            return array_merge($links, $row_meta);
        }
        return $links;
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('custom-rental-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Initialize custom post types
     */
    public function init_post_types() {
        // Vehicles post type
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
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'crcm-vehicles',
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ));

        // Bookings post type
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
            'show_in_menu' => false,
            'supports' => array('title', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'crcm-bookings',
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ));

        // Extras post type
        register_post_type('crcm_extra', array(
            'labels' => array(
                'name' => __('Extras', 'custom-rental-manager'),
                'singular_name' => __('Extra', 'custom-rental-manager'),
                'add_new' => __('Add New Extra', 'custom-rental-manager'),
                'add_new_item' => __('Add New Extra', 'custom-rental-manager'),
                'edit_item' => __('Edit Extra', 'custom-rental-manager'),
                'new_item' => __('New Extra', 'custom-rental-manager'),
                'view_item' => __('View Extra', 'custom-rental-manager'),
                'search_items' => __('Search Extras', 'custom-rental-manager'),
                'not_found' => __('No extras found', 'custom-rental-manager'),
                'not_found_in_trash' => __('No extras found in trash', 'custom-rental-manager'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => false,
            'show_in_rest' => true,
            'rest_base' => 'crcm-extras',
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ));

        // Register taxonomies
        register_taxonomy('crcm_vehicle_type', 'crcm_vehicle', array(
            'labels' => array(
                'name' => __('Vehicle Types', 'custom-rental-manager'),
                'singular_name' => __('Vehicle Type', 'custom-rental-manager'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'hierarchical' => true,
        ));

        register_taxonomy('crcm_location', array('crcm_vehicle', 'crcm_booking'), array(
            'labels' => array(
                'name' => __('Locations', 'custom-rental-manager'),
                'singular_name' => __('Location', 'custom-rental-manager'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'hierarchical' => true,
        ));
    }

    /**
     * Initialize custom user roles
     */
    public function init_user_roles() {
        if (!get_role('crcm_customer')) {
            add_role('crcm_customer', __('Rental Customer', 'custom-rental-manager'), array(
                'read' => true,
                'crcm_view_own_bookings' => true,
                'crcm_create_booking' => true,
                'crcm_cancel_own_booking' => true,
            ));
        }

        if (!get_role('crcm_manager')) {
            add_role('crcm_manager', __('Rental Manager', 'custom-rental-manager'), array(
                'read' => true,
                'edit_posts' => true,
                'edit_others_posts' => true,
                'publish_posts' => true,
                'manage_categories' => true,
                'crcm_manage_vehicles' => true,
                'crcm_manage_bookings' => true,
                'crcm_view_calendar' => true,
                'crcm_manage_extras' => true,
            ));
        }
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'crcm-frontend-style',
            CRCM_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            CRCM_VERSION
        );

        wp_enqueue_script(
            'crcm-frontend-js',
            CRCM_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            CRCM_VERSION,
            true
        );

        // Localize script
        wp_localize_script('crcm-frontend-js', 'crcm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crcm_nonce'),
            'currency_symbol' => $this->settings['currency_symbol'],
            'loading_text' => __('Loading...', 'custom-rental-manager'),
            'error_text' => __('An error occurred. Please try again.', 'custom-rental-manager'),
        ));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'crcm-') === false && !in_array($hook, array('post.php', 'post-new.php', 'edit.php'))) {
            return;
        }

        wp_enqueue_style(
            'crcm-admin-style',
            CRCM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CRCM_VERSION
        );

        wp_enqueue_script(
            'crcm-admin-js',
            CRCM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            CRCM_VERSION,
            true
        );

        // Enqueue date picker CSS
        wp_enqueue_style('jquery-ui-datepicker');

        // Localize admin script
        wp_localize_script('crcm-admin-js', 'crcm_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crcm_admin_nonce'),
            'currency_symbol' => $this->settings['currency_symbol'],
        ));
    }

    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_menu_page(
            __('Rental Manager', 'custom-rental-manager'),
            __('Rental Manager', 'custom-rental-manager'),
            'manage_options',
            'crcm-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-car',
            30
        );

        add_submenu_page(
            'crcm-dashboard',
            __('Dashboard', 'custom-rental-manager'),
            __('Dashboard', 'custom-rental-manager'),
            'manage_options',
            'crcm-dashboard',
            array($this, 'dashboard_page')
        );

        add_submenu_page(
            'crcm-dashboard',
            __('Vehicles', 'custom-rental-manager'),
            __('Vehicles', 'custom-rental-manager'),
            'manage_options',
            'edit.php?post_type=crcm_vehicle'
        );

        add_submenu_page(
            'crcm-dashboard',
            __('Bookings', 'custom-rental-manager'),
            __('Bookings', 'custom-rental-manager'),
            'manage_options',
            'edit.php?post_type=crcm_booking'
        );

        add_submenu_page(
            'crcm-dashboard',
            __('Calendar', 'custom-rental-manager'),
            __('Calendar', 'custom-rental-manager'),
            'manage_options',
            'crcm-calendar',
            array($this, 'calendar_page')
        );

        add_submenu_page(
            'crcm-dashboard',
            __('Extras', 'custom-rental-manager'),
            __('Extras', 'custom-rental-manager'),
            'manage_options',
            'edit.php?post_type=crcm_extra'
        );

        add_submenu_page(
            'crcm-dashboard',
            __('Settings', 'custom-rental-manager'),
            __('Settings', 'custom-rental-manager'),
            'manage_options',
            'crcm-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Initialize admin settings
     */
    public function admin_init() {
        register_setting('crcm_settings_group', 'crcm_settings', array($this, 'sanitize_settings'));
    }

    /**
     * Initialize REST API endpoints
     */
    public function init_rest_api() {
        $api_endpoints = new CRCM_API_Endpoints();
        $api_endpoints->register_routes();
    }

    /**
     * AJAX handler for vehicle search
     */
    public function ajax_search_vehicles() {
        check_ajax_referer('crcm_nonce', 'nonce');

        $search_params = array(
            'pickup_date' => sanitize_text_field($_POST['pickup_date'] ?? ''),
            'return_date' => sanitize_text_field($_POST['return_date'] ?? ''),
            'pickup_location' => sanitize_text_field($_POST['pickup_location'] ?? ''),
            'return_location' => sanitize_text_field($_POST['return_location'] ?? ''),
            'vehicle_type' => sanitize_text_field($_POST['vehicle_type'] ?? ''),
        );

        $vehicle_manager = new CRCM_Vehicle_Manager();
        $available_vehicles = $vehicle_manager->search_available_vehicles($search_params);

        wp_send_json_success($available_vehicles);
    }

    /**
     * AJAX handler for booking creation
     */
    public function ajax_create_booking() {
        check_ajax_referer('crcm_nonce', 'nonce');

        $booking_data = array(
            'vehicle_id' => intval($_POST['vehicle_id'] ?? 0),
            'pickup_date' => sanitize_text_field($_POST['pickup_date'] ?? ''),
            'return_date' => sanitize_text_field($_POST['return_date'] ?? ''),
            'customer_data' => array(
                'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
                'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
                'email' => sanitize_email($_POST['email'] ?? ''),
                'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            ),
            'extras' => array_map('intval', $_POST['extras'] ?? array()),
            'payment_method' => sanitize_text_field($_POST['payment_method'] ?? 'full'),
        );

        $booking_manager = new CRCM_Booking_Manager();
        $result = $booking_manager->create_booking($booking_data);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * Search form shortcode
     */
    public function search_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'modern',
            'show_home_delivery' => 'true',
        ), $atts, 'crcm_search_form');

        ob_start();
        include CRCM_PLUGIN_PATH . 'templates/search-form.php';
        return ob_get_clean();
    }

    /**
     * Vehicle list shortcode
     */
    public function vehicle_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'location' => '',
            'limit' => 12,
        ), $atts, 'crcm_vehicle_list');

        ob_start();
        include CRCM_PLUGIN_PATH . 'templates/vehicle-list.php';
        return ob_get_clean();
    }

    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'vehicle_id' => 0,
        ), $atts, 'crcm_booking_form');

        ob_start();
        include CRCM_PLUGIN_PATH . 'templates/booking-form.php';
        return ob_get_clean();
    }

    /**
     * Customer dashboard shortcode
     */
    public function customer_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your bookings.', 'custom-rental-manager') . '</p>';
        }

        ob_start();
        include CRCM_PLUGIN_PATH . 'templates/customer-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Dashboard page
     */
    public function dashboard_page() {
        include CRCM_PLUGIN_PATH . 'templates/admin/dashboard.php';
    }

    /**
     * Calendar page
     */
    public function calendar_page() {
        include CRCM_PLUGIN_PATH . 'templates/admin/calendar.php';
    }

    /**
     * Settings page
     */
    public function settings_page() {
        include CRCM_PLUGIN_PATH . 'templates/admin/settings.php';
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        $sanitized['company_name'] = sanitize_text_field($input['company_name'] ?? '');
        $sanitized['currency'] = sanitize_text_field($input['currency'] ?? 'EUR');
        $sanitized['currency_symbol'] = sanitize_text_field($input['currency_symbol'] ?? '€');
        $sanitized['free_cancellation_days'] = max(0, intval($input['free_cancellation_days'] ?? 3));
        $sanitized['late_return_extra_day'] = isset($input['late_return_extra_day']) ? true : false;
        $sanitized['late_return_time'] = sanitize_text_field($input['late_return_time'] ?? '10:00');
        $sanitized['stripe_publishable_key'] = sanitize_text_field($input['stripe_publishable_key'] ?? '');
        $sanitized['stripe_secret_key'] = sanitize_text_field($input['stripe_secret_key'] ?? '');
        $sanitized['email_from_name'] = sanitize_text_field($input['email_from_name'] ?? '');
        $sanitized['email_from_email'] = sanitize_email($input['email_from_email'] ?? '');
        $sanitized['show_totaliweb_credit'] = isset($input['show_totaliweb_credit']) ? true : false;

        // Sanitize locations array
        if (isset($input['locations']) && is_array($input['locations'])) {
            $sanitized['locations'] = array();
            foreach ($input['locations'] as $location) {
                $sanitized['locations'][] = array(
                    'name' => sanitize_text_field($location['name'] ?? ''),
                    'address' => sanitize_textarea_field($location['address'] ?? ''),
                );
            }
        }

        return $sanitized;
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create default settings
        if (!get_option('crcm_settings')) {
            update_option('crcm_settings', $this->settings);
        }

        // Initialize post types and taxonomies
        $this->init_post_types();
        $this->init_user_roles();

        // Create database tables
        $this->create_database_tables();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create custom database tables
     */
    private function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Availability table
        $table_name = $wpdb->prefix . 'crcm_availability';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vehicle_id bigint(20) NOT NULL,
            date date NOT NULL,
            available_quantity int(11) NOT NULL DEFAULT 0,
            price_override decimal(10,2) DEFAULT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY vehicle_id (vehicle_id),
            KEY date (date),
            UNIQUE KEY vehicle_date (vehicle_id, date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Price rules table
        $table_name = $wpdb->prefix . 'crcm_price_rules';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vehicle_id bigint(20) NOT NULL,
            rule_type varchar(50) NOT NULL,
            start_date date DEFAULT NULL,
            end_date date DEFAULT NULL,
            days_of_week varchar(20) DEFAULT NULL,
            price_modifier decimal(10,2) NOT NULL,
            modifier_type enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
            priority int(11) NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY vehicle_id (vehicle_id),
            KEY active (active)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Get plugin settings
     */
    public function get_settings() {
        return $this->settings;
    }

    /**
     * Get setting value
     */
    public function get_setting($key, $default = '') {
        return $this->settings[$key] ?? $default;
    }
}

// Initialize the plugin
function crcm() {
    return CustomRentalCarManager::instance();
}

// Global for backwards compatibility
$GLOBALS['custom_rental_car_manager'] = crcm();
