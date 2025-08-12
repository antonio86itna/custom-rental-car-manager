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
 * Plugin Main File - FIXED ACTIVATION & ROLE CREATION
 * 
 * Added proper activation hook to ensure user roles are created.
 * Fixed role creation timing and initialization.
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
define('CRCM_DB_VERSION', '1.0.0');
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
    public $locale_manager;

    /**
     * Get single instance.
     *
     * @since 1.0.0
     *
     * @return CRCM_Plugin Plugin instance.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 10);
        add_action('init', array($this, 'load_textdomain'), 5);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Initialize plugin.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function init() {
        $this->maybe_upgrade_db();

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
     * Load plugin textdomain.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function load_textdomain() {
        $languages_path = dirname(plugin_basename(__FILE__)) . '/languages/';
        load_plugin_textdomain('custom-rental-manager', false, $languages_path);
    }

    /**
     * Load plugin dependencies.
     *
     * @since 1.0.0
     *
     * @return void
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
            'class-customer-portal.php',
            'class-locale-manager.php'
        );
        
        foreach ($classes as $class_file) {
            $file_path = CRCM_PLUGIN_PATH . 'inc/' . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialize manager instances.
     *
     * @since 1.0.0
     *
     * @return void
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
        
        if ( class_exists( 'CRCM_API_Endpoints' ) && null === $this->api_endpoints ) {
            $this->api_endpoints = new CRCM_API_Endpoints();
        }
        
        if (class_exists('CRCM_Customer_Portal')) {
            $this->customer_portal = new CRCM_Customer_Portal();
        }

        if (class_exists('CRCM_Locale_Manager')) {
            $this->locale_manager = new CRCM_Locale_Manager();
        }
    }
    
    /**
     * Register custom post types - CLEANED: No taxonomies, no editor.
     *
     * @since 1.0.0
     *
     * @return void
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
        
        // Booking post type - with granular capabilities
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
            'map_meta_cap' => true,
            'capabilities' => array(
                // Singular capabilities
                'edit_post'              => 'crcm_edit_booking',
                'read_post'              => 'crcm_read_booking',
                'delete_post'            => 'crcm_delete_booking',

                // Plural capabilities
                'edit_posts'             => 'crcm_edit_bookings',
                'edit_others_posts'      => 'crcm_edit_others_bookings',
                'publish_posts'          => 'crcm_publish_bookings',
                'read_private_posts'     => 'crcm_read_private_bookings',
                'delete_posts'           => 'crcm_delete_bookings',
                'delete_private_posts'   => 'crcm_delete_private_bookings',
                'delete_published_posts' => 'crcm_delete_published_bookings',
                'delete_others_posts'    => 'crcm_delete_others_bookings',
                'edit_private_posts'     => 'crcm_edit_private_bookings',
                'edit_published_posts'   => 'crcm_edit_published_bookings',
                'create_posts'           => 'crcm_manage_bookings',
            ),
            'rewrite' => false,
            'show_in_menu' => false, // Will be under main menu
        ));
    }
    
    /**
     * Add admin menu - CLEANED: Removed taxonomy menus.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function add_admin_menu() {
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    /**
     * Admin menu callback - CLEANED: No more taxonomy submenus.
     *
     * @since 1.0.0
     *
     * @return void
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
     * Dashboard page.
     *
     * @since 1.0.0
     *
     * @return void
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
            
            // Show role status for debugging
            echo '<h3>' . esc_html__( 'Role Status (Debug)', 'custom-rental-manager' ) . '</h3>';
            $customer_role = get_role( 'crcm_customer' );
            $manager_role  = get_role( 'crcm_manager' );
            echo '<p><strong>' . esc_html__( 'Customer Role:', 'custom-rental-manager' ) . '</strong> ' . ( $customer_role ? esc_html__( '✅ Created', 'custom-rental-manager' ) : esc_html__( '❌ Missing', 'custom-rental-manager' ) ) . '</p>';
            echo '<p><strong>' . esc_html__( 'Manager Role:', 'custom-rental-manager' ) . '</strong> ' . ( $manager_role ? esc_html__( '✅ Created', 'custom-rental-manager' ) : esc_html__( '❌ Missing', 'custom-rental-manager' ) ) . '</p>';
            
            echo '</div>';
            echo '</div>';
        }
    }
    
    /**
     * Calendar page.
     *
     * @since 1.0.0
     *
     * @return void
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
     * Settings page.
     *
     * @since 1.0.0
     *
     * @return void
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
     * Initialize shortcodes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function init_shortcodes() {
        add_shortcode('crcm_search_form', array($this, 'search_form_shortcode'));
        add_shortcode('crcm_vehicle_list', array($this, 'vehicle_list_shortcode'));
        add_shortcode('crcm_booking_form', array($this, 'booking_form_shortcode'));
        add_shortcode('crcm_customer_dashboard', array($this, 'customer_dashboard_shortcode'));
    }
    
    /**
     * Search form shortcode.
     *
     * @since 1.0.0
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function search_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'default',
        ), $atts);

        wp_enqueue_style(
            'crcm-search-form',
            CRCM_PLUGIN_URL . 'assets/css/frontend-search-form.css',
            array(),
            CRCM_VERSION
        );

        wp_enqueue_script(
            'crcm-search-form',
            CRCM_PLUGIN_URL . 'assets/js/frontend-search-form.js',
            array('jquery'),
            CRCM_VERSION,
            true
        );

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
     * Vehicle list shortcode.
     *
     * @since 1.0.0
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function vehicle_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'limit' => 12,
        ), $atts);

        wp_enqueue_style(
            'crcm-vehicle-list',
            CRCM_PLUGIN_URL . 'assets/css/frontend-vehicle-list.css',
            array(),
            CRCM_VERSION
        );

        wp_enqueue_script(
            'crcm-vehicle-list',
            CRCM_PLUGIN_URL . 'assets/js/frontend-vehicle-list.js',
            array('jquery'),
            CRCM_VERSION,
            true
        );

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
     * Booking form shortcode.
     *
     * @since 1.0.0
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'vehicle_id' => '',
        ), $atts);

        $vehicle_id = ! empty( sanitize_text_field( $_GET['vehicle'] ?? '' ) )
            ? intval( sanitize_text_field( $_GET['vehicle'] ) )
            : intval( $atts['vehicle_id'] );

        $pickup_date = ! empty( sanitize_text_field( $_GET['pickup_date'] ?? '' ) )
            ? sanitize_text_field( $_GET['pickup_date'] )
            : '';

        $return_date = ! empty( sanitize_text_field( $_GET['return_date'] ?? '' ) )
            ? sanitize_text_field( $_GET['return_date'] )
            : '';
        $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
        $daily_rate   = $pricing_data['daily_rate'] ?? 0;
        $rental_days  = 1;

        if ($pickup_date && $return_date) {
            try {
                $pickup = new DateTime($pickup_date);
                $return = new DateTime($return_date);
                $rental_days = max(1, $return->diff($pickup)->days);
            } catch (Exception $e) {
                $rental_days = 1;
            }
        }

        $end_date = new DateTime($pickup_date ?: date('Y-m-d'));
        $end_date->add(new DateInterval('P' . $rental_days . 'D'));
        $base_total_calc  = crcm_calculate_vehicle_pricing($vehicle_id, $pickup_date, $end_date->format('Y-m-d'));
        $extra_daily_rate = $rental_days > 0 ? max(0, ($base_total_calc - ($daily_rate * $rental_days)) / $rental_days) : 0;

        $currency_symbol = crcm_get_setting('currency_symbol', '€');

        wp_enqueue_style(
            'crcm-booking-form',
            CRCM_PLUGIN_URL . 'assets/css/frontend-booking-form.css',
            array(),
            CRCM_VERSION
        );

        wp_enqueue_script(
            'crcm-booking-form',
            CRCM_PLUGIN_URL . 'assets/js/frontend-booking-form.js',
            array('jquery'),
            CRCM_VERSION,
            true
        );

        wp_localize_script(
            'crcm-booking-form',
            'crcmBookingData',
            array(
                'daily_rate'      => $daily_rate,
                'extra_daily_rate'=> $extra_daily_rate,
                'rental_days'     => $rental_days,
                'currency_symbol' => $currency_symbol,
                'days_label'      => __('giorni', 'custom-rental-manager'),
                'free_label'      => __('Gratis', 'custom-rental-manager'),
            )
        );

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
     * Customer dashboard shortcode.
     *
     * @since 1.0.0
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output.
     */
    public function customer_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to access your dashboard.', 'custom-rental-manager') . '</p>';
        }

        if (!crcm_user_is_customer()) {
            return '<p>' . esc_html__('Access restricted to rental customers.', 'custom-rental-manager') . '</p>';
        }

        wp_enqueue_style(
            'crcm-customer-dashboard',
            CRCM_PLUGIN_URL . 'assets/css/frontend-customer-dashboard.css',
            array(),
            CRCM_VERSION
        );

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
     * Enqueue frontend assets.
     *
     * @since 1.0.0
     *
     * @return void
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
                'currency_symbol' => $this->get_setting('currency_symbol', '€'),
                'booking_page_url' => home_url('/booking/'),
            ));
        }
    }
    
    /**
     * Enqueue admin assets.
     *
     * @since 1.0.0
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        $screen = get_current_screen();

        if ( strpos( $hook, 'crcm' ) === false && ( ! $screen || ! in_array( $screen->post_type, array( 'crcm_vehicle', 'crcm_booking' ), true ) ) ) {
            return;
        }

        $css_path = CRCM_PLUGIN_PATH . 'assets/css/admin.css';
        $js_path  = CRCM_PLUGIN_PATH . 'assets/js/admin.js';

        if ( file_exists( $css_path ) ) {
            wp_enqueue_style(
                'crcm-admin',
                CRCM_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                CRCM_VERSION
            );
        }

        if ( file_exists( $js_path ) ) {
            wp_enqueue_script(
                'crcm-admin',
                CRCM_PLUGIN_URL . 'assets/js/admin.js',
                array( 'jquery' ),
                CRCM_VERSION,
                true
            );

            wp_localize_script(
                'crcm-admin',
                'crcm_admin',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'crcm_admin_nonce' ),
                )
            );
        }

        if ( $screen && 'crcm_vehicle' === $screen->post_type ) {
            $vehicle_css = CRCM_PLUGIN_PATH . 'assets/css/admin-vehicle-meta.css';
            $vehicle_js  = CRCM_PLUGIN_PATH . 'assets/js/admin-vehicle-meta.js';

            if ( file_exists( $vehicle_css ) ) {
                wp_enqueue_style(
                    'crcm-admin-vehicle',
                    CRCM_PLUGIN_URL . 'assets/css/admin-vehicle-meta.css',
                    array(),
                    CRCM_VERSION
                );
            }

            if ( file_exists( $vehicle_js ) ) {
                wp_enqueue_script(
                    'crcm-admin-vehicle',
                    CRCM_PLUGIN_URL . 'assets/js/admin-vehicle-meta.js',
                    array( 'jquery', 'crcm-admin' ),
                    CRCM_VERSION,
                    true
                );

                wp_localize_script(
                    'crcm-admin-vehicle',
                    'crcm_vehicle_meta',
                    array(
                        'min_greater_max' => __( 'I giorni minimi non possono essere maggiori di quelli massimi', 'custom-rental-manager' ),
                    )
                );
            }
        }

        if ( $screen && 'crcm_booking' === $screen->post_type ) {
            $booking_css = CRCM_PLUGIN_PATH . 'assets/css/admin-booking.css';
            $booking_js  = CRCM_PLUGIN_PATH . 'assets/js/admin-booking.js';

            if ( file_exists( $booking_css ) ) {
                wp_enqueue_style(
                    'crcm-admin-booking',
                    CRCM_PLUGIN_URL . 'assets/css/admin-booking.css',
                    array(),
                    CRCM_VERSION
                );
            }

            if ( file_exists( $booking_js ) ) {
                wp_enqueue_script(
                    'crcm-admin-booking',
                    CRCM_PLUGIN_URL . 'assets/js/admin-booking.js',
                    array( 'jquery', 'crcm-admin' ),
                    CRCM_VERSION,
                    true
                );

                wp_localize_script(
                    'crcm-admin-booking',
                    'crcm_booking',
                    array(
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'nonce'    => wp_create_nonce( 'crcm_admin_nonce' ),
                        'i18n'     => array(
                            'rental_days'        => __( 'Rental days:', 'custom-rental-manager' ),
                            'searching'          => __( 'Searching...', 'custom-rental-manager' ),
                            'no_results'         => __( 'No customers found', 'custom-rental-manager' ),
                            'search_error'       => __( 'Search error', 'custom-rental-manager' ),
                            'select'             => __( 'Select', 'custom-rental-manager' ),
                            'change_customer'    => __( 'Change Customer', 'custom-rental-manager' ),
                            'email_label'        => __( 'Email:', 'custom-rental-manager' ),
                            'role_label'         => __( 'Role:', 'custom-rental-manager' ),
                            'phone_label'        => __( 'Phone:', 'custom-rental-manager' ),
                            'rental_customer_role' => __( 'Rental Customer', 'custom-rental-manager' ),
                            'loading_vehicle'    => __( 'Loading vehicle details...', 'custom-rental-manager' ),
                            'checking_availability' => __( 'Checking availability...', 'custom-rental-manager' ),
                            'select_dates'       => __( 'Select dates to check availability', 'custom-rental-manager' ),
                            'error_loading_details' => __( 'Error loading details', 'custom-rental-manager' ),
                            'connection_error'   => __( 'Connection error', 'custom-rental-manager' ),
                            'availability_error' => __( 'Error checking availability', 'custom-rental-manager' ),
                            'available'          => __( '✅ Available', 'custom-rental-manager' ),
                            'not_available'      => __( '❌ Not Available', 'custom-rental-manager' ),
                            'units_available'    => __( '%1$s units available of %2$s total', 'custom-rental-manager' ),
                            'no_units_available' => __( 'No units available for selected dates', 'custom-rental-manager' ),
                            'included'           => __( 'Included', 'custom-rental-manager' ),
                            'basic_insurance'    => __( 'Basic Insurance', 'custom-rental-manager' ),
                            'premium_insurance'  => __( 'Premium Insurance', 'custom-rental-manager' ),
                            'base_rate'          => __( 'Base rate', 'custom-rental-manager' ),
                            'late_return_fee'    => __( 'Late return fee', 'custom-rental-manager' ),
                            'per_day'            => __( '/day', 'custom-rental-manager' ),
                        ),
                    )
                );
            }
        }

        $page_param   = sanitize_text_field( $_GET['page'] ?? '' );
        $current_page = ! empty( $page_param ) ? $page_param : '';

        if ( ! empty( $current_page ) && 'crcm-dashboard' === $current_page ) {
            $dashboard_css = CRCM_PLUGIN_PATH . 'assets/css/admin-dashboard.css';
            if ( file_exists( $dashboard_css ) ) {
                wp_enqueue_style(
                    'crcm-admin-dashboard',
                    CRCM_PLUGIN_URL . 'assets/css/admin-dashboard.css',
                    array(),
                    CRCM_VERSION
                );
            }
        }

        if ( ! empty( $current_page ) && 'crcm-calendar' === $current_page ) {
            $calendar_css = CRCM_PLUGIN_PATH . 'assets/css/admin-calendar.css';
            if ( file_exists( $calendar_css ) ) {
                wp_enqueue_style(
                    'crcm-admin-calendar',
                    CRCM_PLUGIN_URL . 'assets/css/admin-calendar.css',
                    array(),
                    CRCM_VERSION
                );
            }
        }

        if ( ! empty( $current_page ) && 'crcm-settings' === $current_page ) {
            $settings_css = CRCM_PLUGIN_PATH . 'assets/css/admin-settings.css';
            $settings_js  = CRCM_PLUGIN_PATH . 'assets/js/admin-settings.js';

            if ( file_exists( $settings_css ) ) {
                wp_enqueue_style(
                    'crcm-admin-settings',
                    CRCM_PLUGIN_URL . 'assets/css/admin-settings.css',
                    array(),
                    CRCM_VERSION
                );
            }

            if ( file_exists( $settings_js ) ) {
                wp_enqueue_script(
                    'crcm-admin-settings',
                    CRCM_PLUGIN_URL . 'assets/js/admin-settings.js',
                    array( 'jquery' ),
                    CRCM_VERSION,
                    true
                );
            }
        }
    }
    
    /**
     * Get plugin setting.
     *
     * @since 1.0.0
     *
     * @param string $key     Setting key.
     * @param mixed  $default Default value if not set.
     * @return mixed Setting value.
     */
    public function get_setting($key, $default = '') {
        $settings = get_option('crcm_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Plugin activation - FIXED: Now properly creates roles.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function activate() {
        $plugin = self::get_instance();

        // Create default settings
        $plugin->create_default_settings();
        
        // Load functions.php to access role creation function
        if (file_exists(CRCM_PLUGIN_PATH . 'inc/functions.php')) {
            require_once CRCM_PLUGIN_PATH . 'inc/functions.php';
        }
        
        // Create user roles immediately on activation
        if (function_exists('crcm_create_custom_user_roles')) {
            crcm_create_custom_user_roles();
        }
        
        // Register post types before flushing
        $plugin->register_post_types();

        // Create or update required database tables
        $plugin->maybe_upgrade_db();

        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('crcm_plugin_activated', true);
        update_option('crcm_activation_time', current_time('mysql'));
    }
    
    /**
     * Plugin deactivation.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('crcm_daily_reminder_check');
        wp_clear_scheduled_hook('crcm_daily_status_check');
        flush_rewrite_rules();

        // Optionally remove roles on deactivation (uncomment if needed)
        // remove_role('crcm_customer');
        // remove_role('crcm_manager');
    }

    /**
     * Create or update database tables.
     *
     * Uses dbDelta to create the availability table and stores the schema
     * version for future upgrades.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function create_availability_table() {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'crcm_availability';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            vehicle_id bigint(20) unsigned NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            status tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY vehicle_id (vehicle_id),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('crcm_db_version', CRCM_DB_VERSION);
    }

    /**
     * Check database version and run upgrades when necessary.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function maybe_upgrade_db() {
        $installed_version = get_option('crcm_db_version');
        if (CRCM_DB_VERSION !== $installed_version) {
            $this->create_availability_table();
        }
    }

    /**
     * Register plugin settings using the WordPress Settings API.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            'crcm_settings_group',
            'crcm_settings',
            array(
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
            )
        );

        add_settings_section( 'crcm_company_section', '', '__return_false', 'crcm-settings' );
        add_settings_field(
            'company_name',
            __( 'Company Name', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_company_section',
            array( 'label_for' => 'company_name', 'type' => 'text' )
        );
        add_settings_field(
            'company_address',
            __( 'Address', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_company_section',
            array( 'label_for' => 'company_address', 'type' => 'textarea', 'rows' => 3 )
        );
        add_settings_field(
            'company_phone',
            __( 'Phone', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_company_section',
            array( 'label_for' => 'company_phone', 'type' => 'text' )
        );
        add_settings_field(
            'company_email',
            __( 'Email', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_company_section',
            array( 'label_for' => 'company_email', 'type' => 'email' )
        );
        add_settings_field(
            'currency_symbol',
            __( 'Currency Symbol', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_company_section',
            array( 'label_for' => 'currency_symbol', 'type' => 'text', 'class' => 'small-text' )
        );
        add_settings_field(
            'currency_position',
            __( 'Currency Position', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_company_section',
            array(
                'label_for' => 'currency_position',
                'type'      => 'select',
                'options'   => array(
                    'before' => __( 'Before amount', 'custom-rental-manager' ),
                    'after'  => __( 'After amount', 'custom-rental-manager' ),
                ),
            )
        );

        add_settings_section( 'crcm_booking_section', '', '__return_false', 'crcm-settings' );
        add_settings_field(
            'booking_advance_days',
            __( 'Booking Advance Days', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_booking_section',
            array( 'label_for' => 'booking_advance_days', 'type' => 'number' )
        );
        add_settings_field(
            'min_booking_hours',
            __( 'Minimum Booking Hours', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_booking_section',
            array( 'label_for' => 'min_booking_hours', 'type' => 'number' )
        );
        add_settings_field(
            'cancellation_hours',
            __( 'Free Cancellation Hours', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_booking_section',
            array( 'label_for' => 'cancellation_hours', 'type' => 'number' )
        );
        add_settings_field(
            'late_return_fee',
            __( 'Late Return Fee', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_booking_section',
            array( 'label_for' => 'late_return_fee', 'type' => 'number' )
        );

        add_settings_section( 'crcm_payment_section', '', '__return_false', 'crcm-settings' );
        add_settings_field(
            'enable_online_payment',
            __( 'Enable Online Payment', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_payment_section',
            array( 'label_for' => 'enable_online_payment', 'type' => 'checkbox' )
        );
        add_settings_field(
            'deposit_percentage',
            __( 'Deposit Percentage', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_payment_section',
            array( 'label_for' => 'deposit_percentage', 'type' => 'number' )
        );
        add_settings_field(
            'minimum_deposit',
            __( 'Minimum Deposit', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_payment_section',
            array( 'label_for' => 'minimum_deposit', 'type' => 'number' )
        );

        add_settings_section( 'crcm_email_section', '', '__return_false', 'crcm-settings' );
        add_settings_field(
            'email_from_name',
            __( 'From Name', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_email_section',
            array( 'label_for' => 'email_from_name', 'type' => 'text' )
        );
        add_settings_field(
            'email_from_email',
            __( 'From Email', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_email_section',
            array( 'label_for' => 'email_from_email', 'type' => 'email' )
        );
        add_settings_field(
            'enable_booking_confirmation',
            __( 'Send Booking Confirmation', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_email_section',
            array( 'label_for' => 'enable_booking_confirmation', 'type' => 'checkbox' )
        );
        add_settings_field(
            'enable_pickup_reminder',
            __( 'Send Pickup Reminder', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_email_section',
            array( 'label_for' => 'enable_pickup_reminder', 'type' => 'checkbox' )
        );
        add_settings_field(
            'enable_admin_notifications',
            __( 'Admin Notifications', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_email_section',
            array( 'label_for' => 'enable_admin_notifications', 'type' => 'checkbox' )
        );

        add_settings_field(
            'booking_confirmation_template',
            __( 'Booking Confirmation Template', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_email_section',
            array( 'label_for' => 'booking_confirmation_template', 'type' => 'text', 'description' => __( 'Template slug for booking confirmations.', 'custom-rental-manager' ) )
        );
        add_settings_field(
            'status_change_template',
            __( 'Status Change Template', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_email_section',
            array( 'label_for' => 'status_change_template', 'type' => 'text', 'description' => __( 'Template slug for status change emails.', 'custom-rental-manager' ) )
        );

        add_settings_section( 'crcm_delivery_section', '', '__return_false', 'crcm-settings' );
        add_settings_field(
            'enable_home_delivery',
            __( 'Enable Home Delivery', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_delivery_section',
            array( 'label_for' => 'enable_home_delivery', 'type' => 'checkbox' )
        );
        add_settings_field(
            'home_delivery_fee',
            __( 'Home Delivery Fee', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_delivery_section',
            array( 'label_for' => 'home_delivery_fee', 'type' => 'number' )
        );
        add_settings_field(
            'home_delivery_radius',
            __( 'Home Delivery Radius', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_delivery_section',
            array( 'label_for' => 'home_delivery_radius', 'type' => 'number' )
        );

        add_settings_section( 'crcm_advanced_section', '', '__return_false', 'crcm-settings' );
        add_settings_field(
            'show_totaliweb_credit',
            __( 'Show "Powered by Totaliweb" credit', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_advanced_section',
            array( 'label_for' => 'show_totaliweb_credit', 'type' => 'checkbox' )
        );
        add_settings_field(
            'custom_css',
            __( 'Custom CSS', 'custom-rental-manager' ),
            array( $this, 'render_field' ),
            'crcm-settings',
            'crcm_advanced_section',
            array( 'label_for' => 'custom_css', 'type' => 'textarea', 'rows' => 10 )
        );
    }

    /**
     * Sanitize settings input.
     *
     * @since 1.0.0
     *
     * @param array $input Raw settings.
     * @return array Sanitized settings.
     */
    public function sanitize_settings( $input ) {
        $defaults = $this->get_default_settings();
        $input    = wp_parse_args( $input, $defaults );

        $output = array();

        $output['company_name']              = sanitize_text_field( $input['company_name'] );
        $output['company_address']           = sanitize_textarea_field( $input['company_address'] );
        $output['company_phone']             = sanitize_text_field( $input['company_phone'] );
        $output['company_email']             = sanitize_email( $input['company_email'] );
        $output['company_website']           = esc_url_raw( $input['company_website'] );
        $output['currency_symbol']           = sanitize_text_field( $input['currency_symbol'] );
        $output['currency_position']         = ( 'after' === $input['currency_position'] ) ? 'after' : 'before';
        $output['default_tax_rate']          = floatval( $input['default_tax_rate'] );
        $output['booking_advance_days']      = intval( $input['booking_advance_days'] );
        $output['min_booking_hours']         = intval( $input['min_booking_hours'] );
        $output['cancellation_hours']        = intval( $input['cancellation_hours'] );
        $output['late_return_fee']           = floatval( $input['late_return_fee'] );
        $output['email_from_name']           = sanitize_text_field( $input['email_from_name'] );
        $output['email_from_email']          = sanitize_email( $input['email_from_email'] );
        $output['enable_booking_confirmation'] = isset( $input['enable_booking_confirmation'] ) ? 1 : 0;
        $output['enable_pickup_reminder']    = isset( $input['enable_pickup_reminder'] ) ? 1 : 0;
        $output['enable_admin_notifications'] = isset( $input['enable_admin_notifications'] ) ? 1 : 0;
        $output['booking_confirmation_template'] = sanitize_text_field( $input['booking_confirmation_template'] );
        $output['status_change_template'] = sanitize_text_field( $input['status_change_template'] );
        $output['enable_home_delivery']      = isset( $input['enable_home_delivery'] ) ? 1 : 0;
        $output['home_delivery_fee']         = floatval( $input['home_delivery_fee'] );
        $output['home_delivery_radius']      = intval( $input['home_delivery_radius'] );
        $output['enable_online_payment']     = isset( $input['enable_online_payment'] ) ? 1 : 0;
        $output['deposit_percentage']        = intval( $input['deposit_percentage'] );
        $output['minimum_deposit']           = floatval( $input['minimum_deposit'] );
        $output['show_totaliweb_credit']     = isset( $input['show_totaliweb_credit'] ) ? 1 : 0;
        $output['custom_css']                = wp_kses_post( $input['custom_css'] );

        return $output;
    }

    /**
     * Render a settings field.
     *
     * @since 1.0.0
     *
     * @param array $args Field arguments.
     * @return void
     */
    public function render_field( $args ) {
        $options = get_option( 'crcm_settings', $this->get_default_settings() );
        $id      = $args['label_for'];
        $type    = $args['type'];
        $value   = isset( $options[ $id ] ) ? $options[ $id ] : '';

        switch ( $type ) {
            case 'textarea':
                printf(
                    '<textarea id="%1$s" name="crcm_settings[%1$s]" rows="%2$d" class="large-text">%3$s</textarea>',
                    esc_attr( $id ),
                    isset( $args['rows'] ) ? intval( $args['rows'] ) : 5,
                    esc_textarea( $value )
                );
                break;
            case 'checkbox':
                printf(
                    '<input type="checkbox" id="%1$s" name="crcm_settings[%1$s]" value="1" %2$s />',
                    esc_attr( $id ),
                    checked( 1, $value, false )
                );
                break;
            case 'select':
                echo '<select id="' . esc_attr( $id ) . '" name="crcm_settings[' . esc_attr( $id ) . ']">';
                foreach ( $args['options'] as $key => $label ) {
                    printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $value, $key, false ), esc_html( $label ) );
                }
                echo '</select>';
                break;
            default:
                $class = isset( $args['class'] ) ? $args['class'] : 'regular-text';
                printf(
                    '<input type="%1$s" id="%2$s" name="crcm_settings[%2$s]" value="%3$s" class="%4$s" />',
                    esc_attr( $type ),
                    esc_attr( $id ),
                    esc_attr( $value ),
                    esc_attr( $class )
                );
        }

        if ( ! empty( $args['description'] ) ) {
            echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
        }
    }

    /**
     * Default settings values.
     *
     * @since 1.0.0
     *
     * @return array Default settings.
     */
    private function get_default_settings() {
        return array(
            'company_name'              => 'Costabilerent',
            'company_address'           => 'Ischia, Italy',
            'company_phone'             => '+39 123 456 789',
            'company_email'             => 'info@costabilerent.com',
            'company_website'           => 'https://costabilerent.com',
            'currency_symbol'           => '€',
            'currency_position'         => 'before',
            'default_tax_rate'          => 22,
            'booking_advance_days'      => 365,
            'min_booking_hours'         => 24,
            'cancellation_hours'        => 72,
            'late_return_fee'           => 25,
            'email_from_name'           => 'Costabilerent',
            'email_from_email'          => 'info@costabilerent.com',
            'enable_booking_confirmation' => 1,
            'enable_pickup_reminder'    => 1,
            'enable_admin_notifications' => 1,
            'booking_confirmation_template' => 'booking-confirmation',
            'status_change_template'   => 'status-change',
            'enable_home_delivery'      => 1,
            'home_delivery_fee'         => 25,
            'home_delivery_radius'      => 20,
            'enable_online_payment'     => 0,
            'deposit_percentage'        => 30,
            'minimum_deposit'           => 200,
            'show_totaliweb_credit'     => 1,
            'custom_css'                => '',
        );
    }
    /**
     * Create default settings.
     *
     * @since 1.0.0
     *
     * @return void
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

register_activation_hook(CRCM_PLUGIN_FILE, array('CRCM_Plugin', 'activate'));
register_deactivation_hook(CRCM_PLUGIN_FILE, array('CRCM_Plugin', 'deactivate'));

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 *
 * @return CRCM_Plugin Plugin instance.
 */
function crcm() {
    return CRCM_Plugin::get_instance();
}

// Initialize only after WordPress is loaded
add_action('plugins_loaded', 'crcm', 10);

/**
 * MANUAL ROLE CREATION FUNCTION - For immediate testing
 * Add this to wp-admin/plugins.php?crcm_create_roles=1 for manual trigger
 * Requires CRCM_ALLOW_ROLE_REPAIR constant set to true.
 */
if (
    defined('CRCM_ALLOW_ROLE_REPAIR') &&
    CRCM_ALLOW_ROLE_REPAIR &&
    ! empty( sanitize_text_field( $_GET['crcm_create_roles'] ?? '' ) ) &&
    current_user_can('manage_options')
) {
    add_action('admin_init', function () {
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

// Show success message
if (
    defined('CRCM_ALLOW_ROLE_REPAIR') &&
    CRCM_ALLOW_ROLE_REPAIR &&
    ! empty( sanitize_text_field( $_GET['crcm_roles_created'] ?? '' ) ) &&
    current_user_can('manage_options')
) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>' . esc_html__( 'Custom Rental Manager:', 'custom-rental-manager' ) . '</strong> ' . esc_html__( 'User roles created successfully!', 'custom-rental-manager' ) . '</p>';
        echo '</div>';
    });
}
