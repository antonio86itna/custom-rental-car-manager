<?php
/**
 * User Management Class - PERFECT ROLE MANAGEMENT SYSTEM
 * 
 * This class handles all user role restrictions and permissions exactly as requested:
 * - Administrator: Full access to everything
 * - Manager: Only vehicles and bookings management  
 * - Customer: Only frontend area, no admin access, clean experience
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Advanced user role management for CRCM
 */
class CRCM_User_Management {
    
    public function __construct() {
        // Core initialization
        add_action('init', array($this, 'create_roles'), 5);
        add_action('admin_init', array($this, 'restrict_admin_access'), 1);
        
        // Admin bar and interface restrictions
        add_action('wp_before_admin_bar_render', array($this, 'modify_admin_bar'));
        add_filter('show_admin_bar', array($this, 'show_admin_bar'));
        
        // Menu and capability restrictions  
        add_action('admin_menu', array($this, 'restrict_admin_menus'), 999);
        add_filter('user_has_cap', array($this, 'modify_user_capabilities'), 10, 4);
        
        // Clean customer experience
        add_action('admin_head', array($this, 'hide_admin_elements'));
        add_action('wp_dashboard_setup', array($this, 'remove_dashboard_widgets'));
        
        // Login/logout redirects
        add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);
        add_action('wp_logout', array($this, 'logout_redirect'));
    }
    
    /**
     * Create custom roles with perfect permissions
     */
    public function create_roles() {
        // ADMINISTRATOR: Everything (WordPress default + plugin capabilities)
        $admin = get_role('administrator');
        if ($admin) {
            // Plugin-specific capabilities
            $admin->add_cap('crcm_full_access');
            $admin->add_cap('crcm_manage_vehicles');
            $admin->add_cap('crcm_manage_bookings');
            $admin->add_cap('crcm_manage_customers');
            $admin->add_cap('crcm_view_reports');
            $admin->add_cap('crcm_manage_settings');
            
            // WordPress standard capabilities (already exist, but ensuring)
            $admin->add_cap('edit_posts');
            $admin->add_cap('edit_others_posts');
            $admin->add_cap('publish_posts'); 
            $admin->add_cap('delete_posts');
            $admin->add_cap('delete_others_posts');
            $admin->add_cap('edit_published_posts');
            $admin->add_cap('delete_published_posts');
        }
        
        // MANAGER: Limited to vehicles and bookings only
        if (!get_role('crcm_manager')) {
            add_role('crcm_manager', __('Rental Manager', 'custom-rental-manager'), array(
                'read' => true,
                
                // WordPress post capabilities (for vehicles/bookings)
                'edit_posts' => true,
                'edit_others_posts' => true,
                'publish_posts' => true,
                'delete_posts' => true,
                'edit_published_posts' => true,
                'delete_published_posts' => true,
                'upload_files' => true,
                
                // Plugin-specific capabilities
                'crcm_manage_vehicles' => true,
                'crcm_manage_bookings' => true,
                'crcm_view_reports' => true,
                
                // Explicitly deny other capabilities
                'manage_options' => false,
                'edit_users' => false,
                'create_users' => false,
                'delete_users' => false,
                'edit_plugins' => false,
                'edit_themes' => false
            ));
        }
        
        // CUSTOMER: Frontend only, no admin access
        if (!get_role('crcm_customer')) {
            add_role('crcm_customer', __('Rental Customer', 'custom-rental-manager'), array(
                'read' => true,
                
                // Plugin-specific customer capabilities
                'crcm_view_own_bookings' => true,
                'crcm_edit_own_profile' => true,
                'crcm_cancel_bookings' => true,
                
                // Explicitly deny all admin capabilities
                'edit_posts' => false,
                'edit_others_posts' => false,
                'publish_posts' => false,
                'delete_posts' => false,
                'manage_options' => false,
                'edit_users' => false,
                'upload_files' => false
            ));
        }
    }
    
    /**
     * CRITICAL: Restrict admin access for customers - redirect to frontend
     */
    public function restrict_admin_access() {
        // Allow AJAX calls to work
        if (wp_doing_ajax()) {
            return;
        }
        
        // Allow administrators full access
        if (current_user_can('manage_options')) {
            return;
        }
        
        // Allow managers access to vehicles and bookings only
        if (current_user_can('crcm_manage_vehicles')) {
            // Manager is allowed in admin, but we'll restrict menus later
            return;
        }
        
        // CUSTOMERS: Redirect to frontend customer area
        if (current_user_can('crcm_view_own_bookings')) {
            if (is_admin() && !wp_doing_ajax()) {
                // Find customer area page
                $customer_page = get_page_by_path('customer-area');
                $redirect_url = $customer_page ? get_permalink($customer_page) : home_url('/');
                
                wp_redirect($redirect_url);
                exit;
            }
        }
        
        // If user has no relevant capabilities, deny access
        if (is_admin() && !current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to access this area.', 'custom-rental-manager'));
        }
    }
    
    /**
     * Hide admin bar for customers completely
     */
    public function show_admin_bar($show_admin_bar) {
        // Hide admin bar for customers
        if (current_user_can('crcm_view_own_bookings') && !current_user_can('crcm_manage_vehicles')) {
            return false;
        }
        
        return $show_admin_bar;
    }
    
    /**
     * Modify admin bar for managers (remove unnecessary items)
     */
    public function modify_admin_bar() {
        global $wp_admin_bar;
        
        // For managers: remove some WordPress default items
        if (current_user_can('crcm_manage_vehicles') && !current_user_can('manage_options')) {
            $wp_admin_bar->remove_node('wp-logo');
            $wp_admin_bar->remove_node('about');
            $wp_admin_bar->remove_node('wporg');
            $wp_admin_bar->remove_node('documentation');
            $wp_admin_bar->remove_node('support-forums');
            $wp_admin_bar->remove_node('feedback');
            $wp_admin_bar->remove_node('themes');
            $wp_admin_bar->remove_node('customize');
            $wp_admin_bar->remove_node('widgets');
            $wp_admin_bar->remove_node('menus');
        }
        
        // For customers: remove everything (but admin bar is already hidden)
        if (current_user_can('crcm_view_own_bookings') && !current_user_can('crcm_manage_vehicles')) {
            $wp_admin_bar->remove_node('wp-logo');
            $wp_admin_bar->remove_node('site-name');
            $wp_admin_bar->remove_node('updates');
            $wp_admin_bar->remove_node('comments');
            $wp_admin_bar->remove_node('new-content');
            $wp_admin_bar->remove_node('edit');
        }
    }
    
    /**
     * Restrict admin menus based on user role
     */
    public function restrict_admin_menus() {
        // MANAGERS: Only show vehicles, bookings, and rental-related menus
        if (current_user_can('crcm_manage_vehicles') && !current_user_can('manage_options')) {
            // Remove WordPress default menus
            remove_menu_page('index.php');                  // Dashboard
            remove_menu_page('edit.php');                   // Posts
            remove_menu_page('upload.php');                 // Media (keep if needed for vehicles)
            remove_menu_page('edit.php?post_type=page');    // Pages
            remove_menu_page('edit-comments.php');          // Comments
            remove_menu_page('themes.php');                 // Appearance
            remove_menu_page('plugins.php');                // Plugins
            remove_menu_page('users.php');                  // Users
            remove_menu_page('tools.php');                  // Tools
            remove_menu_page('options-general.php');        // Settings
            
            // Remove plugin-specific restricted menus
            remove_submenu_page('crcm-dashboard', 'crcm-customers');  // No customer management
            remove_submenu_page('crcm-dashboard', 'crcm-settings');   // No settings access
            
            // Redirect dashboard to vehicles
            add_action('load-index.php', function() {
                wp_redirect(admin_url('admin.php?page=crcm-dashboard'));
                exit;
            });
        }
        
        // CUSTOMERS: Should never reach admin, but just in case
        if (current_user_can('crcm_view_own_bookings') && !current_user_can('crcm_manage_vehicles')) {
            // Remove all menus
            remove_all_actions('admin_menu');
            remove_all_actions('_admin_menu');
        }
    }
    
    /**
     * Modify user capabilities dynamically
     */
    public function modify_user_capabilities($allcaps, $cap, $args, $user) {
        // If checking for post edit capabilities on our custom post types
        if (isset($args[0]) && in_array($args[0], array('edit_post', 'delete_post'))) {
            $post_id = isset($args[2]) ? $args[2] : 0;
            
            if ($post_id) {
                $post = get_post($post_id); 
                
                if ($post && in_array($post->post_type, array('crcm_vehicle', 'crcm_booking'))) {
                    // Administrators: full access
                    if (user_can($user, 'manage_options')) {
                        $allcaps[$args[0]] = true;
                    }
                    // Managers: can edit vehicles and bookings
                    elseif (user_can($user, 'crcm_manage_vehicles')) {
                        $allcaps[$args[0]] = true;
                    }
                    // Customers: no edit access
                    else {
                        $allcaps[$args[0]] = false;
                    }
                }
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Hide admin interface elements for restricted users
     */
    public function hide_admin_elements() {
        $screen = get_current_screen();
        
        // For managers: hide certain interface elements
        if (current_user_can('crcm_manage_vehicles') && !current_user_can('manage_options')) {
            ?>
            <style>
                /* Hide welcome panel */
                #welcome-panel { display: none !important; }
                
                /* Hide screen options and help for cleaner interface */
                #contextual-help-link-wrap { display: none !important; }
                
                /* Hide update nags */
                .update-nag, .notice.notice-warning { display: none !important; }
                
                /* Simplify footer */
                #wpfooter { opacity: 0.5; }
            </style>
            <?php
        }
        
        // For customers: hide everything (they shouldn't be here anyway)
        if (current_user_can('crcm_view_own_bookings') && !current_user_can('crcm_manage_vehicles')) {
            ?>
            <style>
                /* Hide everything except logout */
                #wpadminbar > * { display: none !important; }
                #wpadminbar #wp-admin-bar-my-account { display: block !important; }
                
                /* Hide admin interface */
                #adminmenumain, #wpbody-content { display: none !important; }
            </style>
            <?php
        }
    }
    
    /**
     * Remove dashboard widgets for restricted users
     */
    public function remove_dashboard_widgets() {
        // For managers: keep only relevant widgets
        if (current_user_can('crcm_manage_vehicles') && !current_user_can('manage_options')) {
            // Remove default WordPress widgets
            remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
            remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
            remove_meta_box('dashboard_primary', 'dashboard', 'side');
            remove_meta_box('dashboard_secondary', 'dashboard', 'side');
            remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
            remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
            remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
            remove_meta_box('dashboard_activity', 'dashboard', 'normal');
        }
        
        // For customers: remove all widgets (they shouldn't see dashboard)
        if (current_user_can('crcm_view_own_bookings') && !current_user_can('crcm_manage_vehicles')) {
            remove_action('wp_dashboard_setup', 'wp_dashboard_setup');
        }
    }
    
    /**
     * Handle login redirects based on user role
     */
    public function login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            // Administrators: go to plugin dashboard
            if (in_array('administrator', $user->roles)) {
                return admin_url('admin.php?page=crcm-dashboard');
            }
            
            // Managers: go to plugin dashboard  
            elseif (in_array('crcm_manager', $user->roles)) {
                return admin_url('admin.php?page=crcm-dashboard');
            }
            
            // Customers: go to frontend customer area
            elseif (in_array('crcm_customer', $user->roles)) {
                $customer_page = get_page_by_path('customer-area');
                return $customer_page ? get_permalink($customer_page) : home_url('/');
            }
        }
        
        return $redirect_to;
    }
    
    /**
     * Handle logout redirects
     */
    public function logout_redirect() {
        wp_redirect(home_url('/'));
        exit;
    }
    
    /**
     * Check if current user can access specific CRCM features
     */
    public function user_can_access($feature) {
        switch ($feature) {
            case 'dashboard':
                return current_user_can('manage_options') || current_user_can('crcm_manage_vehicles');
                
            case 'vehicles':
                return current_user_can('manage_options') || current_user_can('crcm_manage_vehicles');
                
            case 'bookings':
                return current_user_can('manage_options') || current_user_can('crcm_manage_vehicles');
                
            case 'customers':
                return current_user_can('manage_options'); // Only admin
                
            case 'settings':
                return current_user_can('manage_options'); // Only admin
                
            case 'reports':
                return current_user_can('manage_options') || current_user_can('crcm_manage_vehicles');
                
            case 'customer_area':
                return current_user_can('crcm_view_own_bookings');
                
            default:
                return false;
        }
    }
    
    /**
     * Get user role display name
     */
    public function get_user_role_display($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return __('Unknown', 'custom-rental-manager');
        }
        
        if (in_array('administrator', $user->roles)) {
            return __('Administrator', 'custom-rental-manager');
        } elseif (in_array('crcm_manager', $user->roles)) {
            return __('Rental Manager', 'custom-rental-manager');
        } elseif (in_array('crcm_customer', $user->roles)) {
            return __('Customer', 'custom-rental-manager');
        } else {
            return __('User', 'custom-rental-manager');
        }
    }
    
    /**
     * Add customer-specific body classes for styling
     */
    public function add_body_classes($classes) {
        if (current_user_can('crcm_view_own_bookings') && !current_user_can('crcm_manage_vehicles')) {
            $classes .= ' crcm-customer-area';
        } elseif (current_user_can('crcm_manage_vehicles') && !current_user_can('manage_options')) {
            $classes .= ' crcm-manager-area';
        } elseif (current_user_can('manage_options')) {
            $classes .= ' crcm-admin-area';
        }
        
        return $classes;
    }
    
    /**
     * Debug function to check user permissions
     */
    public function debug_user_permissions($user_id = null) {
        if (!current_user_can('manage_options')) {
            return array('error' => 'Access denied');
        }
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return array('error' => 'User not found');
        }
        
        return array(
            'user_id' => $user_id,
            'roles' => $user->roles,
            'capabilities' => array_keys(array_filter($user->allcaps)),
            'can_manage_vehicles' => current_user_can('crcm_manage_vehicles'),
            'can_manage_bookings' => current_user_can('crcm_manage_bookings'),
            'can_manage_customers' => current_user_can('crcm_manage_customers'),
            'can_view_reports' => current_user_can('crcm_view_reports'),
            'can_manage_settings' => current_user_can('manage_options'),
            'display_role' => $this->get_user_role_display($user_id)
        );
    }
}

// Initialize user management
new CRCM_User_Management();
