<?php
/**
 * WordPress Standards Compliance Class
 * 
 * Ensures the plugin follows WordPress.org best practices:
 * - Complete security measures with proper sanitization
 * - Performance optimization and caching
 * - WordPress coding standards compliance
 * - Proper nonce usage and CSRF protection
 * - Rate limiting and spam protection
 * - Data validation and error handling
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WordPress Standards Compliance Class
 */
class CRCM_WordPress_Standards {
    
    /**
     * Rate limiting cache
     */
    private $rate_limits = array();
    
    public function __construct() {
        add_action('init', array($this, 'init_security_measures'), 1);
        add_action('init', array($this, 'init_performance_optimization'), 5);
        add_filter('wp_kses_allowed_html', array($this, 'extend_allowed_html'), 10, 2);
        add_action('wp_loaded', array($this, 'validate_plugin_environment'));
        
        // Security hooks
        add_action('wp_ajax_crcm_get_vehicle_booking_data', array($this, 'validate_booking_ajax'), 1);
        add_action('wp_ajax_crcm_calculate_booking_total', array($this, 'validate_booking_ajax'), 1);
        add_action('wp_ajax_crcm_check_vehicle_availability', array($this, 'validate_booking_ajax'), 1);
        add_action('wp_ajax_crcm_search_customers', array($this, 'validate_admin_ajax'), 1);
        add_action('wp_ajax_crcm_create_customer', array($this, 'validate_admin_ajax'), 1);
        
        // Performance hooks
        add_action('save_post', array($this, 'clear_related_cache'), 20, 2);
        add_action('user_register', array($this, 'clear_user_cache'));
        add_action('profile_update', array($this, 'clear_user_cache'));
    }
    
    /**
     * Initialize comprehensive security measures
     */
    public function init_security_measures() {
        // Ensure proper capabilities are checked everywhere
        add_action('admin_init', array($this, 'check_user_capabilities'));
        
        // Sanitize all plugin inputs globally
        add_action('init', array($this, 'sanitize_plugin_inputs'), 20);
        
        // Add security headers
        add_action('send_headers', array($this, 'add_security_headers'));
        
        // Protect against common attacks
        add_action('init', array($this, 'prevent_common_attacks'));
        
        // Log security events
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_action('wp_login', array($this, 'log_successful_login'), 10, 2);
    }
    
    /**
     * Initialize performance optimization
     */
    public function init_performance_optimization() {
        // Object caching for frequent queries
        add_action('init', array($this, 'setup_object_caching'));
        
        // Database query optimization
        add_filter('posts_clauses', array($this, 'optimize_vehicle_queries'), 10, 2);
        
        // Asset optimization
        add_action('wp_enqueue_scripts', array($this, 'optimize_frontend_assets'), 999);
        add_action('admin_enqueue_scripts', array($this, 'optimize_admin_assets'), 999);
    }
    
    /**
     * CRITICAL: Check user capabilities for all plugin actions
     */
    public function check_user_capabilities() {
        $screen = get_current_screen();
        
        if (!$screen) {
            return;
        }
        
        // Check access to plugin admin pages
        if (strpos($screen->id, 'crcm') !== false || strpos($screen->id, 'costabilerent') !== false) {
            if (!$this->user_can_access_admin()) {
                wp_die(
                    __('Access Denied: You do not have permission to access this page.', 'custom-rental-manager'),
                    __('Access Denied', 'custom-rental-manager'),
                    array('response' => 403)
                );
            }
        }
        
        // Check access to custom post types
        if ($screen->post_type === 'crcm_vehicle' || $screen->post_type === 'crcm_booking') {
            if (!current_user_can('edit_posts')) {
                wp_die(
                    __('Access Denied: You do not have permission to manage this content.', 'custom-rental-manager'),
                    __('Access Denied', 'custom-rental-manager'),
                    array('response' => 403)
                );
            }
        }
    }
    
    /**
     * Check if user can access admin areas
     */
    private function user_can_access_admin() {
        // Administrators can access everything
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Managers can access vehicles and bookings
        if (current_user_can('crcm_manage_vehicles')) {
            return true;
        }
        
        // Customers cannot access admin
        return false;
    }
    
    /**
     * ENHANCED: Sanitize all plugin inputs with deep validation
     */
    public function sanitize_plugin_inputs() {
        if (!isset($_POST) || empty($_POST)) {
            return;
        }
        
        // Only sanitize plugin-related inputs
        $plugin_prefixes = array('crcm_', 'booking_data', 'vehicle_data', 'pricing_data', 'extras_data', 'insurance_data', 'misc_data');
        
        foreach ($_POST as $key => $value) {
            $is_plugin_input = false;
            
            foreach ($plugin_prefixes as $prefix) {
                if (strpos($key, $prefix) === 0) {
                    $is_plugin_input = true;
                    break;
                }
            }
            
            if ($is_plugin_input) {
                $_POST[$key] = $this->deep_sanitize($value);
            }
        }
        
        // Same for GET parameters
        if (isset($_GET) && !empty($_GET)) {
            foreach ($_GET as $key => $value) {
                $is_plugin_input = false;
                
                foreach ($plugin_prefixes as $prefix) {
                    if (strpos($key, $prefix) === 0) {
                        $is_plugin_input = true;
                        break;
                    }  
                }
                
                if ($is_plugin_input) {
                    $_GET[$key] = $this->deep_sanitize($value);
                }
            }
        }
    }
    
    /**
     * Deep sanitization for complex data structures
     */
    private function deep_sanitize($data) {
        if (is_array($data)) {
            return array_map(array($this, 'deep_sanitize'), $data);
        }
        
        if (is_string($data)) {
            // Remove any potential XSS
            $data = wp_kses($data, $this->get_allowed_html_tags());
            
            // Sanitize based on data type hints
            if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
                return sanitize_email($data);
            } elseif (filter_var($data, FILTER_VALIDATE_URL)) {
                return esc_url_raw($data);
            } elseif (is_numeric($data)) {
                return is_float($data + 0) ? floatval($data) : intval($data);
            } else {
                return sanitize_text_field($data);
            }
        }
        
        return $data;
    }
    
    /**
     * Get allowed HTML tags for content
     */
    private function get_allowed_html_tags() {
        return array(
            'strong' => array(),
            'em' => array(),
            'b' => array(),
            'i' => array(),
            'u' => array(),
            'br' => array(),
            'p' => array(),
            'span' => array('class' => array()),
            'div' => array('class' => array(), 'id' => array()),
        );
    }
    
    /**
     * Validate AJAX requests for booking operations
     */
    public function validate_booking_ajax() {
        // Verify nonce
        if (!check_ajax_referer('crcm_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security verification failed. Please refresh the page and try again.', 'custom-rental-manager'),
                'code' => 'INVALID_NONCE'
            ));
        }
        
        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'custom-rental-manager'),
                'code' => 'INSUFFICIENT_PERMISSIONS'
            ));
        }
        
        // Rate limiting
        if (!$this->check_rate_limit('booking_ajax', 30, MINUTE_IN_SECONDS)) {
            wp_send_json_error(array(
                'message' => __('Too many requests. Please slow down.', 'custom-rental-manager'),
                'code' => 'RATE_LIMIT_EXCEEDED'
            ));
        }
    }
    
    /**
     * Validate AJAX requests for admin operations
     */
    public function validate_admin_ajax() {
        // Verify nonce
        if (!check_ajax_referer('crcm_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security verification failed.', 'custom-rental-manager'),
                'code' => 'INVALID_NONCE'
            ));
        }
        
        // Check admin capabilities
        if (!current_user_can('manage_options') && !current_user_can('crcm_manage_customers')) {
            wp_send_json_error(array(
                'message' => __('Admin privileges required.', 'custom-rental-manager'),
                'code' => 'ADMIN_REQUIRED'
            ));
        }
        
        // Rate limiting
        if (!$this->check_rate_limit('admin_ajax', 20, MINUTE_IN_SECONDS)) {
            wp_send_json_error(array(
                'message' => __('Rate limit exceeded.', 'custom-rental-manager'),
                'code' => 'RATE_LIMIT_EXCEEDED'
            ));
        }
    }
    
    /**
     * Advanced rate limiting system
     */
    public function check_rate_limit($action, $max_requests, $time_window) {
        $user_ip = $this->get_client_ip();
        $user_id = get_current_user_id();
        $key = "crcm_rate_limit_{$action}_{$user_id}_{$user_ip}";
        
        $requests = get_transient($key);
        
        if ($requests === false) {
            // First request in time window
            set_transient($key, 1, $time_window);
            return true;
        }
        
        if ($requests >= $max_requests) {
            // Rate limit exceeded
            $this->log_security_event('rate_limit_exceeded', array(
                'action' => $action,
                'user_id' => $user_id,
                'ip' => $user_ip,
                'requests' => $requests
            ));
            return false;
        }
        
        // Increment counter
        set_transient($key, $requests + 1, $time_window);
        return true;
    }
    
    /**
     * Get client IP address securely
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ip = $_SERVER[$key];
                
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        if (!headers_sent()) {
            // Prevent clickjacking
            header('X-Frame-Options: SAMEORIGIN');
            
            // Prevent MIME type sniffing
            header('X-Content-Type-Options: nosniff');
            
            // XSS Protection
            header('X-XSS-Protection: 1; mode=block');
            
            // Referrer Policy
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // Content Security Policy (basic)
            if (is_admin()) {
                header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' *.wordpress.org *.w.org code.jquery.com; img-src 'self' data: blob: *;");
            }
        }
    }
    
    /**
     * Prevent common attacks
     */
    public function prevent_common_attacks() {
        // Prevent SQL injection attempts
        $suspicious_patterns = array(
            '/union\s+select/i',
            '/drop\s+table/i', 
            '/insert\s+into/i',
            '/delete\s+from/i',
            '/<script[^>]*>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onclick=/i'
        );
        
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $query_string = $_SERVER['QUERY_STRING'] ?? '';
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $request_uri) || preg_match($pattern, $query_string)) {
                $this->log_security_event('suspicious_request', array(
                    'pattern' => $pattern,
                    'uri' => $request_uri,
                    'query' => $query_string,
                    'ip' => $this->get_client_ip()
                ));
                
                wp_die(__('Suspicious activity detected.', 'custom-rental-manager'), 403);
            }
        }
    }
    
    /**
     * Extend allowed HTML for plugin forms
     */
    public function extend_allowed_html($allowed, $context) {
        if ($context === 'crcm_forms') {
            $allowed['input'] = array(
                'type' => true,
                'name' => true,
                'value' => true,
                'id' => true,
                'class' => true,
                'placeholder' => true,
                'required' => true,
                'min' => true,
                'max' => true,
                'step' => true,
                'data-*' => true,
                'autocomplete' => true
            );
            
            $allowed['select'] = array(
                'name' => true,
                'id' => true,
                'class' => true,
                'required' => true,
                'data-*' => true,
                'multiple' => true
            );
            
            $allowed['option'] = array(
                'value' => true,
                'selected' => true
            );
            
            $allowed['textarea'] = array(
                'name' => true,
                'id' => true,
                'class' => true,
                'rows' => true,
                'cols' => true,
                'placeholder' => true,
                'required' => true
            );
            
            $allowed['button'] = array(
                'type' => true,
                'class' => true,
                'id' => true,
                'data-*' => true
            );
        }
        
        return $allowed;
    }
    
    /**
     * Setup object caching for performance
     */
    public function setup_object_caching() {
        // Cache frequently accessed data
        add_action('init', function() {
            // Cache vehicle types
            if (!wp_cache_get('crcm_vehicle_types')) {
                $vehicle_types = array(
                    'auto' => __('Car', 'custom-rental-manager'),
                    'scooter' => __('Scooter', 'custom-rental-manager')
                );
                wp_cache_set('crcm_vehicle_types', $vehicle_types, 'crcm', HOUR_IN_SECONDS);
            }
            
            // Cache locations
            if (!wp_cache_get('crcm_locations')) {
                $locations = array(
                    'ischia_porto' => array(
                        'name' => __('Ischia Porto', 'custom-rental-manager'),
                        'address' => 'Via Iasolino 94, Ischia'
                    ),
                    'forio' => array(
                        'name' => __('Forio', 'custom-rental-manager'),
                        'address' => 'Via Filippo di Lustro 19, Forio'
                    )
                );
                wp_cache_set('crcm_locations', $locations, 'crcm', HOUR_IN_SECONDS);
            }
        });
    }
    
    /**
     * Optimize database queries for vehicles
     */
    public function optimize_vehicle_queries($clauses, $query) {
        if ($query->get('post_type') === 'crcm_vehicle') {
            // Add indexes hint for better performance
            global $wpdb;
            $clauses['join'] .= " USE INDEX (type_status_date)";
        }
        
        return $clauses;
    }
    
    /**
     * Optimize frontend assets
     */
    public function optimize_frontend_assets() {
        // Defer non-critical JavaScript
        add_filter('script_loader_tag', function($tag, $handle) {
            if ($handle === 'crcm-frontend') {
                return str_replace('<script ', '<script defer ', $tag);
            }
            return $tag;
        }, 10, 2);
        
        // Preload critical resources
        add_action('wp_head', function() {
            if (wp_style_is('crcm-frontend', 'enqueued')) {
                echo '<link rel="preload" href="' . wp_styles()->registered['crcm-frontend']->src . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
            }
        });
    }
    
    /**
     * Optimize admin assets
     */
    public function optimize_admin_assets() {
        // Minify inline styles if not already minified
        add_action('admin_print_styles', function() {
            ob_start(function($css) {
                return preg_replace('/\s+/', ' ', $css);
            });
        });
    }
    
    /**
     * Clear related cache when content changes
     */
    public function clear_related_cache($post_id, $post) {
        if (in_array($post->post_type, array('crcm_vehicle', 'crcm_booking'))) {
            // Clear vehicle cache
            wp_cache_delete("crcm_vehicle_data_{$post_id}", 'crcm');
            wp_cache_delete("crcm_vehicle_availability_{$post_id}", 'crcm');
            
            // Clear search results cache
            wp_cache_flush_group('crcm_search');
            
            // Clear stats cache
            wp_cache_delete('crcm_dashboard_stats', 'crcm');
        }
    }
    
    /**
     * Clear user-related cache
     */
    public function clear_user_cache($user_id = null) {
        if ($user_id) {
            wp_cache_delete("crcm_user_bookings_{$user_id}", 'crcm');
            wp_cache_delete("crcm_customer_stats_{$user_id}", 'crcm');
        }
        
        // Clear customer count cache
        wp_cache_delete('crcm_customer_count', 'crcm');
    }
    
    /**
     * Log security events
     */
    private function log_security_event($event_type, $data = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_entry = array(
                'timestamp' => current_time('mysql'),
                'event' => $event_type,
                'user_id' => get_current_user_id(),
                'ip' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'data' => $data
            );
            
            error_log('CRCM Security Event: ' . wp_json_encode($log_entry));
        }
    }
    
    /**
     * Log failed login attempts
     */
    public function log_failed_login($username) {
        $this->log_security_event('login_failed', array(
            'username' => sanitize_user($username)
        ));
    }
    
    /**
     * Log successful logins
     */
    public function log_successful_login($user_login, $user) {
        if (in_array('crcm_customer', $user->roles) || in_array('crcm_manager', $user->roles)) {
            $this->log_security_event('login_success', array(
                'username' => $user_login,
                'user_id' => $user->ID,
                'roles' => $user->roles
            ));
        }
    }
    
    /**
     * Validate plugin environment
     */
    public function validate_plugin_environment() {
        $issues = array();
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            $issues[] = sprintf(__('PHP version %s is not supported. Please upgrade to PHP 8.0 or higher.', 'custom-rental-manager'), PHP_VERSION);
        }
        
        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, '5.6', '<')) {
            $issues[] = sprintf(__('WordPress version %s is not supported. Please upgrade to WordPress 5.6 or higher.', 'custom-rental-manager'), $wp_version);
        }
        
        // Check required extensions
        $required_extensions = array('json', 'mbstring', 'openssl');
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $issues[] = sprintf(__('Required PHP extension "%s" is not loaded.', 'custom-rental-manager'), $ext);
            }
        }
        
        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($memory_limit < 67108864) { // 64MB
            $issues[] = __('PHP memory limit is too low. At least 64MB is recommended.', 'custom-rental-manager');
        }
        
        // If there are critical issues, show admin notice
        if (!empty($issues) && current_user_can('manage_options')) {
            add_action('admin_notices', function() use ($issues) {
                echo '<div class="notice notice-error">';
                echo '<p><strong>' . __('Custom Rental Manager - Environment Issues:', 'custom-rental-manager') . '</strong></p>';
                echo '<ul>';
                foreach ($issues as $issue) {
                    echo '<li>' . esc_html($issue) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            });
        }
    }
    
    /**
     * Get system information for debugging
     */
    public function get_system_info() {
        if (!current_user_can('manage_options')) {
            return array('error' => 'Access denied');
        }
        
        return array(
            'plugin_version' => CRCM_VERSION,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => get_loaded_extensions(),
            'active_plugins' => get_option('active_plugins'),
            'theme' => get_template(),
            'multisite' => is_multisite(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'caching' => array(
                'object_cache' => wp_using_ext_object_cache(),
                'opcache' => function_exists('opcache_get_status') && opcache_get_status()
            )
        );
    }
}

// Initialize WordPress standards compliance
new CRCM_WordPress_Standards();
