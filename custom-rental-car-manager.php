<?php
/**
 * Plugin Name: Custom Rental Car Manager
 * Plugin URI: https://totaliweb.com/plugins/custom-rental-car-manager
 * Description: Complete rental car and scooter management system for WordPress. Perfect for rental businesses like Costabilerent in Ischia.
 * Version: 1.0.0
 * Author: Totaliweb
 * Author URI: https://totaliweb.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-rental-manager
 * Domain Path: /languages
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Requires PHP: 8.0
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CRCM_PLUGIN_FILE', __FILE__);
define('CRCM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CRCM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRCM_VERSION', '1.0.0');
define('CRCM_BRAND_URL', 'https://totaliweb.com');

/**
 * Main Plugin Class
 */
class CRCM_Plugin {

    /**
     * Single instance of the plugin
     */
    private static $instance = null;

    /**
     * Manager instances
     */
    public $vehicle_manager;
    public $booking_manager;
    public $calendar_manager;
    public $email_manager;
    public $payment_manager;
    public $api_endpoints;
    public $customer_portal;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
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
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        $this->load_dependencies();
        $this->init_managers();
        $this->register_post_types();
        $this->register_taxonomies();
        $this->add_admin_menu();
        $this->init_shortcodes();
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        $languages_path = dirname(plugin_basename(__FILE__)) . '/languages/';
        load_plugin_textdomain('custom-rental-manager', false, $languages_path);
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load helper functions first
        require_once CRCM_PLUGIN_PATH . 'inc/functions.php';

        // Load manager classes
        require_once CRCM_PLUGIN_PATH . 'inc/class-vehicle-manager.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-booking-manager.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-calendar-manager.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-email-manager.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-payment-manager.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-api-endpoints.php';
        require_once CRCM_PLUGIN_PATH . 'inc/class-customer-portal.php';
    }

    /**
     * Initialize manager instances - FIX: Actually instantiate classes
     */
    private function init_managers() {
        $this->vehicle_manager = new CRCM_Vehicle_Manager();
        $this->booking_manager = new CRCM_Booking_Manager();
        $this->calendar_manager = new CRCM_Calendar_Manager();
        $this->email_manager = new CRCM_Email_Manager();
        $this->payment_manager = new CRCM_Payment_Manager();
        $this->api_endpoints = new CRCM_API_Endpoints();
        $this->customer_portal = new CRCM_Customer_Portal();
    }

    /**
     * Register custom post types
     */
    private function register_post_types() {
        // Vehicle post type
        register_post_type('crcm_vehicle', array(
            'labels' => array(
                'name' => __('Vehicles', 'custom-rental-manager'),
                'singular_name' => __('Vehicle', 'custom-rental-manager'),
                'add_new' => __('Add New Vehicle', 'custom-rental-manager'),
                'add_new_item' => __('Add New Vehicle', 'custom-rental-manager'),
                'edit_item' => __('Edit Vehicle', 'custom-rental-manager'),
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-car',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest' => true,
        ));

        // Booking post type
        register_post_type('crcm_booking', array(
            'labels' => array(
                'name' => __('Bookings', 'custom-rental-manager'),
                'singular_name' => __('Booking', 'custom-rental-manager'),
                'add_new' => __('Add New Booking', 'custom-rental-manager'),
                'edit_item' => __('Edit Booking', 'custom-rental-manager'),
            ),
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title'),
            'capabilities' => array(
                'create_posts' => 'manage_options',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_private_posts' => 'manage_options',
            ),
        ));
    }

    /**
     * Register taxonomies
     */
    private function register_taxonomies() {
        // Vehicle type taxonomy
        register_taxonomy('crcm_vehicle_type', 'crcm_vehicle', array(
            'labels' => array(
                'name' => __('Vehicle Types', 'custom-rental-manager'),
                'singular_name' => __('Vehicle Type', 'custom-rental-manager'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_admin_column' => true,
        ));

        // Location taxonomy
        register_taxonomy('crcm_location', array('crcm_vehicle', 'crcm_booking'), array(
            'labels' => array(
                'name' => __('Locations', 'custom-rental-manager'),
                'singular_name' => __('Location', 'custom-rental-manager'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_admin_column' => true,
        ));
    }

    /**
     * Add admin menu
     */
    private function add_admin_menu() {
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    /**
     * Admin menu callback
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
            __('Calendar', 'custom-rental-manager'),
            __('Calendar', 'custom-rental-manager'),
            'manage_options',
            'crcm-calendar',
            array($this, 'calendar_page')
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
     * Dashboard page - FIX: Template existence check
     */
    public function dashboard_page() {
        $template_path = CRCM_PLUGIN_PATH . 'templates/admin/dashboard.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Rental Manager Dashboard', 'custom-rental-manager') . '</h1>';
            echo '<div class="notice notice-warning"><p>' . __('Dashboard template not found. Please ensure all plugin files are properly uploaded.', 'custom-rental-manager') . '</p></div>';
            echo '</div>';
        }
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
            echo '<h1>' . __('Rental Calendar', 'custom-rental-manager') . '</h1>';
            echo '<div class="notice notice-warning"><p>' . __('Calendar template not found.', 'custom-rental-manager') . '</p></div>';
            echo '</div>';
        }
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
            echo '<h1>' . __('Rental Settings', 'custom-rental-manager') . '</h1>';
            echo '<div class="notice notice-warning"><p>' . __('Settings template not found.', 'custom-rental-manager') . '</p></div>';
            echo '</div>';
        }
    }

    /**
     * Initialize shortcodes - FIX: Template existence checks
     */
    private function init_shortcodes() {
        add_shortcode('crcm_search_form', array($this, 'search_form_shortcode'));
        add_shortcode('crcm_vehicle_list', array($this, 'vehicle_list_shortcode'));
        add_shortcode('crcm_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('crcm_customer_dashboard', array($this, 'customer_dashboard_shortcode'));
    }

    /**
     * Search form shortcode
     */
    public function search_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default',
        ), $atts);

        ob_start();
        $template_path = CRCM_PLUGIN_PATH . 'templates/frontend/search-form.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="crcm-error">' . __('Search form template not found.', 'custom-rental-manager') . '</div>';
        }
        return ob_get_clean();
    }

    /**
     * Vehicle list shortcode
     */
    public function vehicle_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'limit' => 12,
        ), $atts);

        ob_start();
        $template_path = CRCM_PLUGIN_PATH . 'templates/frontend/vehicle-list.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="crcm-error">' . __('Vehicle list template not found.', 'custom-rental-manager') . '</div>';
        }
        return ob_get_clean();
    }

    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'vehicle_id' => '',
        ), $atts);

        ob_start();
        $template_path = CRCM_PLUGIN_PATH . 'templates/frontend/booking-form.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="crcm-error">' . __('Booking form template not found.', 'custom-rental-manager') . '</div>';
        }
        return ob_get_clean();
    }

    /**
     * Customer dashboard shortcode
     */
    public function customer_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="crcm-login-required">' . __('Please log in to access your dashboard.', 'custom-rental-manager') . '</div>';
        }

        ob_start();
        $template_path = CRCM_PLUGIN_PATH . 'templates/frontend/customer-dashboard.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="crcm-error">' . __('Customer dashboard template not found.', 'custom-rental-manager') . '</div>';
        }
        return ob_get_clean();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'crcm-frontend',
            CRCM_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            CRCM_VERSION
        );

        wp_enqueue_script(
            'crcm-frontend',
            CRCM_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            CRCM_VERSION,
            true
        );

        wp_localize_script('crcm-frontend', 'crcm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crcm_nonce'),
            'currency_symbol' => $this->get_setting('currency_symbol', '€'),
        ));
    }

    /**
     * Enqueue admin assets - FIX: Proper jQuery UI CSS
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'crcm') === false && !in_array(get_current_screen()->post_type, array('crcm_vehicle', 'crcm_booking'))) {
            return;
        }

        wp_enqueue_style(
            'crcm-admin',
            CRCM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CRCM_VERSION
        );

        // FIX: Proper jQuery UI datepicker CSS
        wp_enqueue_style(
            'jquery-ui-datepicker-style',
            'https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css',
            array(),
            '1.13.2'
        );

        wp_enqueue_script(
            'crcm-admin',
            CRCM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            CRCM_VERSION,
            true
        );

        wp_localize_script('crcm-admin', 'crcm_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crcm_admin_nonce'),
        ));
    }

    /**
     * Get plugin setting
     */
    public function get_setting($key, $default = '') {
        $settings = get_option('crcm_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_default_settings();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        wp_clear_scheduled_hook('crcm_daily_reminder_check');
        flush_rewrite_rules();
    }

    /**
     * Create default settings
     */
    private function create_default_settings() {
        $default_settings = array(
            'company_name' => 'Costabilerent',
            'company_address' => 'Ischia, Italy',
            'company_phone' => '+39 123 456 789',
            'company_email' => 'info@costabilerent.com',
            'currency_symbol' => '€',
            'show_totaliweb_credit' => true,
        );

        $existing_settings = get_option('crcm_settings', array());
        $settings = wp_parse_args($existing_settings, $default_settings);
        update_option('crcm_settings', $settings);
    }
}

/**
 * Initialize the plugin
 */
function crcm() {
    return CRCM_Plugin::get_instance();
}

// Initialize
crcm();
