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
 * Main Plugin File - COMPLETE ECOSYSTEM WITH ALL EXISTING FUNCTIONALITY PRESERVED
 * 
 * Fixed all connection issues while preserving ALL existing components:
 * - Calendar Manager with full functionality
 * - Settings Manager with complete admin interface
 * - Dashboard with stats and management
 * - All existing managers and templates
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

/**
 * Main Plugin Class - COMPLETE ECOSYSTEM WITH ALL EXISTING FUNCTIONALITY
 */
class CRCM_Plugin {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Manager instances - ALL EXISTING MANAGERS PRESERVED
     */
    public $vehicle_manager;
    public $booking_manager;
    public $calendar_manager;
    public $email_manager;
    public $payment_manager;
    public $api_endpoints;
    public $customer_portal;
    public $settings_manager;
    public $dashboard_manager;
    public $availability_manager;
    public $pricing_manager;
    public $notification_manager;
    public $report_manager;
    
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
        // Critical: Add activation hook to create roles
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'), 5);
        add_action('init', array($this, 'load_textdomain'), 5);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Force role creation check on admin
        add_action('admin_init', array($this, 'check_and_create_roles'));
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
        add_action('admin_menu', array($this, 'admin_menu'));
        
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
     * Load plugin dependencies - ALL EXISTING COMPONENTS
     */
    private function load_dependencies() {
        // Load helper functions first
        if (file_exists(CRCM_PLUGIN_PATH . 'inc/functions.php')) {
            require_once CRCM_PLUGIN_PATH . 'inc/functions.php';
        }
        
        // Load ALL manager classes (preserving existing ones)
        $classes = array(
            'class-vehicle-manager.php',
            'class-booking-manager.php',
            'class-calendar-manager.php',
            'class-email-manager.php',
            'class-payment-manager.php',
            'class-api-endpoints.php',
            'class-customer-portal.php',
            'class-settings-manager.php',
            'class-dashboard-manager.php',
            'class-availability-manager.php',
            'class-pricing-manager.php',
            'class-notification-manager.php',
            'class-report-manager.php'
        );
        
        foreach ($classes as $class_file) {
            $file_path = CRCM_PLUGIN_PATH . 'inc/' . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialize ALL manager instances
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
        
        if (class_exists('CRCM_Settings_Manager')) {
            $this->settings_manager = new CRCM_Settings_Manager();
        }
        
        if (class_exists('CRCM_Dashboard_Manager')) {
            $this->dashboard_manager = new CRCM_Dashboard_Manager();
        }
        
        if (class_exists('CRCM_Availability_Manager')) {
            $this->availability_manager = new CRCM_Availability_Manager();
        }
        
        if (class_exists('CRCM_Pricing_Manager')) {
            $this->pricing_manager = new CRCM_Pricing_Manager();
        }
        
        if (class_exists('CRCM_Notification_Manager')) {
            $this->notification_manager = new CRCM_Notification_Manager();
        }
        
        if (class_exists('CRCM_Report_Manager')) {
            $this->report_manager = new CRCM_Report_Manager();
        }
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
            'has_archive' => false,
            'menu_icon' => 'dashicons-car',
            'supports' => array('title', 'thumbnail'),
            'show_in_rest' => false,
            'rewrite' => false,
            'show_in_menu' => false,
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
            'rewrite' => false,
            'show_in_menu' => false,
        ));
    }
    
    /**
     * Admin menu callback - COMPLETE EXISTING MENU STRUCTURE
     */
    public function admin_menu() {
        // Main menu page - Dashboard
        add_menu_page(
            __('Costabilerent', 'custom-rental-manager'),
            __('Costabilerent', 'custom-rental-manager'),
            'manage_options',
            'crcm-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-car',
            30
        );
        
        // Dashboard submenu (duplicate main for consistency)
        add_submenu_page(
            'crcm-dashboard',
            __('Dashboard', 'custom-rental-manager'),
            __('Dashboard', 'custom-rental-manager'),
            'manage_options',
            'crcm-dashboard',
            array($this, 'dashboard_page')
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
        
        // Customers submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Customers', 'custom-rental-manager'),
            __('Customers', 'custom-rental-manager'),
            'manage_options',
            'crcm-customers',
            array($this, 'customers_page')
        );
        
        // Reports submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Reports', 'custom-rental-manager'),
            __('Reports', 'custom-rental-manager'),
            'manage_options',
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
    }
    
    /**
     * Dashboard page - COMPLETE EXISTING FUNCTIONALITY
     */
    public function dashboard_page() {
        $template_path = CRCM_PLUGIN_PATH . 'templates/admin/dashboard.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback dashboard if template doesn't exist
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Costabilerent Dashboard', 'custom-rental-manager') . '</h1>';
            
            // Show basic stats
            echo '<div class="crcm-dashboard-stats">';
            echo '<h2>' . esc_html__('Quick Stats', 'custom-rental-manager') . '</h2>';
            
            $vehicle_count = wp_count_posts('crcm_vehicle');
            $booking_count = wp_count_posts('crcm_booking');
            
            echo '<div class="crcm-stats-grid">';
            echo '<div class="stat-card">';
            echo '<h3>' . sprintf(esc_html__('Vehicles: %d', 'custom-rental-manager'), $vehicle_count->publish ?? 0) . '</h3>';
            echo '<p>Active vehicles in fleet</p>';
            echo '</div>';
            
            echo '<div class="stat-card">';
            echo '<h3>' . sprintf(esc_html__('Bookings: %d', 'custom-rental-manager'), $booking_count->publish ?? 0) . '</h3>';
            echo '<p>Total bookings</p>';
            echo '</div>';
            echo '</div>';
            
            // Show role status for debugging
            echo '<h3>' . __('System Status', 'custom-rental-manager') . '</h3>';
            $customer_role = get_role('crcm_customer');
            $manager_role = get_role('crcm_manager');
            echo '<p><strong>Customer Role:</strong> ' . ($customer_role ? '✅ Active' : '❌ Missing') . '</p>';
            echo '<p><strong>Manager Role:</strong> ' . ($manager_role ? '✅ Active' : '❌ Missing') . '</p>';
            
            echo '</div>';
            echo '</div>';
        }
    }
    
    /**
     * Calendar page - COMPLETE EXISTING FUNCTIONALITY
     */
    public function calendar_page() {
        $template_path = CRCM_PLUGIN_PATH . 'templates/admin/calendar.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback calendar if template doesn't exist
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Rental Calendar', 'custom-rental-manager') . '</h1>';
            
            if ($this->calendar_manager && method_exists($this->calendar_manager, 'render_calendar')) {
                $this->calendar_manager->render_calendar();
            } else {
                echo '<div class="crcm-calendar-placeholder">';
                echo '<p>' . esc_html__('Calendar functionality will be loaded here.', 'custom-rental-manager') . '</p>';
                echo '<div id="crcm-calendar-container"></div>';
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Customers page
     */
    public function customers_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Customer Management', 'custom-rental-manager') . '</h1>';
        
        // Get customers with rental role
        $customers = get_users(array(
            'role' => 'crcm_customer',
            'orderby' => 'registered',
            'order' => 'DESC'
        ));
        
        echo '<div class="crcm-customers-list">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Name', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Email', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Phone', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Bookings', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Registered', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Actions', 'custom-rental-manager') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($customers as $customer) {
            $phone = get_user_meta($customer->ID, 'phone', true);
            $booking_count = get_user_meta($customer->ID, 'crcm_total_bookings', true) ?: 0;
            
            echo '<tr>';
            echo '<td><strong>' . esc_html($customer->display_name) . '</strong></td>';
            echo '<td>' . esc_html($customer->user_email) . '</td>';
            echo '<td>' . esc_html($phone ?: '-') . '</td>';
            echo '<td>' . esc_html($booking_count) . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($customer->user_registered)) . '</td>';
            echo '<td>';
            echo '<a href="' . get_edit_user_link($customer->ID) . '" class="button button-small">Edit</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Reports page
     */
    public function reports_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Reports & Analytics', 'custom-rental-manager') . '</h1>';
        
        if ($this->report_manager && method_exists($this->report_manager, 'render_reports')) {
            $this->report_manager->render_reports();
        } else {
            echo '<div class="crcm-reports-placeholder">';
            echo '<p>' . esc_html__('Reports functionality will be loaded here.', 'custom-rental-manager') . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Settings page - COMPLETE EXISTING FUNCTIONALITY
     */
    public function settings_page() {
        $template_path = CRCM_PLUGIN_PATH . 'templates/admin/settings.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback settings if template doesn't exist
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Costabilerent Settings', 'custom-rental-manager') . '</h1>';
            
            if ($this->settings_manager && method_exists($this->settings_manager, 'render_settings')) {
                $this->settings_manager->render_settings();
            } else {
                echo '<form method="post" action="options.php">';
                settings_fields('crcm_settings');
                do_settings_sections('crcm_settings');
                
                echo '<table class="form-table">';
                echo '<tr>';
                echo '<th><label for="company_name">Company Name</label></th>';
                echo '<td><input type="text" id="company_name" name="crcm_settings[company_name]" value="' . esc_attr(crcm_get_setting('company_name', 'Costabilerent')) . '" class="regular-text" /></td>';
                echo '</tr>';
                echo '<tr>';
                echo '<th><label for="company_phone">Phone</label></th>';
                echo '<td><input type="text" id="company_phone" name="crcm_settings[company_phone]" value="' . esc_attr(crcm_get_setting('company_phone', '+39 081 123 456')) . '" class="regular-text" /></td>';
                echo '</tr>';
                echo '<tr>';
                echo '<th><label for="company_email">Email</label></th>';
                echo '<td><input type="email" id="company_email" name="crcm_settings[company_email]" value="' . esc_attr(crcm_get_setting('company_email', 'info@costabilerent.com')) . '" class="regular-text" /></td>';
                echo '</tr>';
                echo '</table>';
                
                submit_button();
                echo '</form>';
            }
            
            echo '</div>';
        }
    }
    
    /**
     * Initialize shortcodes - ALL EXISTING SHORTCODES
     */
    private function init_shortcodes() {
        add_shortcode('crcm_search_form', array($this, 'search_form_shortcode'));
        add_shortcode('crcm_vehicle_list', array($this, 'vehicle_list_shortcode'));
        add_shortcode('crcm_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('crcm_customer_dashboard', array($this, 'customer_dashboard_shortcode'));
        add_shortcode('crcm_search', array($this, 'search_form_shortcode')); // Alias
        add_shortcode('crcm_list', array($this, 'vehicle_list_shortcode')); // Alias
        add_shortcode('crcm_booking', array($this, 'booking_form_shortcode')); // Alias
        add_shortcode('crcm_area', array($this, 'customer_dashboard_shortcode')); // Alias
    }
    
    /**
     * Search form shortcode
     */
    public function search_form_shortcode($atts) {
        $template_path = CRCM_PLUGIN_PATH . 'templates/frontend/search-form.php';
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '<div class="crcm-search-form-placeholder"><p>Search form will be loaded here</p></div>';
    }
    
    /**
     * Vehicle list shortcode
     */
    public function vehicle_list_shortcode($atts) {
        $template_path = CRCM_PLUGIN_PATH . 'templates/frontend/vehicle-list.php';
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '<div class="crcm-vehicle-list-placeholder"><p>Vehicle list will be loaded here</p></div>';
    }
    
    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        $template_path = CRCM_PLUGIN_PATH . 'templates/frontend/booking-form.php';
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '<div class="crcm-booking-form-placeholder"><p>Booking form will be loaded here</p></div>';
    }
    
    /**
     * Customer dashboard shortcode
     */
    public function customer_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to access your dashboard.', 'custom-rental-manager') . '</p>';
        }
        
        $template_path = CRCM_PLUGIN_PATH . 'templates/frontend/customer-dashboard.php';
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '<div class="crcm-customer-dashboard-placeholder"><p>Customer dashboard will be loaded here</p></div>';
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        $css_path = CRCM_PLUGIN_PATH . 'assets/css/frontend.css';
        $js_path = CRCM_PLUGIN_PATH . 'assets/js/frontend.js';
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'crcm-frontend',
                CRCM_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                CRCM_VERSION
            );
        }
        
        if (file_exists($js_path)) {
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
            ));
        }
    }
    
    /**
     * Enqueue admin assets - FIXED AJAX LOCALIZATION
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'crcm') === false && !in_array(get_current_screen()->post_type, array('crcm_vehicle', 'crcm_booking'))) {
            return;
        }
        
        $css_path = CRCM_PLUGIN_PATH . 'assets/css/admin.css';
        $js_path = CRCM_PLUGIN_PATH . 'assets/js/admin.js';
        
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'crcm-admin',
                CRCM_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                CRCM_VERSION
            );
        }
        
        // Always enqueue jQuery UI datepicker
        wp_enqueue_style(
            'jquery-ui-datepicker-style',
            'https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css',
            array(),
            '1.13.2'
        );
        
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'crcm-admin',
                CRCM_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-datepicker'),
                CRCM_VERSION,
                true
            );
            
            // CRITICAL: Proper AJAX localization - FIXED
            wp_localize_script('crcm-admin', 'crcm_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('crcm_admin_nonce'),
                'plugin_url' => CRCM_PLUGIN_URL,
                'locations' => array(
                    'ischia_porto' => array(
                        'name' => __('Ischia Porto', 'custom-rental-manager'),
                        'address' => 'Via Iasolino 94, Ischia'
                    ),
                    'forio' => array(
                        'name' => __('Forio', 'custom-rental-manager'),
                        'address' => 'Via Filippo di Lustro 19, Forio'
                    )
                )
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
     * Check and create roles if they don't exist
     */
    public function check_and_create_roles() {
        $customer_role = get_role('crcm_customer');
        $manager_role = get_role('crcm_manager');
        
        if (!$customer_role || !$manager_role) {
            // Force create roles
            if (function_exists('crcm_create_custom_user_roles')) {
                crcm_create_custom_user_roles();
            }
        }
    }
    
    /**
     * Plugin activation - PRESERVE ALL EXISTING FUNCTIONALITY
     */
    public function activate() {
        // Create default settings
        $this->create_default_settings();
        
        // Load functions.php to access role creation function
        if (file_exists(CRCM_PLUGIN_PATH . 'inc/functions.php')) {
            require_once CRCM_PLUGIN_PATH . 'inc/functions.php';
        }
        
        // Create user roles immediately on activation
        if (function_exists('crcm_create_custom_user_roles')) {
            crcm_create_custom_user_roles();
        }
        
        // Register post types before flushing
        $this->register_post_types();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('crcm_plugin_activated', true);
        update_option('crcm_activation_time', current_time('mysql'));
        
        // Create default pages if they don't exist
        $this->create_default_pages();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Create default settings - COMPLETE EXISTING SETTINGS
     */
    private function create_default_settings() {
        $default_settings = array(
            'company_name' => 'Costabilerent',
            'company_address' => 'Ischia, Italy',
            'company_phone' => '+39 081 123 456',
            'company_email' => 'info@costabilerent.com',
            'currency_symbol' => '€',
            'locations' => array(
                'ischia_porto' => array(
                    'name' => 'Ischia Porto',
                    'address' => 'Via Iasolino 94, Ischia',
                    'enabled' => true
                ),
                'forio' => array(
                    'name' => 'Forio',
                    'address' => 'Via Filippo di Lustro 19, Forio',
                    'enabled' => true
                )
            ),
            'booking_settings' => array(
                'require_deposit' => true,
                'deposit_percentage' => 20,
                'cancellation_hours' => 24,
                'late_return_penalty' => true
            ),
            'email_settings' => array(
                'send_confirmations' => true,
                'send_reminders' => true,
                'admin_notifications' => true
            ),
            'payment_settings' => array(
                'stripe_enabled' => false,
                'paypal_enabled' => false,
                'cash_enabled' => true
            )
        );
        
        $existing_settings = get_option('crcm_settings', array());
        $settings = wp_parse_args($existing_settings, $default_settings);
        update_option('crcm_settings', $settings);
    }
    
    /**
     * Create default pages for the plugin
     */
    private function create_default_pages() {
        $pages = array(
            'vehicle-search' => array(
                'title' => __('Vehicle Search', 'custom-rental-manager'),
                'content' => '[crcm_search_form]',
                'shortcode' => 'crcm_search_form'
            ),
            'vehicle-catalog' => array(
                'title' => __('Our Vehicles', 'custom-rental-manager'),
                'content' => '[crcm_vehicle_list]',
                'shortcode' => 'crcm_vehicle_list'
            ),
            'booking' => array(
                'title' => __('Book Now', 'custom-rental-manager'),
                'content' => '[crcm_booking_form]',
                'shortcode' => 'crcm_booking_form'
            ),
            'customer-area' => array(
                'title' => __('My Account', 'custom-rental-manager'),
                'content' => '[crcm_customer_dashboard]',
                'shortcode' => 'crcm_customer_dashboard'
            )
        );
        
        foreach ($pages as $slug => $page_data) {
            $existing_page = get_page_by_path($slug);
            
            if (!$existing_page) {
                wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ));
            }
        }
    }
}

/**
 * Initialize the plugin
 */
function crcm() {
    return CRCM_Plugin::get_instance();
}

// Initialize plugin
crcm();

/**
 * Manual role creation function for debugging
 */
if (isset($_GET['crcm_create_roles']) && current_user_can('manage_options')) {
    add_action('admin_init', function() {
        if (file_exists(CRCM_PLUGIN_PATH . 'inc/functions.php')) {
            require_once CRCM_PLUGIN_PATH . 'inc/functions.php';
            if (function_exists('crcm_create_custom_user_roles')) {
                crcm_create_custom_user_roles();
                wp_redirect(admin_url('plugins.php?crcm_roles_created=1'));
                exit;
            }
        }
    });
}

// Show success message for manual role creation
if (isset($_GET['crcm_roles_created'])) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Custom Rental Manager:</strong> User roles created successfully!</p>';
        echo '</div>';
    });
}
