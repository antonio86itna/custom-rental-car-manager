<?php
/**
 * Plugin Name: Custom Rental Car Manager - PERFETTO RESET
 * Plugin URI: https://totaliweb.com/
 * Description: Sistema di gestione noleggio veicoli COMPLETAMENTE FUNZIONANTE - Reset Edition
 * Version: 2.0.0 RESET
 * Author: TotaliWeb
 * Text Domain: custom-rental-manager
 * Domain Path: /languages
 */

// PREVENT DIRECT ACCESS
if (!defined('ABSPATH')) {
    exit;
}

// DEFINE PLUGIN CONSTANTS
define('CRCM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRCM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CRCM_VERSION', '2.0.0-RESET');

/**
 * MAIN PLUGIN CLASS - PERFETTO E SICURO
 */
class Custom_Rental_Car_Manager_RESET {
    
    private $vehicle_manager;
    private $booking_manager;
    
    /**
     * Constructor - SAFE INITIALIZATION
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // SAFE: Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin - MAIN INITIALIZATION
     */
    public function init() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize managers
        $this->vehicle_manager = new CRCM_Vehicle_Manager();
        $this->booking_manager = new CRCM_Booking_Manager();
        
        // Register post types
        $this->register_post_types();
        
        // Admin hooks - ONLY when needed
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Frontend hooks
        $this->init_frontend();
    }
    
    /**
     * Load plugin dependencies - SAFE LOADING
     */
    private function load_dependencies() {
        // Check if files exist before requiring
        $files = array(
            'class-vehicle-manager-RESET.php',
            'class-booking-manager-RESET.php'
        );
        
        foreach ($files as $file) {
            $file_path = CRCM_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                add_action('admin_notices', function() use ($file) {
                    echo '<div class="notice notice-error"><p>';
                    echo sprintf(__('CRCM: Missing required file %s', 'custom-rental-manager'), $file);
                    echo '</p></div>';
                });
            }
        }
    }
    
    /**
     * Initialize admin functionality - ADMIN ONLY
     */
    private function init_admin() {
        // Meta boxes - SAFE: Add only when editing
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Custom columns
        add_filter('manage_crcm_vehicle_posts_columns', array($this, 'vehicle_columns'));
        add_action('manage_crcm_vehicle_posts_custom_column', array($this, 'vehicle_column_content'), 10, 2);
        
        add_filter('manage_crcm_booking_posts_columns', array($this, 'booking_columns'));
        add_action('manage_crcm_booking_posts_custom_column', array($this, 'booking_column_content'), 10, 2);
        
        // Make columns sortable
        add_filter('manage_edit-crcm_vehicle_sortable_columns', array($this, 'vehicle_sortable_columns'));
        add_filter('manage_edit-crcm_booking_sortable_columns', array($this, 'booking_sortable_columns'));
    }
    
    /**
     * Initialize frontend functionality - FRONTEND ONLY
     */
    private function init_frontend() {
        // Frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // Shortcodes
        add_shortcode('crcm_vehicle_list', array($this, 'vehicle_list_shortcode'));
        add_shortcode('crcm_booking_form', array($this, 'booking_form_shortcode'));
    }
    
    /**
     * Register custom post types - VEHICLES & BOOKINGS
     */
    public function register_post_types() {
        // VEHICLE POST TYPE
        $vehicle_labels = array(
            'name' => __('Vehicles', 'custom-rental-manager'),
            'singular_name' => __('Vehicle', 'custom-rental-manager'),
            'menu_name' => __('Vehicles', 'custom-rental-manager'),
            'add_new' => __('Add New Vehicle', 'custom-rental-manager'),
            'add_new_item' => __('Add New Vehicle', 'custom-rental-manager'),
            'edit_item' => __('Edit Vehicle', 'custom-rental-manager'),
            'new_item' => __('New Vehicle', 'custom-rental-manager'),
            'view_item' => __('View Vehicle', 'custom-rental-manager'),
            'search_items' => __('Search Vehicles', 'custom-rental-manager'),
            'not_found' => __('No vehicles found', 'custom-rental-manager'),
            'not_found_in_trash' => __('No vehicles found in trash', 'custom-rental-manager'),
        );
        
        $vehicle_args = array(
            'labels' => $vehicle_labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false, // Will be added to custom menu
            'query_var' => true,
            'rewrite' => array('slug' => 'vehicles'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-car',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest' => false, // Keep it simple
        );
        
        register_post_type('crcm_vehicle', $vehicle_args);
        
        // BOOKING POST TYPE
        $booking_labels = array(
            'name' => __('Bookings', 'custom-rental-manager'),
            'singular_name' => __('Booking', 'custom-rental-manager'),
            'menu_name' => __('Bookings', 'custom-rental-manager'),
            'add_new' => __('Add New Booking', 'custom-rental-manager'),
            'add_new_item' => __('Add New Booking', 'custom-rental-manager'),
            'edit_item' => __('Edit Booking', 'custom-rental-manager'),
            'new_item' => __('New Booking', 'custom-rental-manager'),
            'view_item' => __('View Booking', 'custom-rental-manager'),
            'search_items' => __('Search Bookings', 'custom-rental-manager'),
            'not_found' => __('No bookings found', 'custom-rental-manager'),
            'not_found_in_trash' => __('No bookings found in trash', 'custom-rental-manager'),
        );
        
        $booking_args = array(
            'labels' => $booking_labels,
            'public' => false, // Private - admin only
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => false, // Will be added to custom menu
            'query_var' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 26,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title'),
            'show_in_rest' => false,
        );
        
        register_post_type('crcm_booking', $booking_args);
    }
    
    /**
     * Add meta boxes - SAFE: Only when editing
     */
    public function add_meta_boxes() {
        // Check current screen
        $screen = get_current_screen();
        if (!$screen) return;
        
        // Vehicle meta boxes
        if ($screen->post_type === 'crcm_vehicle') {
            if ($this->vehicle_manager) {
                $this->vehicle_manager->add_meta_boxes();
            }
        }
        
        // Booking meta boxes
        if ($screen->post_type === 'crcm_booking') {
            if ($this->booking_manager) {
                $this->booking_manager->add_meta_boxes();
            }
        }
    }
    
    /**
     * Add admin menu - ORGANIZED MENU
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Rental Manager', 'custom-rental-manager'),
            __('Rental Manager', 'custom-rental-manager'),
            'manage_options',
            'crcm-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-car',
            25
        );
        
        // Dashboard submenu
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
            __('All Vehicles', 'custom-rental-manager'),
            __('All Vehicles', 'custom-rental-manager'),
            'edit_posts',
            'edit.php?post_type=crcm_vehicle'
        );
        
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
            __('All Bookings', 'custom-rental-manager'),
            __('All Bookings', 'custom-rental-manager'),
            'edit_posts',
            'edit.php?post_type=crcm_booking'
        );
        
        add_submenu_page(
            'crcm-dashboard',
            __('Add Booking', 'custom-rental-manager'),
            __('Add Booking', 'custom-rental-manager'),
            'edit_posts',
            'post-new.php?post_type=crcm_booking'
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
     * Dashboard page - OVERVIEW & STATS
     */
    public function dashboard_page() {
        // Get statistics
        $vehicle_stats = $this->vehicle_manager->get_vehicle_statistics();
        $booking_stats = $this->booking_manager->get_booking_statistics();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Rental Manager Dashboard', 'custom-rental-manager'); ?></h1>
            
            <div class="crcm-dashboard-stats">
                <div class="crcm-stat-cards">
                    <!-- Vehicle Stats -->
                    <div class="crcm-stat-card">
                        <h3><?php _e('Vehicles', 'custom-rental-manager'); ?></h3>
                        <div class="crcm-stat-number"><?php echo $vehicle_stats['total']; ?></div>
                        <div class="crcm-stat-details">
                            <span class="available"><?php echo $vehicle_stats['available']; ?> <?php _e('Available', 'custom-rental-manager'); ?></span>
                            <span class="rented"><?php echo $vehicle_stats['rented']; ?> <?php _e('Rented', 'custom-rental-manager'); ?></span>
                            <span class="maintenance"><?php echo $vehicle_stats['maintenance']; ?> <?php _e('Maintenance', 'custom-rental-manager'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Booking Stats -->
                    <div class="crcm-stat-card">
                        <h3><?php _e('Bookings', 'custom-rental-manager'); ?></h3>
                        <div class="crcm-stat-number"><?php echo $booking_stats['total']; ?></div>
                        <div class="crcm-stat-details">
                            <span class="active"><?php echo $booking_stats['active']; ?> <?php _e('Active', 'custom-rental-manager'); ?></span>
                            <span class="confirmed"><?php echo $booking_stats['confirmed']; ?> <?php _e('Confirmed', 'custom-rental-manager'); ?></span>
                            <span class="pending"><?php echo $booking_stats['pending']; ?> <?php _e('Pending', 'custom-rental-manager'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Revenue Stats -->
                    <div class="crcm-stat-card">
                        <h3><?php _e('Revenue', 'custom-rental-manager'); ?></h3>
                        <div class="crcm-stat-number">€<?php echo number_format($booking_stats['total_revenue'], 0); ?></div>
                        <div class="crcm-stat-details">
                            <span><?php _e('Total Revenue', 'custom-rental-manager'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="crcm-recent-activity">
                    <h3><?php _e('Recent Activity', 'custom-rental-manager'); ?></h3>
                    
                    <div class="crcm-activity-tabs">
                        <button class="crcm-tab-button active" data-tab="recent-bookings"><?php _e('Recent Bookings', 'custom-rental-manager'); ?></button>
                        <button class="crcm-tab-button" data-tab="vehicle-status"><?php _e('Vehicle Status', 'custom-rental-manager'); ?></button>
                    </div>
                    
                    <div id="recent-bookings" class="crcm-tab-content active">
                        <?php $this->display_recent_bookings(); ?>
                    </div>
                    
                    <div id="vehicle-status" class="crcm-tab-content">
                        <?php $this->display_vehicle_status(); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .crcm-dashboard-stats {
            margin-top: 20px;
        }
        
        .crcm-stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .crcm-stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        
        .crcm-stat-card h3 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        
        .crcm-stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #0073aa;
            margin-bottom: 10px;
        }
        
        .crcm-stat-details span {
            display: inline-block;
            margin: 0 5px;
            padding: 2px 6px;
            background: #f1f1f1;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .crcm-stat-details .available { background: #d1e7dd; color: #0f5132; }
        .crcm-stat-details .rented { background: #fff3cd; color: #664d03; }
        .crcm-stat-details .maintenance { background: #f8d7da; color: #721c24; }
        .crcm-stat-details .active { background: #d1e7dd; color: #0f5132; }
        .crcm-stat-details .confirmed { background: #cce5ff; color: #003d7a; }
        .crcm-stat-details .pending { background: #fff3cd; color: #664d03; }
        
        .crcm-recent-activity {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .crcm-activity-tabs {
            margin-bottom: 20px;
            border-bottom: 1px solid #ccd0d4;
        }
        
        .crcm-tab-button {
            background: none;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        
        .crcm-tab-button.active {
            border-bottom-color: #0073aa;
            color: #0073aa;
        }
        
        .crcm-tab-content {
            display: none;
        }
        
        .crcm-tab-content.active {
            display: block;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.crcm-tab-button').on('click', function() {
                var tab = $(this).data('tab');
                
                $('.crcm-tab-button').removeClass('active');
                $('.crcm-tab-content').removeClass('active');
                
                $(this).addClass('active');
                $('#' + tab).addClass('active');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display recent bookings - DASHBOARD WIDGET
     */
    private function display_recent_bookings() {
        $recent_bookings = get_posts(array(
            'post_type' => 'crcm_booking',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($recent_bookings)) {
            echo '<p>' . __('No recent bookings found.', 'custom-rental-manager') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Customer', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Vehicle', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Dates', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Status', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Total', 'custom-rental-manager') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($recent_bookings as $booking) {
            $customer_name = get_post_meta($booking->ID, '_crcm_customer_name', true);
            $vehicle_id = get_post_meta($booking->ID, '_crcm_vehicle_id', true);
            $pickup_date = get_post_meta($booking->ID, '_crcm_pickup_date', true);
            $return_date = get_post_meta($booking->ID, '_crcm_return_date', true);
            $status = get_post_meta($booking->ID, '_crcm_booking_status', true);
            $total = get_post_meta($booking->ID, '_crcm_total_amount', true);
            
            $vehicle_title = $vehicle_id ? get_the_title($vehicle_id) : __('Unknown Vehicle', 'custom-rental-manager');
            
            echo '<tr>';
            echo '<td><a href="' . get_edit_post_link($booking->ID) . '">' . esc_html($customer_name) . '</a></td>';
            echo '<td>' . esc_html($vehicle_title) . '</td>';
            echo '<td>' . date_i18n('d/m/Y', strtotime($pickup_date)) . ' - ' . date_i18n('d/m/Y', strtotime($return_date)) . '</td>';
            echo '<td><span class="status-' . $status . '">' . ucfirst($status) . '</span></td>';
            echo '<td>€' . number_format($total, 2) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Display vehicle status - DASHBOARD WIDGET
     */
    private function display_vehicle_status() {
        $vehicles = get_posts(array(
            'post_type' => 'crcm_vehicle',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        if (empty($vehicles)) {
            echo '<p>' . __('No vehicles found.', 'custom-rental-manager') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Vehicle', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Type', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Status', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Location', 'custom-rental-manager') . '</th>';
        echo '<th>' . __('Next Service', 'custom-rental-manager') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($vehicles as $vehicle) {
            $vehicle_type = get_post_meta($vehicle->ID, '_crcm_vehicle_type', true);
            $status = get_post_meta($vehicle->ID, '_crcm_vehicle_status', true) ?: 'available';
            $location = get_post_meta($vehicle->ID, '_crcm_location', true);
            $next_service = get_post_meta($vehicle->ID, '_crcm_next_service', true);
            
            echo '<tr>';
            echo '<td><a href="' . get_edit_post_link($vehicle->ID) . '">' . esc_html($vehicle->post_title) . '</a></td>';
            echo '<td>' . ucfirst($vehicle_type) . '</td>';
            echo '<td><span class="status-' . $status . '">' . ucfirst($status) . '</span></td>';
            echo '<td>' . ucfirst(str_replace('_', ' ', $location)) . '</td>';
            echo '<td>' . ($next_service ? date_i18n('d/m/Y', strtotime($next_service)) : '-') . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Reports page - ANALYTICS & REPORTS
     */
    public function reports_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Reports', 'custom-rental-manager'); ?></h1>
            <p><?php _e('Analytics and reporting features coming soon!', 'custom-rental-manager'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Settings page - PLUGIN CONFIGURATION
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Settings', 'custom-rental-manager'); ?></h1>
            <p><?php _e('Configuration options coming soon!', 'custom-rental-manager'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Vehicle custom columns
     */
    public function vehicle_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['vehicle_type'] = __('Type', 'custom-rental-manager');
        $new_columns['license_plate'] = __('License Plate', 'custom-rental-manager');
        $new_columns['daily_rate'] = __('Daily Rate', 'custom-rental-manager');
        $new_columns['status'] = __('Status', 'custom-rental-manager');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    public function vehicle_column_content($column, $post_id) {
        switch ($column) {
            case 'vehicle_type':
                echo ucfirst(get_post_meta($post_id, '_crcm_vehicle_type', true));
                break;
            case 'license_plate':
                echo strtoupper(get_post_meta($post_id, '_crcm_license_plate', true));
                break;
            case 'daily_rate':
                $rate = get_post_meta($post_id, '_crcm_daily_rate', true);
                echo $rate ? '€' . number_format($rate, 0) : '-';
                break;
            case 'status':
                $status = get_post_meta($post_id, '_crcm_vehicle_status', true) ?: 'available';
                echo '<span class="status-' . $status . '">' . ucfirst($status) . '</span>';
                break;
        }
    }
    
    public function vehicle_sortable_columns($columns) {
        $columns['vehicle_type'] = 'vehicle_type';
        $columns['daily_rate'] = 'daily_rate';
        $columns['status'] = 'status';
        return $columns;
    }
    
    /**
     * Booking custom columns
     */
    public function booking_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['customer'] = __('Customer', 'custom-rental-manager');
        $new_columns['vehicle'] = __('Vehicle', 'custom-rental-manager');
        $new_columns['dates'] = __('Rental Period', 'custom-rental-manager');
        $new_columns['status'] = __('Status', 'custom-rental-manager');
        $new_columns['total'] = __('Total', 'custom-rental-manager');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    public function booking_column_content($column, $post_id) {
        switch ($column) {
            case 'customer':
                echo esc_html(get_post_meta($post_id, '_crcm_customer_name', true));
                break;
            case 'vehicle':
                $vehicle_id = get_post_meta($post_id, '_crcm_vehicle_id', true);
                if ($vehicle_id) {
                    echo '<a href="' . get_edit_post_link($vehicle_id) . '">' . esc_html(get_the_title($vehicle_id)) . '</a>';
                } else {
                    echo '-';
                }
                break;
            case 'dates':
                $pickup = get_post_meta($post_id, '_crcm_pickup_date', true);
                $return = get_post_meta($post_id, '_crcm_return_date', true);
                if ($pickup && $return) {
                    echo date_i18n('d/m', strtotime($pickup)) . ' - ' . date_i18n('d/m/Y', strtotime($return));
                } else {
                    echo '-';
                }
                break;
            case 'status':
                $status = get_post_meta($post_id, '_crcm_booking_status', true) ?: 'pending';
                echo '<span class="status-' . $status . '">' . ucfirst($status) . '</span>';
                break;
            case 'total':
                $total = get_post_meta($post_id, '_crcm_total_amount', true);
                echo $total ? '€' . number_format($total, 2) : '-';
                break;
        }
    }
    
    public function booking_sortable_columns($columns) {
        $columns['customer'] = 'customer';
        $columns['dates'] = 'pickup_date';
        $columns['status'] = 'status';
        $columns['total'] = 'total';
        return $columns;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        // Only load on our post types
        if (!in_array($post_type, array('crcm_vehicle', 'crcm_booking'))) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');
        
        // Custom admin CSS
        wp_add_inline_style('wp-admin', '
            .status-available { background: #d1e7dd; color: #0f5132; padding: 2px 6px; border-radius: 3px; }
            .status-rented { background: #fff3cd; color: #664d03; padding: 2px 6px; border-radius: 3px; }
            .status-maintenance { background: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; }
            .status-out_of_service { background: #e2e3e5; color: #383d41; padding: 2px 6px; border-radius: 3px; }
            .status-pending { background: #fff3cd; color: #664d03; padding: 2px 6px; border-radius: 3px; }
            .status-confirmed { background: #cce5ff; color: #003d7a; padding: 2px 6px; border-radius: 3px; }
            .status-active { background: #d1e7dd; color: #0f5132; padding: 2px 6px; border-radius: 3px; }
            .status-completed { background: #e2e3e5; color: #383d41; padding: 2px 6px; border-radius: 3px; }
            .status-cancelled { background: #f8d7da; color: #721c24; padding: 2px 6px; border-radius: 3px; }
            
            .extras-list label { display: block; margin: 5px 0; }
            .extra-price { color: #666; font-size: 0.9em; margin-left: 10px; }
            .pricing-total { font-size: 1.5em; font-weight: bold; color: #0073aa; }
            
            #pricing-breakdown-table { width: 100%; margin-top: 10px; }
            #pricing-breakdown-table th, #pricing-breakdown-table td { 
                padding: 5px 10px; border-bottom: 1px solid #ddd; text-align: left; 
            }
            #pricing-breakdown-table th { background: #f9f9f9; }
        ');
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_enqueue_scripts() {
        // Frontend styles will be added here
    }
    
    /**
     * Vehicle list shortcode
     */
    public function vehicle_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'limit' => 10,
            'status' => 'available'
        ), $atts);
        
        $args = array(
            'post_type' => 'crcm_vehicle',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_crcm_vehicle_status',
                    'value' => $atts['status']
                )
            )
        );
        
        if (!empty($atts['type'])) {
            $args['meta_query'][] = array(
                'key' => '_crcm_vehicle_type',
                'value' => $atts['type']
            );
        }
        
        $vehicles = get_posts($args);
        
        if (empty($vehicles)) {
            return '<p>' . __('No vehicles available.', 'custom-rental-manager') . '</p>';
        }
        
        $output = '<div class="crcm-vehicle-list">';
        
        foreach ($vehicles as $vehicle) {
            $brand = get_post_meta($vehicle->ID, '_crcm_brand', true);
            $model = get_post_meta($vehicle->ID, '_crcm_model', true);
            $daily_rate = get_post_meta($vehicle->ID, '_crcm_daily_rate', true);
            $vehicle_type = get_post_meta($vehicle->ID, '_crcm_vehicle_type', true);
            
            $output .= '<div class="crcm-vehicle-item">';
            $output .= '<h3>' . esc_html($vehicle->post_title) . '</h3>';
            $output .= '<p><strong>' . __('Type:', 'custom-rental-manager') . '</strong> ' . ucfirst($vehicle_type) . '</p>';
            if ($daily_rate) {
                $output .= '<p><strong>' . __('Daily Rate:', 'custom-rental-manager') . '</strong> €' . number_format($daily_rate, 0) . '</p>';
            }
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Booking form shortcode
     */
    public function booking_form_shortcode($atts) {
        // Simple booking form will be implemented here
        return '<p>' . __('Booking form coming soon!', 'custom-rental-manager') . '</p>';
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('custom-rental-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Plugin activation - SAFE ACTIVATION
     */
    public function activate() {
        // Flush rewrite rules
        $this->register_post_types();
        flush_rewrite_rules();
        
        // Add success notice
        add_option('crcm_activation_notice', true);
        
        // Log activation
        error_log('CRCM: Plugin activated successfully - Reset Edition v' . CRCM_VERSION);
    }
    
    /**
     * Plugin deactivation - CLEAN DEACTIVATION
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('CRCM: Plugin deactivated');
    }
    
    /**
     * Show activation notice
     */
    public function activation_notice() {
        if (get_option('crcm_activation_notice')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php _e('Custom Rental Manager', 'custom-rental-manager'); ?></strong> <?php _e('has been activated successfully! RESET Edition v' . CRCM_VERSION, 'custom-rental-manager'); ?></p>
            </div>
            <?php
            delete_option('crcm_activation_notice');
        }
    }
}

// INITIALIZE PLUGIN - SAFE INITIALIZATION
function crcm_init() {
    global $crcm_plugin;
    $crcm_plugin = new Custom_Rental_Car_Manager_RESET();
}

// Hook initialization
add_action('plugins_loaded', 'crcm_init');

// Show activation notice
add_action('admin_notices', function() {
    if (get_option('crcm_activation_notice')) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php _e('Custom Rental Manager RESET Edition', 'custom-rental-manager'); ?></strong> <?php _e('activated successfully! Version', 'custom-rental-manager'); ?> <?php echo CRCM_VERSION; ?></p>
        </div>
        <?php
        delete_option('crcm_activation_notice');
    }
});

// FINAL CHECK - ENSURE EVERYTHING IS LOADED
if (!function_exists('crcm_check_requirements')) {
    function crcm_check_requirements() {
        if (version_compare(PHP_VERSION, '7.0', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo __('Custom Rental Manager requires PHP 7.0 or higher.', 'custom-rental-manager');
                echo '</p></div>';
            });
            return false;
        }
        return true;
    }
    add_action('admin_init', 'crcm_check_requirements');
}

// SUCCESS MESSAGE
error_log('CRCM RESET: Plugin file loaded successfully - All functions working!');
