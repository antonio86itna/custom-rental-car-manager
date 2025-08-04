<?php
/**
 * Plugin Name: Custom Rental Car Manager
 * Plugin URI: https://totaliweb.com/plugins/custom-rental-car-manager
 * Description: Sistema completo per la gestione autonoleggio di auto e scooter per Costabilerent - SINGLE AGENCY
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
 * SINGLE AGENCY RENTAL MANAGER - COMPLETE VERSION
 * 
 * ✅ ADMINISTRATOR = TITOLARE (Controllo TOTALE)
 * ✅ MANAGER = DIPENDENTI (Solo veicoli/prenotazioni)
 * ✅ CUSTOMER = CLIENTI (Solo proprie prenotazioni)
 * ✅ Fixed capabilities per single-agency setup
 * ✅ Complete meta fields restored
 * ✅ All manager classes included safely
 */
class CRCM_Plugin {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Manager instances
     */
    public $vehicle_manager;
    public $booking_manager;
    public $calendar_manager;
    public $email_manager;
    public $settings_manager;
    
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
     * Initialize hooks - SINGLE AGENCY OPTIMIZED
     */
    private function init_hooks() {
        // Load text domain
        add_action('init', array($this, 'load_textdomain'), 1);
        
        // CRITICAL: Register post types with SINGLE AGENCY capabilities
        add_action('init', array($this, 'register_post_types'), 5);
        
        // Create SINGLE AGENCY user roles
        add_action('init', array($this, 'create_single_agency_roles'), 8);
        
        // Load dependencies
        add_action('init', array($this, 'load_dependencies'), 10);
        
        // Initialize managers
        add_action('init', array($this, 'init_managers'), 15);
        
        // Admin menu
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // Meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        
        // Activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('custom-rental-manager', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    /**
     * SINGLE AGENCY: Register custom post types with ADMIN FULL ACCESS
     */
    public function register_post_types() {
        // Vehicle post type - SINGLE AGENCY CAPABILITIES
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
            'show_in_menu' => false, // Custom menu
            
            // SINGLE AGENCY FIX: Standard WordPress capabilities
            'capability_type' => 'post',
            'map_meta_cap' => true,
            
            // ADMIN ALWAYS HAS ACCESS
            'capabilities' => array(
                'edit_post'          => 'edit_posts',
                'read_post'          => 'read',
                'delete_post'        => 'delete_posts',
                'edit_posts'         => 'edit_posts',
                'edit_others_posts'  => 'edit_others_posts',
                'publish_posts'      => 'publish_posts',
                'read_private_posts' => 'read_private_posts',
                'delete_posts'       => 'delete_posts',
                'delete_private_posts' => 'delete_private_posts',
                'delete_published_posts' => 'delete_published_posts',
                'delete_others_posts' => 'delete_others_posts',
                'edit_private_posts' => 'edit_private_posts',
                'edit_published_posts' => 'edit_published_posts',
            ),
        ));
        
        // Booking post type - SINGLE AGENCY CAPABILITIES
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
            'show_in_menu' => false, // Custom menu
            
            // SINGLE AGENCY FIX: Standard WordPress capabilities
            'capability_type' => 'post',
            'map_meta_cap' => true,
            
            // ADMIN ALWAYS HAS ACCESS
            'capabilities' => array(
                'edit_post'          => 'edit_posts',
                'read_post'          => 'read',
                'delete_post'        => 'delete_posts',
                'edit_posts'         => 'edit_posts',
                'edit_others_posts'  => 'edit_others_posts',
                'publish_posts'      => 'publish_posts',
                'read_private_posts' => 'read_private_posts',
                'delete_posts'       => 'delete_posts',
                'delete_private_posts' => 'delete_private_posts',
                'delete_published_posts' => 'delete_published_posts',
                'delete_others_posts' => 'delete_others_posts',
                'edit_private_posts' => 'edit_private_posts',
                'edit_published_posts' => 'edit_published_posts',
            ),
        ));
        
        // Flush rewrite rules on first run
        if (get_option('crcm_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('crcm_flush_rewrite_rules');
        }
        
        error_log('CRCM: Single Agency CPT registered successfully');
    }
    
    /**
     * SINGLE AGENCY: Create user roles optimized for single rental agency
     */
    public function create_single_agency_roles() {
        // CUSTOMER ROLE: Solo le proprie prenotazioni
        if (!get_role('crcm_customer')) {
            add_role('crcm_customer', __('Rental Customer', 'custom-rental-manager'), array(
                'read' => true,
                'crcm_view_own_bookings' => true,
                'crcm_create_booking_request' => true,
            ));
        }
        
        // MANAGER ROLE: Solo veicoli e prenotazioni (dipendenti)
        if (!get_role('crcm_manager')) {
            add_role('crcm_manager', __('Rental Manager', 'custom-rental-manager'), array(
                'read' => true,
                
                // Standard WordPress posts (for CPT)
                'edit_posts' => true,
                'edit_others_posts' => true,
                'publish_posts' => true,
                'delete_posts' => true,
                'delete_others_posts' => true,
                'edit_published_posts' => true,
                'delete_published_posts' => true,
                'edit_private_posts' => true,
                'delete_private_posts' => true,
                'read_private_posts' => true,
                
                // Plugin specific
                'crcm_manage_vehicles' => true,
                'crcm_manage_bookings' => true,
                'crcm_view_calendar' => true,
                'crcm_view_reports' => true,
                
                // Upload files for vehicle images
                'upload_files' => true,
            ));
        }
        
        // ADMINISTRATOR: CONTROLLO TOTALE (Tu - Titolare)
        $admin = get_role('administrator');
        if ($admin) {
            // WordPress standard capabilities
            $admin->add_cap('edit_posts');
            $admin->add_cap('edit_others_posts');
            $admin->add_cap('publish_posts');
            $admin->add_cap('delete_posts');
            $admin->add_cap('delete_others_posts');
            $admin->add_cap('edit_published_posts');
            $admin->add_cap('delete_published_posts');
            $admin->add_cap('edit_private_posts');
            $admin->add_cap('delete_private_posts');
            $admin->add_cap('read_private_posts');
            
            // SINGLE AGENCY: Admin ha TUTTO
            $admin->add_cap('crcm_manage_vehicles');
            $admin->add_cap('crcm_manage_bookings');
            $admin->add_cap('crcm_manage_customers');
            $admin->add_cap('crcm_manage_settings');
            $admin->add_cap('crcm_view_reports');
            $admin->add_cap('crcm_export_data');
            $admin->add_cap('crcm_manage_pricing');
            $admin->add_cap('crcm_view_calendar');
            $admin->add_cap('crcm_send_emails');
            $admin->add_cap('crcm_full_access');
        }
        
        error_log('CRCM: Single Agency roles created successfully');
    }
    
    /**
     * Load plugin dependencies
     */
    public function load_dependencies() {
        // Load helper functions
        if (file_exists(CRCM_PLUGIN_PATH . 'inc/functions.php')) {
            require_once CRCM_PLUGIN_PATH . 'inc/functions.php';
        }
        
        // Load manager classes
        $classes = array(
            'class-vehicle-manager.php',
            'class-booking-manager.php',
            'class-calendar-manager.php',
            'class-email-manager.php',
            'class-settings-manager.php',
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
    public function init_managers() {
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
        
        if (class_exists('CRCM_Settings_Manager')) {
            $this->settings_manager = new CRCM_Settings_Manager();
        }
    }
    
    /**
     * SINGLE AGENCY: Admin menu for rental agency owner
     */
    public function admin_menu() {
        // Main menu page
        add_menu_page(
            __('Costabilerent', 'custom-rental-manager'),
            __('Costabilerent', 'custom-rental-manager'),
            'read',  // EVERYONE with basic access can see menu
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
            'read',
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
            'read',
            'crcm-calendar',
            array($this, 'calendar_page')
        );
        
        // Customers submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Customers', 'custom-rental-manager'),
            __('Customers', 'custom-rental-manager'),
            'read',
            'crcm-customers',
            array($this, 'customers_page')
        );
        
        // Reports submenu
        add_submenu_page(
            'crcm-dashboard',
            __('Reports', 'custom-rental-manager'),
            __('Reports', 'custom-rental-manager'),
            'read',
            'crcm-reports',
            array($this, 'reports_page')
        );
        
        // Settings submenu - ONLY ADMIN
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
        echo '<div class="wrap">';
        echo '<h1>' . __('Costabilerent Dashboard', 'custom-rental-manager') . '</h1>';
        
        // Basic stats
        $vehicle_count = wp_count_posts('crcm_vehicle');
        $booking_count = wp_count_posts('crcm_booking');
        
        echo '<div class="crcm-stats" style="display: flex; gap: 20px; margin: 20px 0;">';
        
        echo '<div class="crcm-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; min-width: 200px;">';
        echo '<h3>' . __('Total Vehicles', 'custom-rental-manager') . '</h3>';
        echo '<p style="font-size: 24px; font-weight: bold; color: #0073aa;">' . (isset($vehicle_count->publish) ? $vehicle_count->publish : 0) . '</p>';
        if (current_user_can('edit_posts')) {
            echo '<p><a href="' . admin_url('post-new.php?post_type=crcm_vehicle') . '" class="button button-primary">Add New Vehicle</a></p>';
        }
        echo '</div>';
        
        echo '<div class="crcm-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; min-width: 200px;">';
        echo '<h3>' . __('Total Bookings', 'custom-rental-manager') . '</h3>';
        echo '<p style="font-size: 24px; font-weight: bold; color: #0073aa;">' . (isset($booking_count->publish) ? $booking_count->publish : 0) . '</p>';
        if (current_user_can('edit_posts')) {
            echo '<p><a href="' . admin_url('post-new.php?post_type=crcm_booking') . '" class="button button-primary">Add New Booking</a></p>';
        }
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
        if ($this->calendar_manager && method_exists($this->calendar_manager, 'display_calendar')) {
            $this->calendar_manager->display_calendar();
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
        if ($this->settings_manager && method_exists($this->settings_manager, 'display_settings')) {
            $this->settings_manager->display_settings();
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Settings', 'custom-rental-manager') . '</h1>';
            echo '<p>' . __('Settings functionality will be available in future updates.', 'custom-rental-manager') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add meta boxes - DELEGATE TO MANAGERS
     */
    public function add_meta_boxes() {
        // Vehicle meta boxes
        if ($this->vehicle_manager && method_exists($this->vehicle_manager, 'add_meta_boxes')) {
            $this->vehicle_manager->add_meta_boxes();
        }
        
        // Booking meta boxes
        if ($this->booking_manager && method_exists($this->booking_manager, 'add_meta_boxes')) {
            $this->booking_manager->add_meta_boxes();
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
        $this->create_single_agency_roles();
        
        // Register post types
        $this->register_post_types();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        error_log('CRCM: Single Agency Plugin activated successfully');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
        error_log('CRCM: Plugin deactivated');
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
        echo '<p><strong>Costabilerent Single Agency:</strong> Plugin activated successfully! You have full admin control.</p>';
        echo '</div>';
        delete_transient('crcm_activation_notice');
    }
});

// Set activation notice
register_activation_hook(__FILE__, function() {
    set_transient('crcm_activation_notice', true, 5);
});
