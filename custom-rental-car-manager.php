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

/**
 * Plugin Main File - CLEANED & OPTIMIZED
 * 
 * Removed taxonomies completely and cleaned up admin menu.
 * Fixed post type registration to remove editor and taxonomies.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
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
        
        add_action('init', array($this, 'init'), 10);
        add_action('init', array($this, 'load_textdomain'), 5);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load dependencies first
        $this->load_dependencies();
        
        // Register post types (NO taxonomies)
        $this->register_post_types();
        
        // Initialize managers after WordPress objects are ready
        $this->init_managers();
        
        // Add admin menu
        $this->add_admin_menu();
        
        // Initialize shortcodes
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
        if (file_exists(CRCM_PLUGIN_PATH . 'inc/functions.php')) {
            require_once CRCM_PLUGIN_PATH . 'inc/functions.php';
        }
        
        // Load manager classes only if they exist
        $classes = array(
            'class-vehicle-manager.php',
            'class-booking-manager.php',
            'class-calendar-manager.php',
            'class-email-manager.php',
            'class-payment-manager.php',
            'class-api-endpoints.php',
            'class-customer-portal.php'
        );
        
        foreach ($classes as $class_file) {
            $file_path = CRCM_PLUGIN_PATH . 'inc/' . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialize manager instances
     */
    private function init_managers() {
        if (class_exists('CRCM_Vehicle_Manager')) {
            $this->vehicle_manager = new CRCM_Vehicle_Manager();
        }
        
        if (class_exists('CRCM_Booking_Manager')) {
            $this->booking_manager = new CRCM_Booking_Manager();
        }
        
        if (class_exists('CRCM_Calendar_Manager')) {
            $this->calendar_manager = new CRCM_Calendar_Manager();
        }
        
        if (class_exists('CRCM_Email_Manager')) {
            $this->email_manager = new CRCM_Email_Manager();
        }
        
        if (class_exists('CRCM_Payment_Manager')) {
            $this->payment_manager = new CRCM_Payment_Manager();
        }
        
        if (class_exists('CRCM_API_Endpoints')) {
            $this->api_endpoints = new CRCM_API_Endpoints();
        }
        
        if (class_exists('CRCM_Customer_Portal')) {
            $this->customer_portal = new CRCM_Customer_Portal();
        }
    }
    
    /**
     * Register custom post types - CLEANED: No taxonomies, no editor
     */
    private function register_post_types() {
        // Vehicle post type - CLEANED: No editor, no taxonomies
        register_post_type('crcm_vehicle', array(
            'labels' => array(
                'name' => __('Vehicles', 'custom-rental-manager'),
                'singular_name' => __('Vehicle', 'custom-rental-manager'),
                'add_new' => __('Add New Vehicle', 'custom-rental-manager'),
                'add_new_item' => __('Add New Vehicle', 'custom-rental-manager'),
                'edit_item' => __('Edit Vehicle', 'custom-rental-manager'),
            ),
            'public' => true,
            'has_archive' => false,
            'menu_icon' => 'dashicons-car',
            'supports' => array('title', 'thumbnail'), // REMOVED: editor, excerpt
            'show_in_rest' => false,
            'rewrite' => false,
            'show_in_menu' => false, // Will be under main menu
        ));
        
        // Booking post type - NO CHANGES
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
            'rewrite' => false,
            'show_in_menu' => false, // Will be under main menu
        ));
    }
    
    /**
     * Add admin menu - CLEANED: Removed taxonomy menus
     */
    private function add_admin_menu() {
        add_action('admin_menu', array($this, 'admin_menu'));
    }
    
    /**
     * Admin menu callback - CLEANED: No more taxonomy submenus
     */
    public function admin_menu() {
        // Main menu page
        add_menu_page(
            __('Rental Manager', 'custom-rental-manager'),
            __('Rental Manager', 'custom-rental-manager'),
            'manage_options',
            'crcm-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-car',
            30
        );
        
        // Vehicles submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Vehicles', 'custom-rental-manager'),
            __('Vehicles', 'custom-rental-manager'),
            'manage_options',
            'edit.php?post_type=crcm_vehicle'
        );
        
        // Add Vehicle submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Add Vehicle', 'custom-rental-manager'),
            __('Add Vehicle', 'custom-rental-manager'),
            'manage_options',
            'post-new.php?post_type=crcm_vehicle'
        );
        
        // Bookings submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Bookings', 'custom-rental-manager'),
            __('Bookings', 'custom-rental-manager'),
            'manage_options',
            'edit.php?post_type=crcm_booking'
        );
        
        // Add Booking submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Add Booking', 'custom-rental-manager'),
            __('Add Booking', 'custom-rental-manager'),
            'manage_options',
            'post-new.php?post_type=crcm_booking'
        );
        
        // Calendar submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Calendar', 'custom-rental-manager'),
            __('Calendar', 'custom-rental-manager'),
            'manage_options',
            'crcm-calendar',
            array($this, 'calendar_page')
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
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $template_path = CRCM_PLUGIN_PATH . 'templates/admin/dashboard.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Rental Manager Dashboard', 'custom-rental-manager') . '</h1>';
            echo '<p>' . esc_html__('Dashboard template not found. Please ensure all plugin files are properly uploaded.', 'custom-rental-manager') . '</p>';
            
            // Show basic stats even without template
            echo '<div class="crcm-basic-stats">';
            echo '<h2>' . esc_html__('Quick Stats', 'custom-rental-manager') . '</h2>';
            
            $vehicle_count = wp_count_posts('crcm_vehicle');
            $booking_count = wp_count_posts('crcm_booking');
            
            echo '<p>' . sprintf(esc_html__('Vehicles: %d', 'custom-rental-manager'), $vehicle_count->publish ?? 0) . '</p>';
            echo '<p>' . sprintf(esc_html__('Bookings: %d', 'custom-rental-manager'), $booking_count->publish ?? 0) . '</p>';
            echo '</div>';
            
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
            echo '<h1>' . esc_html__('Rental Calendar', 'custom-rental-manager') . '</h1>';
            echo '<p>' . esc_html__('Calendar template not found.', 'custom-rental-manager') . '</p>';
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
            echo '<h1>' . esc_html__('Rental Settings', 'custom-rental-manager') . '</h1>';
            echo '<p>' . esc_html__('Settings template not found.', 'custom-rental-manager') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Initialize shortcodes
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
            echo '<p>' . esc_html__('Search form template not found.', 'custom-rental-manager') . '</p>';
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
            echo '<p>' . esc_html__('Vehicle list template not found.', 'custom-rental-manager') . '</p>';
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
            echo '<p>' . esc_html__('Booking form template not found.', 'custom-rental-manager') . '</p>';
        }
        return ob_get_clean();
    }
    
    /**
     * Customer dashboard shortcode
     */
    public function customer_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to access your dashboard.', 'custom-rental-manager') . '</p>';
        }
        
        ob_start();
        $template_path = CRCM_PLUGIN_PATH . 'templates/frontend/customer-dashboard.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<p>' . esc_html__('Customer dashboard template not found.', 'custom-rental-manager') . '</p>';
        }
        return ob_get_clean();
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        $css_path = CRCM_PLUGIN_PATH . 'asset/css/frontend.css';
        $js_path = CRCM_PLUGIN_PATH . 'asset/js/frontend.js';
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'crcm-frontend',
                CRCM_PLUGIN_URL . 'asset/css/frontend.css',
                array(),
                CRCM_VERSION
            );
        }
        
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'crcm-frontend',
                CRCM_PLUGIN_URL . 'asset/js/frontend.js',
                array('jquery'),
                CRCM_VERSION,
                true
            );
            
            wp_localize_script('crcm-frontend', 'crcm_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('crcm_nonce'),
                'currency_symbol' => $this->get_setting('currency_symbol', '€'),
                'booking_page_url' => home_url('/booking/'),
            ));
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'crcm') === false && !in_array(get_current_screen()->post_type, array('crcm_vehicle', 'crcm_booking'))) {
            return;
        }
        
        $css_path = CRCM_PLUGIN_PATH . 'asset/css/admin.css';
        $js_path = CRCM_PLUGIN_PATH . 'asset/js/admin.js';
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'crcm-admin',
                CRCM_PLUGIN_URL . 'asset/css/admin.css',
                array(),
                CRCM_VERSION
            );
        }
        
        wp_enqueue_style(
            'jquery-ui-datepicker-style',
            'https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css',
            array(),
            '1.13.2'
        );
        
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'crcm-admin',
                CRCM_PLUGIN_URL . 'asset/js/admin.js',
                array('jquery', 'jquery-ui-datepicker'),
                CRCM_VERSION,
                true
            );
            
            wp_localize_script('crcm-admin', 'crcm_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('crcm_admin_nonce'),
            ));
        }
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
        
        // FLUSH REWRITE RULES: Delay to avoid early execution
        add_action('init', 'flush_rewrite_rules', 999);
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

// Initialize only after WordPress is loaded
add_action('plugins_loaded', 'crcm', 10);
