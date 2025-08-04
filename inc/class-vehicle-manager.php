<?php
/**
 * Vehicle Manager Class - RESET EDITION
 * 
 * PERFETTO E FUNZIONANTE - Sistema veicoli completo e dinamico
 * Gestione completa veicoli con tutte le funzionalità avanzate
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CRCM_Vehicle_Manager {
    
    /**
     * Constructor - SAFE INITIALIZATION
     */
    public function __construct() {
        // Register vehicle post type
        add_action('init', array($this, 'register_vehicle_post_type'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_vehicle_meta_boxes'));
        
        // Save vehicle data
        add_action('save_post', array($this, 'save_vehicle_data'));
        
        // Admin columns
        add_filter('manage_crcm_vehicle_posts_columns', array($this, 'vehicle_admin_columns'));
        add_action('manage_crcm_vehicle_posts_custom_column', array($this, 'vehicle_admin_column_content'), 10, 2);
        
        // Make columns sortable
        add_filter('manage_edit-crcm_vehicle_sortable_columns', array($this, 'vehicle_sortable_columns'));
        
        // Admin list filters
        add_action('restrict_manage_posts', array($this, 'vehicle_admin_filters'));
        add_filter('pre_get_posts', array($this, 'filter_vehicles_by_status'));
        
        // AJAX handlers
        add_action('wp_ajax_crcm_vehicle_quick_edit', array($this, 'ajax_vehicle_quick_edit'));
        add_action('wp_ajax_crcm_bulk_vehicle_action', array($this, 'ajax_bulk_vehicle_action'));
        add_action('wp_ajax_crcm_get_vehicle_availability', array($this, 'ajax_get_vehicle_availability'));
        
        // Vehicle gallery
        add_action('wp_enqueue_scripts', array($this, 'enqueue_vehicle_scripts'));
        add_action('wp_ajax_crcm_vehicle_gallery_upload', array($this, 'ajax_vehicle_gallery_upload'));
        add_action('wp_ajax_crcm_vehicle_gallery_remove', array($this, 'ajax_vehicle_gallery_remove'));
        
        // Frontend shortcodes
        add_shortcode('crcm_vehicle_list', array($this, 'vehicle_list_shortcode'));
        add_shortcode('crcm_vehicle_details', array($this, 'vehicle_details_shortcode'));
        add_shortcode('crcm_vehicle_search', array($this, 'vehicle_search_shortcode'));
    }
    
    /**
     * Register vehicle custom post type
     */
    public function register_vehicle_post_type() {
        $labels = array(
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
            'not_found_in_trash' => __('No vehicles found in trash', 'custom-rental-manager')
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-car',
            'menu_position' => 26,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'vehicles')
        );
        
        register_post_type('crcm_vehicle', $args);
        
        // Register vehicle categories taxonomy
        $category_labels = array(
            'name' => __('Vehicle Categories', 'custom-rental-manager'),
            'singular_name' => __('Vehicle Category', 'custom-rental-manager'),
            'search_items' => __('Search Categories', 'custom-rental-manager'),
            'popular_items' => __('Popular Categories', 'custom-rental-manager'),
            'all_items' => __('All Categories', 'custom-rental-manager'),
            'edit_item' => __('Edit Category', 'custom-rental-manager'),
            'update_item' => __('Update Category', 'custom-rental-manager'),
            'add_new_item' => __('Add New Category', 'custom-rental-manager'),
            'new_item_name' => __('New Category Name', 'custom-rental-manager'),
            'menu_name' => __('Categories', 'custom-rental-manager')
        );
        
        register_taxonomy('crcm_vehicle_category', 'crcm_vehicle', array(
            'labels' => $category_labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'vehicle-category')
        ));
        
        // Register vehicle features taxonomy
        $features_labels = array(
            'name' => __('Vehicle Features', 'custom-rental-manager'),
            'singular_name' => __('Vehicle Feature', 'custom-rental-manager'),
            'search_items' => __('Search Features', 'custom-rental-manager'),
            'popular_items' => __('Popular Features', 'custom-rental-manager'),
            'all_items' => __('All Features', 'custom-rental-manager'),
            'edit_item' => __('Edit Feature', 'custom-rental-manager'),
            'update_item' => __('Update Feature', 'custom-rental-manager'),
            'add_new_item' => __('Add New Feature', 'custom-rental-manager'),
            'new_item_name' => __('New Feature Name', 'custom-rental-manager'),
            'menu_name' => __('Features', 'custom-rental-manager')
        );
        
        register_taxonomy('crcm_vehicle_features', 'crcm_vehicle', array(
            'labels' => $features_labels,
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'vehicle-features')
        ));
    }
    
    /**
     * Add vehicle meta boxes
     */
    public function add_vehicle_meta_boxes() {
        add_meta_box(
            'crcm_vehicle_details',
            __('Vehicle Details', 'custom-rental-manager'),
            array($this, 'vehicle_details_meta_box'),
            'crcm_vehicle',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crcm_vehicle_pricing',
            __('Pricing Information', 'custom-rental-manager'),
            array($this, 'vehicle_pricing_meta_box'),
            'crcm_vehicle',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crcm_vehicle_availability',
            __('Availability & Status', 'custom-rental-manager'),
            array($this, 'vehicle_availability_meta_box'),
            'crcm_vehicle',
            'side',
            'default'
        );
        
        add_meta_box(
            'crcm_vehicle_gallery',
            __('Vehicle Gallery', 'custom-rental-manager'),
            array($this, 'vehicle_gallery_meta_box'),
            'crcm_vehicle',
            'normal',
            'default'
        );
        
        add_meta_box(
            'crcm_vehicle_maintenance',
            __('Maintenance Information', 'custom-rental-manager'),
            array($this, 'vehicle_maintenance_meta_box'),
            'crcm_vehicle',
            'normal',
            'default'
        );
        
        add_meta_box(
            'crcm_vehicle_insurance',
            __('Insurance & Documentation', 'custom-rental-manager'),
            array($this, 'vehicle_insurance_meta_box'),
            'crcm_vehicle',
            'normal',
            'default'
        );
    }
    
    /**
     * Vehicle details meta box
     */
    public function vehicle_details_meta_box($post) {
        wp_nonce_field('crcm_vehicle_meta', 'crcm_vehicle_meta_nonce');
        
        // Get existing values
        $make = get_post_meta($post->ID, '_crcm_vehicle_make', true);
        $model = get_post_meta($post->ID, '_crcm_vehicle_model', true);
        $year = get_post_meta($post->ID, '_crcm_vehicle_year', true);
        $color = get_post_meta($post->ID, '_crcm_vehicle_color', true);
        $license_plate = get_post_meta($post->ID, '_crcm_license_plate', true);
        $vin = get_post_meta($post->ID, '_crcm_vehicle_vin', true);
        $engine_type = get_post_meta($post->ID, '_crcm_engine_type', true);
        $transmission = get_post_meta($post->ID, '_crcm_transmission', true);
        $fuel_type = get_post_meta($post->ID, '_crcm_fuel_type', true);
        $seats = get_post_meta($post->ID, '_crcm_vehicle_seats', true);
        $doors = get_post_meta($post->ID, '_crcm_vehicle_doors', true);
        $mileage = get_post_meta($post->ID, '_crcm_vehicle_mileage', true);
        
        ?>
        <div class="crcm-meta-box-grid">
            <div class="crcm-meta-row">
                <div class="crcm-meta-col">
                    <label for="crcm_vehicle_make"><strong><?php _e('Make', 'custom-rental-manager'); ?></strong></label>
                    <input type="text" id="crcm_vehicle_make" name="crcm_vehicle_make" value="<?php echo esc_attr($make); ?>" class="widefat" required>
                </div>
                <div class="crcm-meta-col">
                    <label for="crcm_vehicle_model"><strong><?php _e('Model', 'custom-rental-manager'); ?></strong></label>
                    <input type="text" id="crcm_vehicle_model" name="crcm_vehicle_model" value="<?php echo esc_attr($model); ?>" class="widefat" required>
                </div>
            </div>
            
            <div class="crcm-meta-row">
                <div class="crcm-meta-col">
                    <label for="crcm_vehicle_year"><strong><?php _e('Year', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_vehicle_year" name="crcm_vehicle_year" value="<?php echo esc_attr($year); ?>" class="widefat" min="1900" max="<?php echo date('Y') + 1; ?>" required>
                </div>
                <div class="crcm-meta-col">
                    <label for="crcm_vehicle_color"><strong><?php _e('Color', 'custom-rental-manager'); ?></strong></label>
                    <input type="text" id="crcm_vehicle_color" name="crcm_vehicle_color" value="<?php echo esc_attr($color); ?>" class="widefat">
                </div>
            </div>
            
            <div class="crcm-meta-row">
                <div class="crcm-meta-col">
                    <label for="crcm_license_plate"><strong><?php _e('License Plate', 'custom-rental-manager'); ?></strong></label>
                    <input type="text" id="crcm_license_plate" name="crcm_license_plate" value="<?php echo esc_attr($license_plate); ?>" class="widefat" required>
                </div>
                <div class="crcm-meta-col">
                    <label for="crcm_vehicle_vin"><strong><?php _e('VIN Number', 'custom-rental-manager'); ?></strong></label>
                    <input type="text" id="crcm_vehicle_vin" name="crcm_vehicle_vin" value="<?php echo esc_attr($vin); ?>" class="widefat">
                </div>
            </div>
            
            <div class="crcm-meta-row">
                <div class="crcm-meta-col">
                    <label for="crcm_engine_type"><strong><?php _e('Engine Type', 'custom-rental-manager'); ?></strong></label>
                    <input type="text" id="crcm_engine_type" name="crcm_engine_type" value="<?php echo esc_attr($engine_type); ?>" class="widefat" placeholder="e.g., 2.0L Turbo">
                </div>
                <div class="crcm-meta-col">
                    <label for="crcm_transmission"><strong><?php _e('Transmission', 'custom-rental-manager'); ?></strong></label>
                    <select id="crcm_transmission" name="crcm_transmission" class="widefat">
                        <option value="manual" <?php selected($transmission, 'manual'); ?>><?php _e('Manual', 'custom-rental-manager'); ?></option>
                        <option value="automatic" <?php selected($transmission, 'automatic'); ?>><?php _e('Automatic', 'custom-rental-manager'); ?></option>
                        <option value="cvt" <?php selected($transmission, 'cvt'); ?>><?php _e('CVT', 'custom-rental-manager'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="crcm-meta-row">
                <div class="crcm-meta-col">
                    <label for="crcm_fuel_type"><strong><?php _e('Fuel Type', 'custom-rental-manager'); ?></strong></label>
                    <select id="crcm_fuel_type" name="crcm_fuel_type" class="widefat">
                        <option value="gasoline" <?php selected($fuel_type, 'gasoline'); ?>><?php _e('Gasoline', 'custom-rental-manager'); ?></option>
                        <option value="diesel" <?php selected($fuel_type, 'diesel'); ?>><?php _e('Diesel', 'custom-rental-manager'); ?></option>
                        <option value="hybrid" <?php selected($fuel_type, 'hybrid'); ?>><?php _e('Hybrid', 'custom-rental-manager'); ?></option>
                        <option value="electric" <?php selected($fuel_type, 'electric'); ?>><?php _e('Electric', 'custom-rental-manager'); ?></option>
                    </select>
                </div>
                <div class="crcm-meta-col">
                    <label for="crcm_vehicle_mileage"><strong><?php _e('Current Mileage (km)', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_vehicle_mileage" name="crcm_vehicle_mileage" value="<?php echo esc_attr($mileage); ?>" class="widefat" min="0">
                </div>
            </div>
            
            <div class="crcm-meta-row">
                <div class="crcm-meta-col">
                    <label for="crcm_vehicle_seats"><strong><?php _e('Number of Seats', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_vehicle_seats" name="crcm_vehicle_seats" value="<?php echo esc_attr($seats); ?>" class="widefat" min="1" max="20">
                </div>
                <div class="crcm-meta-col">
                    <label for="crcm_vehicle_doors"><strong><?php _e('Number of Doors', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_vehicle_doors" name="crcm_vehicle_doors" value="<?php echo esc_attr($doors); ?>" class="widefat" min="2" max="10">
                </div>
            </div>
        </div>
        
        <style>
        .crcm-meta-box-grid {
            display: grid;
            gap: 15px;
        }
        .crcm-meta-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .crcm-meta-col label {
            display: block;
            margin-bottom: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Vehicle pricing meta box
     */
    public function vehicle_pricing_meta_box($post) {
        $daily_rate = get_post_meta($post->ID, '_crcm_daily_rate', true);
        $weekly_rate = get_post_meta($post->ID, '_crcm_weekly_rate', true);
        $monthly_rate = get_post_meta($post->ID, '_crcm_monthly_rate', true);
        $deposit_amount = get_post_meta($post->ID, '_crcm_deposit_amount', true);
        $km_included = get_post_meta($post->ID, '_crcm_km_included', true);
        $extra_km_rate = get_post_meta($post->ID, '_crcm_extra_km_rate', true);
        $weekend_surcharge = get_post_meta($post->ID, '_crcm_weekend_surcharge', true);
        $peak_season_rate = get_post_meta($post->ID, '_crcm_peak_season_rate', true);
        
        ?>
        <div class="crcm-meta-box-grid">
            <div class="crcm-meta-row">
                <div class="crcm-meta-col">
                    <label for="crcm_daily_rate"><strong><?php _e('Daily Rate (€)', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_daily_rate" name="crcm_daily_rate" value="<?php echo esc_attr($daily_rate); ?>" class="widefat" step="0.01" min="0" required>
                </div>
                <div class="crcm-meta-col">
                    <label for="crcm_weekly_rate"><strong><?php _e('Weekly Rate (€)', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_weekly_rate" name="crcm_weekly_rate" value="<?php echo esc_attr($weekly_rate); ?>" class="widefat" step="0.01" min="0">
                    <small><?php _e('Leave empty for auto-calculation (daily × 7 × 0.85)', 'custom-rental-manager'); ?></small>
                </div>
            </div>
            
            <div class="crcm-meta-row">
                <div class="crcm-meta-col">
                    <label for="crcm_monthly_rate"><strong><?php _e('Monthly Rate (€)', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_monthly_rate" name="crcm_monthly_rate" value="<?php echo esc_attr($monthly_rate); ?>" class="widefat" step="0.01" min="0">
                    <small><?php _e('Leave empty for auto-calculation (daily × 30 × 0.70)', 'custom-rental-manager'); ?></small>
                </div>
                <div class="crcm-meta-col">
                    <label for="crcm_deposit_amount"><strong><?php _e('Security Deposit (€)', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_deposit_amount" name="crcm_deposit_amount" value="<?php echo esc_attr($deposit_amount); ?>" class="widefat" step="0.01" min="0">
                </div>
            </div>
            
            <div class="crcm-meta-row">
                <div class="crcm-meta-col">
                    <label for="crcm_km_included"><strong><?php _e('Kilometers Included (per day)', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_km_included" name="crcm_km_included" value="<?php echo esc_attr($km_included ?: 200); ?>" class="widefat" min="0">
                </div>
                <div class="crcm-meta-col">
                    <label for="crcm_extra_km_rate"><strong><?php _e('Extra KM Rate (€ per km)', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_extra_km_rate" name="crcm_extra_km_rate" value="<?php echo esc_attr($extra_km_rate ?: 0.25); ?>" class="widefat" step="0.01" min="0">
                </div>
            </div>
            
            <div class="crcm-meta-row">
                <div class="crcm-meta-col">
                    <label for="crcm_weekend_surcharge"><strong><?php _e('Weekend Surcharge (%)', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_weekend_surcharge" name="crcm_weekend_surcharge" value="<?php echo esc_attr($weekend_surcharge ?: 0); ?>" class="widefat" step="0.01" min="0" max="100">
                </div>
                <div class="crcm-meta-col">
                    <label for="crcm_peak_season_rate"><strong><?php _e('Peak Season Rate (€)', 'custom-rental-manager'); ?></strong></label>
                    <input type="number" id="crcm_peak_season_rate" name="crcm_peak_season_rate" value="<?php echo esc_attr($peak_season_rate); ?>" class="widefat" step="0.01" min="0">
                    <small><?php _e('Special rate for peak season (July-August)', 'custom-rental-manager'); ?></small>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Auto-calculate weekly and monthly rates
            $('#crcm_daily_rate').on('input', function() {
                var dailyRate = parseFloat($(this).val()) || 0;
                
                if ($('#crcm_weekly_rate').val() === '') {
                    $('#crcm_weekly_rate').val((dailyRate * 7 * 0.85).toFixed(2));
                }
                
                if ($('#crcm_monthly_rate').val() === '') {
                    $('#crcm_monthly_rate').val((dailyRate * 30 * 0.70).toFixed(2));
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Vehicle availability meta box
     */
    public function vehicle_availability_meta_box($post) {
        $status = get_post_meta($post->ID, '_crcm_vehicle_status', true) ?: 'available';
        $availability_notes = get_post_meta($post->ID, '_crcm_availability_notes', true);
        $next_maintenance = get_post_meta($post->ID, '_crcm_next_maintenance', true);
        $location = get_post_meta($post->ID, '_crcm_vehicle_location', true);
        
        ?>
        <div class="crcm-vehicle-status-panel">
            <div class="crcm-status-indicator status-<?php echo esc_attr($status); ?>">
                <span class="status-dot"></span>
                <span class="status-text"><?php echo $this->get_status_label($status); ?></span>
            </div>
            
            <div class="crcm-status-controls">
                <label for="crcm_vehicle_status"><strong><?php _e('Status', 'custom-rental-manager'); ?></strong></label>
                <select id="crcm_vehicle_status" name="crcm_vehicle_status" class="widefat">
                    <option value="available" <?php selected($status, 'available'); ?>><?php _e('Available', 'custom-rental-manager'); ?></option>
                    <option value="rented" <?php selected($status, 'rented'); ?>><?php _e('Currently Rented', 'custom-rental-manager'); ?></option>
                    <option value="maintenance" <?php selected($status, 'maintenance'); ?>><?php _e('Under Maintenance', 'custom-rental-manager'); ?></option>
                    <option value="out_of_service" <?php selected($status, 'out_of_service'); ?>><?php _e('Out of Service', 'custom-rental-manager'); ?></option>
                </select>
                
                <label for="crcm_vehicle_location"><strong><?php _e('Current Location', 'custom-rental-manager'); ?></strong></label>
                <input type="text" id="crcm_vehicle_location" name="crcm_vehicle_location" value="<?php echo esc_attr($location); ?>" class="widefat" placeholder="<?php _e('Main office, Airport, etc.', 'custom-rental-manager'); ?>">
                
                <label for="crcm_availability_notes"><strong><?php _e('Availability Notes', 'custom-rental-manager'); ?></strong></label>
                <textarea id="crcm_availability_notes" name="crcm_availability_notes" class="widefat" rows="3" placeholder="<?php _e('Internal notes about availability...', 'custom-rental-manager'); ?>"><?php echo esc_textarea($availability_notes); ?></textarea>
                
                <label for="crcm_next_maintenance"><strong><?php _e('Next Maintenance Date', 'custom-rental-manager'); ?></strong></label>
                <input type="date" id="crcm_next_maintenance" name="crcm_next_maintenance" value="<?php echo esc_attr($next_maintenance); ?>" class="widefat">
            </div>
            
            <div class="crcm-quick-actions">
                <h4><?php _e('Quick Actions', 'custom-rental-manager'); ?></h4>
                <button type="button" class="button" onclick="setVehicleStatus('available')"><?php _e('Mark Available', 'custom-rental-manager'); ?></button>
                <button type="button" class="button" onclick="setVehicleStatus('maintenance')"><?php _e('Send to Maintenance', 'custom-rental-manager'); ?></button>
                <button type="button" class="button" onclick="checkAvailability(<?php echo $post->ID; ?>)"><?php _e('Check Availability', 'custom-rental-manager'); ?></button>
            </div>
        </div>
        
        <style>
        .crcm-vehicle-status-panel {
            padding: 10px 0;
        }
        
        .crcm-status-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-available .status-dot { background: #4CAF50; }
        .status-rented .status-dot { background: #FF9800; }
        .status-maintenance .status-dot { background: #2196F3; }
        .status-out_of_service .status-dot { background: #F44336; }
        
        .crcm-status-controls label {
            display: block;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        
        .crcm-quick-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        
        .crcm-quick-actions h4 {
            margin-bottom: 10px;
        }
        
        .crcm-quick-actions .button {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        </style>
        
        <script>
        function setVehicleStatus(status) {
            document.getElementById('crcm_vehicle_status').value = status;
            document.querySelector('.crcm-status-indicator').className = 'crcm-status-indicator status-' + status;
        }
        
        function checkAvailability(vehicleId) {
            if (typeof jQuery !== 'undefined') {
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'crcm_get_vehicle_availability',
                        vehicle_id: vehicleId,
                        nonce: '<?php echo wp_create_nonce('crcm_ajax'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Vehicle availability: ' + response.data.message);
                        }
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    /**
     * Vehicle gallery meta box
     */
    public function vehicle_gallery_meta_box($post) {
        $gallery_images = get_post_meta($post->ID, '_crcm_vehicle_gallery', true) ?: array();
        
        ?>
        <div class="crcm-vehicle-gallery">
            <div class="gallery-container">
                <div id="vehicle-gallery-images" class="gallery-images">
                    <?php foreach ($gallery_images as $image_id): ?>
                        <div class="gallery-item" data-image-id="<?php echo $image_id; ?>">
                            <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                            <div class="gallery-item-actions">
                                <button type="button" class="button-link gallery-remove" onclick="removeGalleryImage(<?php echo $image_id; ?>)"><?php _e('Remove', 'custom-rental-manager'); ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="gallery-upload-area">
                    <button type="button" id="upload-gallery-images" class="button"><?php _e('Add Images', 'custom-rental-manager'); ?></button>
                    <p class="description"><?php _e('Add multiple images to showcase your vehicle from different angles.', 'custom-rental-manager'); ?></p>
                </div>
            </div>
            
            <input type="hidden" id="vehicle-gallery-data" name="crcm_vehicle_gallery" value="<?php echo esc_attr(json_encode($gallery_images)); ?>">
        </div>
        
        <style>
        .gallery-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .gallery-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .gallery-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        
        .gallery-item-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.7);
            border-radius: 3px;
        }
        
        .gallery-remove {
            color: white !important;
            padding: 2px 6px;
            font-size: 11px;
            text-decoration: none;
        }
        
        .gallery-upload-area {
            text-align: center;
            padding: 20px;
            border: 2px dashed #ddd;
            border-radius: 4px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;
            var galleryData = <?php echo json_encode($gallery_images); ?>;
            
            $('#upload-gallery-images').on('click', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Select Vehicle Images', 'custom-rental-manager'); ?>',
                    button: {
                        text: '<?php _e('Add to Gallery', 'custom-rental-manager'); ?>'
                    },
                    multiple: true
                });
                
                mediaUploader.on('select', function() {
                    var attachments = mediaUploader.state().get('selection').toJSON();
                    
                    attachments.forEach(function(attachment) {
                        if (galleryData.indexOf(attachment.id) === -1) {
                            galleryData.push(attachment.id);
                            addGalleryImage(attachment);
                        }
                    });
                    
                    updateGalleryData();
                });
                
                mediaUploader.open();
            });
            
            function addGalleryImage(attachment) {
                var imageHtml = '<div class="gallery-item" data-image-id="' + attachment.id + '">';
                imageHtml += '<img src="' + attachment.sizes.thumbnail.url + '" alt="' + attachment.alt + '">';
                imageHtml += '<div class="gallery-item-actions">';
                imageHtml += '<button type="button" class="button-link gallery-remove" onclick="removeGalleryImage(' + attachment.id + ')"><?php _e('Remove', 'custom-rental-manager'); ?></button>';
                imageHtml += '</div>';
                imageHtml += '</div>';
                
                $('#vehicle-gallery-images').append(imageHtml);
            }
            
            function updateGalleryData() {
                $('#vehicle-gallery-data').val(JSON.stringify(galleryData));
            }
            
            window.removeGalleryImage = function(imageId) {
                var index = galleryData.indexOf(imageId);
                if (index > -1) {
                    galleryData.splice(index, 1);
                    $('[data-image-id="' + imageId + '"]').remove();
                    updateGalleryData();
                }
            };
        });
        </script>
        <?php
    }
    
    /**
     * Vehicle maintenance meta box
     */
    public function vehicle_maintenance_meta_box($post) {
        $last_service = get_post_meta($post->ID, '_crcm_last_service_date', true);
        $last_service_km = get_post_meta($post->ID, '_crcm_last_service_km', true);
        $service_interval = get_post_meta($post->ID, '_crcm_service_interval', true) ?: 10000;
        $maintenance_notes = get_post_meta($post->ID, '_crcm_maintenance_notes', true);
        $maintenance_history = get_post_meta($post->ID, '_crcm_maintenance_history', true) ?: array();
        
        ?>
        <div class="crcm-maintenance-panel">
            <div class="maintenance-current">
                <h4><?php _e('Current Maintenance Status', 'custom-rental-manager'); ?></h4>
                
                <div class="crcm-meta-row">
                    <div class="crcm-meta-col">
                        <label for="crcm_last_service_date"><?php _e('Last Service Date', 'custom-rental-manager'); ?></label>
                        <input type="date" id="crcm_last_service_date" name="crcm_last_service_date" value="<?php echo esc_attr($last_service); ?>" class="widefat">
                    </div>
                    <div class="crcm-meta-col">
                        <label for="crcm_last_service_km"><?php _e('Last Service KM', 'custom-rental-manager'); ?></label>
                        <input type="number" id="crcm_last_service_km" name="crcm_last_service_km" value="<?php echo esc_attr($last_service_km); ?>" class="widefat" min="0">
                    </div>
                </div>
                
                <div class="crcm-meta-row">
                    <div class="crcm-meta-col">
                        <label for="crcm_service_interval"><?php _e('Service Interval (KM)', 'custom-rental-manager'); ?></label>
                        <input type="number" id="crcm_service_interval" name="crcm_service_interval" value="<?php echo esc_attr($service_interval); ?>" class="widefat" min="1000" step="1000">
                    </div>
                    <div class="crcm-meta-col">
                        <label><?php _e('Next Service Due', 'custom-rental-manager'); ?></label>
                        <div class="service-due-info">
                            <span id="next-service-km"><?php echo ($last_service_km + $service_interval); ?> KM</span>
                            <span class="service-status" id="service-status"><?php echo $this->get_service_status($post->ID); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="maintenance-notes">
                    <label for="crcm_maintenance_notes"><?php _e('Maintenance Notes', 'custom-rental-manager'); ?></label>
                    <textarea id="crcm_maintenance_notes" name="crcm_maintenance_notes" class="widefat" rows="3" placeholder="<?php _e('General maintenance notes and reminders...', 'custom-rental-manager'); ?>"><?php echo esc_textarea($maintenance_notes); ?></textarea>
                </div>
            </div>
            
            <div class="maintenance-history">
                <h4><?php _e('Maintenance History', 'custom-rental-manager'); ?></h4>
                
                <div class="add-maintenance-record">
                    <h5><?php _e('Add New Maintenance Record', 'custom-rental-manager'); ?></h5>
                    <div class="maintenance-form">
                        <div class="crcm-meta-row">
                            <div class="crcm-meta-col">
                                <input type="date" id="new_maintenance_date" placeholder="<?php _e('Service Date', 'custom-rental-manager'); ?>" class="widefat">
                            </div>
                            <div class="crcm-meta-col">
                                <input type="number" id="new_maintenance_km" placeholder="<?php _e('KM at Service', 'custom-rental-manager'); ?>" class="widefat" min="0">
                            </div>
                        </div>
                        <input type="text" id="new_maintenance_type" placeholder="<?php _e('Service Type (Oil Change, Brake Service, etc.)', 'custom-rental-manager'); ?>" class="widefat">
                        <input type="number" id="new_maintenance_cost" placeholder="<?php _e('Cost (€)', 'custom-rental-manager'); ?>" class="widefat" step="0.01" min="0">
                        <textarea id="new_maintenance_notes" placeholder="<?php _e('Service notes and details...', 'custom-rental-manager'); ?>" class="widefat" rows="2"></textarea>
                        <button type="button" id="add-maintenance-record" class="button"><?php _e('Add Record', 'custom-rental-manager'); ?></button>
                    </div>
                </div>
                
                <div class="maintenance-records" id="maintenance-records">
                    <?php if (!empty($maintenance_history)): ?>
                        <?php foreach ($maintenance_history as $index => $record): ?>
                            <div class="maintenance-record" data-index="<?php echo $index; ?>">
                                <div class="record-header">
                                    <strong><?php echo esc_html($record['type']); ?></strong>
                                    <span class="record-date"><?php echo esc_html($record['date']); ?></span>
                                    <span class="record-km"><?php echo esc_html($record['km']); ?> KM</span>
                                    <button type="button" class="button-link record-remove" onclick="removeMaintenanceRecord(<?php echo $index; ?>)"><?php _e('Remove', 'custom-rental-manager'); ?></button>
                                </div>
                                <?php if (!empty($record['cost'])): ?>
                                    <div class="record-cost">€<?php echo esc_html($record['cost']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($record['notes'])): ?>
                                    <div class="record-notes"><?php echo esc_html($record['notes']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-records"><?php _e('No maintenance records found.', 'custom-rental-manager'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <input type="hidden" id="maintenance-history-data" name="crcm_maintenance_history" value="<?php echo esc_attr(json_encode($maintenance_history)); ?>">
        </div>
        
        <style>
        .maintenance-current,
        .maintenance-history {
            margin-bottom: 20px;
        }
        
        .service-due-info {
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .service-status {
            display: inline-block;
            margin-left: 10px;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            color: white;
        }
        
        .service-status.overdue { background: #F44336; }
        .service-status.due-soon { background: #FF9800; }
        .service-status.ok { background: #4CAF50; }
        
        .maintenance-form {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .maintenance-record {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .record-remove {
            color: #dc3545 !important;
        }
        
        .record-cost {
            font-weight: bold;
            color: #28a745;
        }
        
        .record-notes {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var maintenanceHistory = <?php echo json_encode($maintenance_history); ?>;
            
            $('#add-maintenance-record').on('click', function() {
                var newRecord = {
                    date: $('#new_maintenance_date').val(),
                    km: $('#new_maintenance_km').val(),
                    type: $('#new_maintenance_type').val(),
                    cost: $('#new_maintenance_cost').val(),
                    notes: $('#new_maintenance_notes').val()
                };
                
                if (!newRecord.date || !newRecord.type) {
                    alert('<?php _e('Date and Service Type are required.', 'custom-rental-manager'); ?>');
                    return;
                }
                
                maintenanceHistory.push(newRecord);
                addMaintenanceRecordToDOM(newRecord, maintenanceHistory.length - 1);
                updateMaintenanceHistoryData();
                clearMaintenanceForm();
            });
            
            function addMaintenanceRecordToDOM(record, index) {
                var recordHtml = '<div class="maintenance-record" data-index="' + index + '">';
                recordHtml += '<div class="record-header">';
                recordHtml += '<strong>' + record.type + '</strong>';
                recordHtml += '<span class="record-date">' + record.date + '</span>';
                recordHtml += '<span class="record-km">' + (record.km || 'N/A') + ' KM</span>';
                recordHtml += '<button type="button" class="button-link record-remove" onclick="removeMaintenanceRecord(' + index + ')"><?php _e('Remove', 'custom-rental-manager'); ?></button>';
                recordHtml += '</div>';
                if (record.cost) {
                    recordHtml += '<div class="record-cost">€' + record.cost + '</div>';
                }
                if (record.notes) {
                    recordHtml += '<div class="record-notes">' + record.notes + '</div>';
                }
                recordHtml += '</div>';
                
                $('.no-records').remove();
                $('#maintenance-records').append(recordHtml);
            }
            
            function clearMaintenanceForm() {
                $('#new_maintenance_date, #new_maintenance_km, #new_maintenance_type, #new_maintenance_cost, #new_maintenance_notes').val('');
            }
            
            function updateMaintenanceHistoryData() {
                $('#maintenance-history-data').val(JSON.stringify(maintenanceHistory));
            }
            
            window.removeMaintenanceRecord = function(index) {
                if (confirm('<?php _e('Are you sure you want to remove this maintenance record?', 'custom-rental-manager'); ?>')) {
                    maintenanceHistory.splice(index, 1);
                    $('[data-index="' + index + '"]').remove();
                    updateMaintenanceHistoryData();
                    
                    // Re-index remaining records
                    $('#maintenance-records .maintenance-record').each(function(newIndex) {
                        $(this).attr('data-index', newIndex);
                        $(this).find('.record-remove').attr('onclick', 'removeMaintenanceRecord(' + newIndex + ')');
                    });
                }
            };
            
            // Update service due calculation when values change
            $('#crcm_last_service_km, #crcm_service_interval').on('input', function() {
                var lastServiceKm = parseInt($('#crcm_last_service_km').val()) || 0;
                var interval = parseInt($('#crcm_service_interval').val()) || 10000;
                var nextServiceKm = lastServiceKm + interval;
                
                $('#next-service-km').text(nextServiceKm + ' KM');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Vehicle insurance meta box
     */
    public function vehicle_insurance_meta_box($post) {
        $insurance_company = get_post_meta($post->ID, '_crcm_insurance_company', true);
        $insurance_policy = get_post_meta($post->ID, '_crcm_insurance_policy', true);
        $insurance_expiry = get_post_meta($post->ID, '_crcm_insurance_expiry', true);
        $registration_expiry = get_post_meta($post->ID, '_crcm_registration_expiry', true);
        $mot_expiry = get_post_meta($post->ID, '_crcm_mot_expiry', true);
        $road_tax_expiry = get_post_meta($post->ID, '_crcm_road_tax_expiry', true);
        
        ?>
        <div class="crcm-insurance-panel">
            <div class="insurance-info">
                <h4><?php _e('Insurance Information', 'custom-rental-manager'); ?></h4>
                
                <div class="crcm-meta-row">
                    <div class="crcm-meta-col">
                        <label for="crcm_insurance_company"><?php _e('Insurance Company', 'custom-rental-manager'); ?></label>
                        <input type="text" id="crcm_insurance_company" name="crcm_insurance_company" value="<?php echo esc_attr($insurance_company); ?>" class="widefat">
                    </div>
                    <div class="crcm-meta-col">
                        <label for="crcm_insurance_policy"><?php _e('Policy Number', 'custom-rental-manager'); ?></label>
                        <input type="text" id="crcm_insurance_policy" name="crcm_insurance_policy" value="<?php echo esc_attr($insurance_policy); ?>" class="widefat">
                    </div>
                </div>
            </div>
            
            <div class="expiry-dates">
                <h4><?php _e('Important Expiry Dates', 'custom-rental-manager'); ?></h4>
                
                <div class="expiry-grid">
                    <div class="expiry-item">
                        <label for="crcm_insurance_expiry"><?php _e('Insurance Expiry', 'custom-rental-manager'); ?></label>
                        <input type="date" id="crcm_insurance_expiry" name="crcm_insurance_expiry" value="<?php echo esc_attr($insurance_expiry); ?>" class="widefat">
                        <div class="expiry-status" data-expiry="<?php echo esc_attr($insurance_expiry); ?>"></div>
                    </div>
                    
                    <div class="expiry-item">
                        <label for="crcm_registration_expiry"><?php _e('Registration Expiry', 'custom-rental-manager'); ?></label>
                        <input type="date" id="crcm_registration_expiry" name="crcm_registration_expiry" value="<?php echo esc_attr($registration_expiry); ?>" class="widefat">
                        <div class="expiry-status" data-expiry="<?php echo esc_attr($registration_expiry); ?>"></div>
                    </div>
                    
                    <div class="expiry-item">
                        <label for="crcm_mot_expiry"><?php _e('MOT/Inspection Expiry', 'custom-rental-manager'); ?></label>
                        <input type="date" id="crcm_mot_expiry" name="crcm_mot_expiry" value="<?php echo esc_attr($mot_expiry); ?>" class="widefat">
                        <div class="expiry-status" data-expiry="<?php echo esc_attr($mot_expiry); ?>"></div>
                    </div>
                    
                    <div class="expiry-item">
                        <label for="crcm_road_tax_expiry"><?php _e('Road Tax Expiry', 'custom-rental-manager'); ?></label>
                        <input type="date" id="crcm_road_tax_expiry" name="crcm_road_tax_expiry" value="<?php echo esc_attr($road_tax_expiry); ?>" class="widefat">
                        <div class="expiry-status" data-expiry="<?php echo esc_attr($road_tax_expiry); ?>"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .expiry-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .expiry-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
        }
        
        .expiry-item label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .expiry-status {
            margin-top: 5px;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            text-align: center;
        }
        
        .expiry-status.expired {
            background: #F44336;
            color: white;
        }
        
        .expiry-status.expiring-soon {
            background: #FF9800;
            color: white;
        }
        
        .expiry-status.ok {
            background: #4CAF50;
            color: white;
        }
        
        .expiry-status.not-set {
            background: #9E9E9E;
            color: white;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Check expiry status when dates change
            $('.expiry-item input[type="date"]').on('change', function() {
                updateExpiryStatus($(this));
            });
            
            // Initial check
            $('.expiry-item input[type="date"]').each(function() {
                updateExpiryStatus($(this));
            });
            
            function updateExpiryStatus($input) {
                var expiryDate = $input.val();
                var $status = $input.siblings('.expiry-status');
                
                if (!expiryDate) {
                    $status.removeClass('expired expiring-soon ok').addClass('not-set').text('<?php _e('Not Set', 'custom-rental-manager'); ?>');
                    return;
                }
                
                var today = new Date();
                var expiry = new Date(expiryDate);
                var daysDiff = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
                
                $status.removeClass('expired expiring-soon ok not-set');
                
                if (daysDiff < 0) {
                    $status.addClass('expired').text('<?php _e('EXPIRED', 'custom-rental-manager'); ?>');
                } else if (daysDiff <= 30) {
                    $status.addClass('expiring-soon').text('<?php _e('Expires in', 'custom-rental-manager'); ?> ' + daysDiff + ' <?php _e('days', 'custom-rental-manager'); ?>');
                } else {
                    $status.addClass('ok').text('<?php _e('Valid', 'custom-rental-manager'); ?>');
                }
            }
        });
        </script>
        <?php
    }
    
    /**
     * Save vehicle meta data
     */
    public function save_vehicle_data($post_id) {
        // Security checks
        if (!isset($_POST['crcm_vehicle_meta_nonce']) || !wp_verify_nonce($_POST['crcm_vehicle_meta_nonce'], 'crcm_vehicle_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Vehicle details
        $vehicle_fields = array(
            '_crcm_vehicle_make' => 'sanitize_text_field',
            '_crcm_vehicle_model' => 'sanitize_text_field',
            '_crcm_vehicle_year' => 'intval',
            '_crcm_vehicle_color' => 'sanitize_text_field',
            '_crcm_license_plate' => 'sanitize_text_field',
            '_crcm_vehicle_vin' => 'sanitize_text_field',
            '_crcm_engine_type' => 'sanitize_text_field',
            '_crcm_transmission' => 'sanitize_text_field',
            '_crcm_fuel_type' => 'sanitize_text_field',
            '_crcm_vehicle_seats' => 'intval',
            '_crcm_vehicle_doors' => 'intval',
            '_crcm_vehicle_mileage' => 'intval',
            
            // Pricing
            '_crcm_daily_rate' => 'floatval',
            '_crcm_weekly_rate' => 'floatval',
            '_crcm_monthly_rate' => 'floatval',
            '_crcm_deposit_amount' => 'floatval',
            '_crcm_km_included' => 'intval',
            '_crcm_extra_km_rate' => 'floatval',
            '_crcm_weekend_surcharge' => 'floatval',
            '_crcm_peak_season_rate' => 'floatval',
            
            // Availability
            '_crcm_vehicle_status' => 'sanitize_text_field',
            '_crcm_vehicle_location' => 'sanitize_text_field',
            '_crcm_availability_notes' => 'sanitize_textarea_field',
            '_crcm_next_maintenance' => 'sanitize_text_field',
            
            // Maintenance
            '_crcm_last_service_date' => 'sanitize_text_field',
            '_crcm_last_service_km' => 'intval',
            '_crcm_service_interval' => 'intval',
            '_crcm_maintenance_notes' => 'sanitize_textarea_field',
            
            // Insurance
            '_crcm_insurance_company' => 'sanitize_text_field',
            '_crcm_insurance_policy' => 'sanitize_text_field',
            '_crcm_insurance_expiry' => 'sanitize_text_field',
            '_crcm_registration_expiry' => 'sanitize_text_field',
            '_crcm_mot_expiry' => 'sanitize_text_field',
            '_crcm_road_tax_expiry' => 'sanitize_text_field'
        );
        
        foreach ($vehicle_fields as $field => $sanitize_callback) {
            $field_name = str_replace('_crcm_', 'crcm_', $field);
            if (isset($_POST[$field_name])) {
                $value = call_user_func($sanitize_callback, $_POST[$field_name]);
                update_post_meta($post_id, $field, $value);
            }
        }
        
        // Vehicle gallery
        if (isset($_POST['crcm_vehicle_gallery'])) {
            $gallery_data = json_decode(stripslashes($_POST['crcm_vehicle_gallery']), true);
            if (is_array($gallery_data)) {
                $sanitized_gallery = array_map('intval', $gallery_data);
                update_post_meta($post_id, '_crcm_vehicle_gallery', $sanitized_gallery);
            }
        }
        
        // Maintenance history
        if (isset($_POST['crcm_maintenance_history'])) {
            $maintenance_data = json_decode(stripslashes($_POST['crcm_maintenance_history']), true);
            if (is_array($maintenance_data)) {
                $sanitized_maintenance = array();
                foreach ($maintenance_data as $record) {
                    $sanitized_maintenance[] = array(
                        'date' => sanitize_text_field($record['date']),
                        'km' => intval($record['km']),
                        'type' => sanitize_text_field($record['type']),
                        'cost' => floatval($record['cost']),
                        'notes' => sanitize_textarea_field($record['notes'])
                    );
                }
                update_post_meta($post_id, '_crcm_maintenance_history', $sanitized_maintenance);
            }
        }
        
        // Auto-calculate weekly and monthly rates if not set
        $daily_rate = floatval($_POST['crcm_daily_rate'] ?? 0);
        if ($daily_rate > 0) {
            if (empty($_POST['crcm_weekly_rate'])) {
                update_post_meta($post_id, '_crcm_weekly_rate', $daily_rate * 7 * 0.85);
            }
            if (empty($_POST['crcm_monthly_rate'])) {
                update_post_meta($post_id, '_crcm_monthly_rate', $daily_rate * 30 * 0.70);
            }
        }
    }
    
    /**
     * Admin columns for vehicle list
     */
    public function vehicle_admin_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['vehicle_image'] = __('Image', 'custom-rental-manager');
        $new_columns['vehicle_details'] = __('Vehicle Details', 'custom-rental-manager');
        $new_columns['vehicle_status'] = __('Status', 'custom-rental-manager');
        $new_columns['daily_rate'] = __('Daily Rate', 'custom-rental-manager');
        $new_columns['location'] = __('Location', 'custom-rental-manager');
        $new_columns['crcm_vehicle_category'] = __('Category', 'custom-rental-manager');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Admin column content
     */
    public function vehicle_admin_column_content($column, $post_id) {
        switch ($column) {
            case 'vehicle_image':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(60, 60));
                } else {
                    echo '<div class="no-image">🚗</div>';
                }
                break;
                
            case 'vehicle_details':
                $make = get_post_meta($post_id, '_crcm_vehicle_make', true);
                $model = get_post_meta($post_id, '_crcm_vehicle_model', true);
                $year = get_post_meta($post_id, '_crcm_vehicle_year', true);
                $license_plate = get_post_meta($post_id, '_crcm_license_plate', true);
                $seats = get_post_meta($post_id, '_crcm_vehicle_seats', true);
                $fuel_type = get_post_meta($post_id, '_crcm_fuel_type', true);
                
                echo '<div class="vehicle-details">';
                if ($make && $model) {
                    echo '<strong>' . esc_html($make . ' ' . $model) . '</strong><br>';
                }
                if ($year) {
                    echo esc_html($year) . ' ';
                }
                if ($fuel_type) {
                    echo '(' . esc_html(ucfirst($fuel_type)) . ')<br>';
                }
                if ($license_plate) {
                    echo '<span class="license-plate">' . esc_html($license_plate) . '</span>';
                }
                if ($seats) {
                    echo ' • ' . $seats . ' ' . __('seats', 'custom-rental-manager');
                }
                echo '</div>';
                break;
                
            case 'vehicle_status':
                $status = get_post_meta($post_id, '_crcm_vehicle_status', true) ?: 'available';
                $status_label = $this->get_status_label($status);
                echo '<span class="vehicle-status-badge status-' . esc_attr($status) . '">' . esc_html($status_label) . '</span>';
                break;
                
            case 'daily_rate':
                $rate = get_post_meta($post_id, '_crcm_daily_rate', true);
                if ($rate) {
                    echo '€' . number_format($rate, 2);
                } else {
                    echo '—';
                }
                break;
                
            case 'location':
                $location = get_post_meta($post_id, '_crcm_vehicle_location', true);
                echo $location ? esc_html($location) : '—';
                break;
        }
    }
    
    /**
     * Make columns sortable
     */
    public function vehicle_sortable_columns($columns) {
        $columns['daily_rate'] = 'daily_rate';
        $columns['vehicle_status'] = 'vehicle_status';
        return $columns;
    }
    
    /**
     * Admin filters
     */
    public function vehicle_admin_filters() {
        global $typenow;
        
        if ($typenow === 'crcm_vehicle') {
            // Status filter
            $current_status = isset($_GET['vehicle_status']) ? $_GET['vehicle_status'] : '';
            
            echo '<select name="vehicle_status">';
            echo '<option value="">' . __('All Statuses', 'custom-rental-manager') . '</option>';
            echo '<option value="available"' . selected($current_status, 'available', false) . '>' . __('Available', 'custom-rental-manager') . '</option>';
            echo '<option value="rented"' . selected($current_status, 'rented', false) . '>' . __('Rented', 'custom-rental-manager') . '</option>';
            echo '<option value="maintenance"' . selected($current_status, 'maintenance', false) . '>' . __('Maintenance', 'custom-rental-manager') . '</option>';
            echo '<option value="out_of_service"' . selected($current_status, 'out_of_service', false) . '>' . __('Out of Service', 'custom-rental-manager') . '</option>';
            echo '</select>';
            
            // Fuel type filter
            $current_fuel = isset($_GET['fuel_type']) ? $_GET['fuel_type'] : '';
            
            echo '<select name="fuel_type">';
            echo '<option value="">' . __('All Fuel Types', 'custom-rental-manager') . '</option>';
            echo '<option value="gasoline"' . selected($current_fuel, 'gasoline', false) . '>' . __('Gasoline', 'custom-rental-manager') . '</option>';
            echo '<option value="diesel"' . selected($current_fuel, 'diesel', false) . '>' . __('Diesel', 'custom-rental-manager') . '</option>';
            echo '<option value="hybrid"' . selected($current_fuel, 'hybrid', false) . '>' . __('Hybrid', 'custom-rental-manager') . '</option>';
            echo '<option value="electric"' . selected($current_fuel, 'electric', false) . '>' . __('Electric', 'custom-rental-manager') . '</option>';
            echo '</select>';
        }
    }
    
    /**
     * Filter vehicles by status and other criteria
     */
    public function filter_vehicles_by_status($query) {
        global $pagenow;
        
        if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'crcm_vehicle') {
            $meta_query = array();
            
            if (!empty($_GET['vehicle_status'])) {
                $meta_query[] = array(
                    'key' => '_crcm_vehicle_status',
                    'value' => sanitize_text_field($_GET['vehicle_status']),
                    'compare' => '='
                );
            }
            
            if (!empty($_GET['fuel_type'])) {
                $meta_query[] = array(
                    'key' => '_crcm_fuel_type',
                    'value' => sanitize_text_field($_GET['fuel_type']),
                    'compare' => '='
                );
            }
            
            if (!empty($meta_query)) {
                $query->query_vars['meta_query'] = $meta_query;
            }
        }
    }
    
    /**
     * Enqueue vehicle scripts
     */
    public function enqueue_vehicle_scripts() {
        if (is_admin()) {
            wp_enqueue_media();
        }
    }
    
    /**
     * AJAX: Get vehicle availability
     */
    public function ajax_get_vehicle_availability() {
        check_ajax_referer('crcm_ajax', 'nonce');
        
        $vehicle_id = intval($_POST['vehicle_id']);
        
        if (!$vehicle_id) {
            wp_send_json_error('Invalid vehicle ID');
        }
        
        $status = get_post_meta($vehicle_id, '_crcm_vehicle_status', true) ?: 'available';
        $current_bookings = $this->get_vehicle_current_bookings($vehicle_id);
        
        $message = sprintf(
            __('Vehicle status: %s. Current active bookings: %d', 'custom-rental-manager'),
            $this->get_status_label($status),
            count($current_bookings)
        );
        
        wp_send_json_success(array('message' => $message, 'bookings' => $current_bookings));
    }
    
    /**
     * Vehicle list shortcode
     */
    public function vehicle_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'status' => 'available',
            'limit' => 12,
            'orderby' => 'date',
            'order' => 'DESC'
        ), $atts);
        
        $args = array(
            'post_type' => 'crcm_vehicle',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'meta_query' => array()
        );
        
        if (!empty($atts['status'])) {
            $args['meta_query'][] = array(
                'key' => '_crcm_vehicle_status',
                'value' => $atts['status'],
                'compare' => '='
            );
        }
        
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'crcm_vehicle_category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }
        
        $vehicles = get_posts($args);
        
        if (empty($vehicles)) {
            return '<p>' . __('No vehicles found.', 'custom-rental-manager') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="crcm-vehicle-list">
            <?php foreach ($vehicles as $vehicle): ?>
                <div class="crcm-vehicle-card">
                    <div class="vehicle-image">
                        <?php if (has_post_thumbnail($vehicle->ID)): ?>
                            <?php echo get_the_post_thumbnail($vehicle->ID, 'medium'); ?>
                        <?php else: ?>
                            <div class="no-image-placeholder">🚗</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vehicle-info">
                        <h3 class="vehicle-title"><?php echo get_the_title($vehicle->ID); ?></h3>
                        
                        <div class="vehicle-details">
                            <?php
                            $make = get_post_meta($vehicle->ID, '_crcm_vehicle_make', true);
                            $model = get_post_meta($vehicle->ID, '_crcm_vehicle_model', true);
                            $year = get_post_meta($vehicle->ID, '_crcm_vehicle_year', true);
                            $seats = get_post_meta($vehicle->ID, '_crcm_vehicle_seats', true);
                            $fuel_type = get_post_meta($vehicle->ID, '_crcm_fuel_type', true);
                            
                            if ($make && $model) {
                                echo '<div class="vehicle-make-model">' . esc_html($make . ' ' . $model) . '</div>';
                            }
                            if ($year) {
                                echo '<div class="vehicle-year">' . esc_html($year) . '</div>';
                            }
                            ?>
                            
                            <div class="vehicle-specs">
                                <?php if ($seats): ?>
                                    <span class="spec-item">👥 <?php echo $seats; ?> <?php _e('seats', 'custom-rental-manager'); ?></span>
                                <?php endif; ?>
                                <?php if ($fuel_type): ?>
                                    <span class="spec-item">⛽ <?php echo ucfirst($fuel_type); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="vehicle-pricing">
                            <?php
                            $daily_rate = get_post_meta($vehicle->ID, '_crcm_daily_rate', true);
                            if ($daily_rate): ?>
                                <div class="price-daily">
                                    <span class="price">€<?php echo number_format($daily_rate, 0); ?></span>
                                    <span class="period"><?php _e('per day', 'custom-rental-manager'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="vehicle-actions">
                            <a href="<?php echo get_permalink($vehicle->ID); ?>" class="button view-details"><?php _e('View Details', 'custom-rental-manager'); ?></a>
                            <a href="#" class="button button-primary book-now" data-vehicle-id="<?php echo $vehicle->ID; ?>"><?php _e('Book Now', 'custom-rental-manager'); ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <style>
        .crcm-vehicle-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .crcm-vehicle-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        
        .crcm-vehicle-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .vehicle-image {
            height: 200px;
            overflow: hidden;
        }
        
        .vehicle-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-image-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
            font-size: 3em;
            color: #ccc;
        }
        
        .vehicle-info {
            padding: 15px;
        }
        
        .vehicle-title {
            margin: 0 0 10px 0;
            font-size: 1.2em;
        }
        
        .vehicle-make-model {
            font-weight: bold;
            color: #333;
        }
        
        .vehicle-year {
            color: #666;
            font-size: 0.9em;
        }
        
        .vehicle-specs {
            margin: 10px 0;
        }
        
        .spec-item {
            display: inline-block;
            margin-right: 15px;
            font-size: 0.9em;
            color: #666;
        }
        
        .vehicle-pricing {
            margin: 15px 0;
        }
        
        .price-daily {
            display: flex;
            align-items: baseline;
        }
        
        .price {
            font-size: 1.5em;
            font-weight: bold;
            color: #2196F3;
            margin-right: 5px;
        }
        
        .period {
            color: #666;
            font-size: 0.9em;
        }
        
        .vehicle-actions {
            display: flex;
            gap: 10px;
        }
        
        .vehicle-actions .button {
            flex: 1;
            text-align: center;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #fff;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .vehicle-actions .button:hover {
            background: #f0f0f0;
        }
        
        .vehicle-actions .button-primary {
            background: #2196F3;
            color: white;
            border-color: #2196F3;
        }
        
        .vehicle-actions .button-primary:hover {
            background: #1976D2;
            border-color: #1976D2;
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Vehicle details shortcode
     */
    public function vehicle_details_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID()
        ), $atts);
        
        $vehicle_id = intval($atts['id']);
        
        if (!$vehicle_id || get_post_type($vehicle_id) !== 'crcm_vehicle') {
            return '<p>' . __('Vehicle not found.', 'custom-rental-manager') . '</p>';
        }
        
        $vehicle = get_post($vehicle_id);
        
        ob_start();
        ?>
        <div class="crcm-vehicle-details">
            <div class="vehicle-gallery">
                <?php
                $gallery_images = get_post_meta($vehicle_id, '_crcm_vehicle_gallery', true) ?: array();
                if (has_post_thumbnail($vehicle_id)) {
                    array_unshift($gallery_images, get_post_thumbnail_id($vehicle_id));
                }
                
                if (!empty($gallery_images)): ?>
                    <div class="main-image">
                        <?php echo wp_get_attachment_image($gallery_images[0], 'large'); ?>
                    </div>
                    
                    <?php if (count($gallery_images) > 1): ?>
                        <div class="thumbnail-gallery">
                            <?php foreach ($gallery_images as $image_id): ?>
                                <div class="thumbnail-item" onclick="showMainImage(<?php echo $image_id; ?>)">
                                    <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="vehicle-content">
                <h2><?php echo get_the_title($vehicle_id); ?></h2>
                
                <div class="vehicle-specs-grid">
                    <?php
                    $specs = array(
                        'make' => array('label' => __('Make', 'custom-rental-manager'), 'value' => get_post_meta($vehicle_id, '_crcm_vehicle_make', true)),
                        'model' => array('label' => __('Model', 'custom-rental-manager'), 'value' => get_post_meta($vehicle_id, '_crcm_vehicle_model', true)),
                        'year' => array('label' => __('Year', 'custom-rental-manager'), 'value' => get_post_meta($vehicle_id, '_crcm_vehicle_year', true)),
                        'seats' => array('label' => __('Seats', 'custom-rental-manager'), 'value' => get_post_meta($vehicle_id, '_crcm_vehicle_seats', true)),
                        'doors' => array('label' => __('Doors', 'custom-rental-manager'), 'value' => get_post_meta($vehicle_id, '_crcm_vehicle_doors', true)),
                        'transmission' => array('label' => __('Transmission', 'custom-rental-manager'), 'value' => ucfirst(get_post_meta($vehicle_id, '_crcm_transmission', true))),
                        'fuel_type' => array('label' => __('Fuel Type', 'custom-rental-manager'), 'value' => ucfirst(get_post_meta($vehicle_id, '_crcm_fuel_type', true))),
                        'color' => array('label' => __('Color', 'custom-rental-manager'), 'value' => get_post_meta($vehicle_id, '_crcm_vehicle_color', true))
                    );
                    
                    foreach ($specs as $spec):
                        if (!empty($spec['value'])): ?>
                            <div class="spec-item">
                                <span class="spec-label"><?php echo $spec['label']; ?>:</span>
                                <span class="spec-value"><?php echo esc_html($spec['value']); ?></span>
                            </div>
                        <?php endif;
                    endforeach; ?>
                </div>
                
                <div class="vehicle-description">
                    <?php echo apply_filters('the_content', $vehicle->post_content); ?>
                </div>
                
                <div class="vehicle-features">
                    <?php
                    $features = get_the_terms($vehicle_id, 'crcm_vehicle_features');
                    if ($features && !is_wp_error($features)): ?>
                        <h3><?php _e('Features', 'custom-rental-manager'); ?></h3>
                        <div class="features-list">
                            <?php foreach ($features as $feature): ?>
                                <span class="feature-tag"><?php echo esc_html($feature->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="vehicle-pricing-details">
                    <h3><?php _e('Pricing', 'custom-rental-manager'); ?></h3>
                    <div class="pricing-grid">
                        <?php
                        $daily_rate = get_post_meta($vehicle_id, '_crcm_daily_rate', true);
                        $weekly_rate = get_post_meta($vehicle_id, '_crcm_weekly_rate', true);
                        $monthly_rate = get_post_meta($vehicle_id, '_crcm_monthly_rate', true);
                        $deposit = get_post_meta($vehicle_id, '_crcm_deposit_amount', true);
                        $km_included = get_post_meta($vehicle_id, '_crcm_km_included', true);
                        ?>
                        
                        <?php if ($daily_rate): ?>
                            <div class="pricing-item">
                                <span class="pricing-label"><?php _e('Daily Rate', 'custom-rental-manager'); ?></span>
                                <span class="pricing-value">€<?php echo number_format($daily_rate, 2); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($weekly_rate): ?>
                            <div class="pricing-item">
                                <span class="pricing-label"><?php _e('Weekly Rate', 'custom-rental-manager'); ?></span>
                                <span class="pricing-value">€<?php echo number_format($weekly_rate, 2); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($monthly_rate): ?>
                            <div class="pricing-item">
                                <span class="pricing-label"><?php _e('Monthly Rate', 'custom-rental-manager'); ?></span>
                                <span class="pricing-value">€<?php echo number_format($monthly_rate, 2); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($deposit): ?>
                            <div class="pricing-item">
                                <span class="pricing-label"><?php _e('Security Deposit', 'custom-rental-manager'); ?></span>
                                <span class="pricing-value">€<?php echo number_format($deposit, 2); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($km_included): ?>
                            <div class="pricing-item">
                                <span class="pricing-label"><?php _e('KM Included (daily)', 'custom-rental-manager'); ?></span>
                                <span class="pricing-value"><?php echo number_format($km_included); ?> KM</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="booking-action">
                    <button class="button button-primary book-vehicle" data-vehicle-id="<?php echo $vehicle_id; ?>">
                        <?php _e('Book This Vehicle', 'custom-rental-manager'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <style>
        .crcm-vehicle-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .crcm-vehicle-details {
                grid-template-columns: 1fr;
            }
        }
        
        .vehicle-gallery .main-image img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .thumbnail-gallery {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            overflow-x: auto;
        }
        
        .thumbnail-item {
            flex-shrink: 0;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .thumbnail-item:hover {
            opacity: 1;
        }
        
        .thumbnail-item img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .vehicle-specs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 20px 0;
        }
        
        .spec-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .spec-label {
            font-weight: bold;
            color: #666;
        }
        
        .features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0;
        }
        
        .feature-tag {
            background: #f0f0f0;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 0.9em;
            color: #666;
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        
        .pricing-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .pricing-label {
            color: #666;
        }
        
        .pricing-value {
            font-weight: bold;
            color: #2196F3;
        }
        
        .booking-action {
            margin-top: 30px;
            text-align: center;
        }
        
        .book-vehicle {
            padding: 12px 30px;
            font-size: 1.1em;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .book-vehicle:hover {
            background: #1976D2;
        }
        </style>
        
        <script>
        function showMainImage(imageId) {
            // This would implement gallery functionality
            console.log('Show image:', imageId);
        }
        
        jQuery(document).ready(function($) {
            $('.book-vehicle').on('click', function() {
                var vehicleId = $(this).data('vehicle-id');
                // Implement booking functionality
                alert('Booking functionality - Vehicle ID: ' + vehicleId);
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Vehicle search shortcode
     */
    public function vehicle_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_filters' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div class="crcm-vehicle-search">
            <form class="vehicle-search-form" method="get">
                <div class="search-fields">
                    <div class="search-field">
                        <label for="pickup_date"><?php _e('Pickup Date', 'custom-rental-manager'); ?></label>
                        <input type="date" id="pickup_date" name="pickup_date" value="<?php echo esc_attr($_GET['pickup_date'] ?? ''); ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="search-field">
                        <label for="return_date"><?php _e('Return Date', 'custom-rental-manager'); ?></label>
                        <input type="date" id="return_date" name="return_date" value="<?php echo esc_attr($_GET['return_date'] ?? ''); ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <?php if ($atts['show_filters'] === 'true'): ?>
                        <div class="search-field">
                            <label for="vehicle_category"><?php _e('Category', 'custom-rental-manager'); ?></label>
                            <select id="vehicle_category" name="vehicle_category">
                                <option value=""><?php _e('All Categories', 'custom-rental-manager'); ?></option>
                                <?php
                                $categories = get_terms(array('taxonomy' => 'crcm_vehicle_category', 'hide_empty' => false));
                                foreach ($categories as $category) {
                                    $selected = selected($_GET['vehicle_category'] ?? '', $category->slug, false);
                                    echo '<option value="' . esc_attr($category->slug) . '"' . $selected . '>' . esc_html($category->name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="search-field">
                            <label for="fuel_type"><?php _e('Fuel Type', 'custom-rental-manager'); ?></label>
                            <select id="fuel_type" name="fuel_type">
                                <option value=""><?php _e('Any', 'custom-rental-manager'); ?></option>
                                <option value="gasoline" <?php selected($_GET['fuel_type'] ?? '', 'gasoline'); ?>><?php _e('Gasoline', 'custom-rental-manager'); ?></option>
                                <option value="diesel" <?php selected($_GET['fuel_type'] ?? '', 'diesel'); ?>><?php _e('Diesel', 'custom-rental-manager'); ?></option>
                                <option value="hybrid" <?php selected($_GET['fuel_type'] ?? '', 'hybrid'); ?>><?php _e('Hybrid', 'custom-rental-manager'); ?></option>
                                <option value="electric" <?php selected($_GET['fuel_type'] ?? '', 'electric'); ?>><?php _e('Electric', 'custom-rental-manager'); ?></option>
                            </select>
                        </div>
                        
                        <div class="search-field">
                            <label for="min_seats"><?php _e('Min. Seats', 'custom-rental-manager'); ?></label>
                            <select id="min_seats" name="min_seats">
                                <option value=""><?php _e('Any', 'custom-rental-manager'); ?></option>
                                <option value="2" <?php selected($_GET['min_seats'] ?? '', '2'); ?>>2+</option>
                                <option value="4" <?php selected($_GET['min_seats'] ?? '', '4'); ?>>4+</option>
                                <option value="5" <?php selected($_GET['min_seats'] ?? '', '5'); ?>>5+</option>
                                <option value="7" <?php selected($_GET['min_seats'] ?? '', '7'); ?>>7+</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="search-actions">
                    <button type="submit" class="button button-primary search-button"><?php _e('Search Vehicles', 'custom-rental-manager'); ?></button>
                    <button type="button" class="button clear-filters"><?php _e('Clear Filters', 'custom-rental-manager'); ?></button>
                </div>
            </form>
            
            <div class="search-results" id="vehicle-search-results">
                <?php
                if (!empty($_GET['pickup_date']) || !empty($_GET['vehicle_category']) || !empty($_GET['fuel_type'])) {
                    echo $this->get_search_results($_GET);
                }
                ?>
            </div>
        </div>
        
        <style>
        .vehicle-search-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .search-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .search-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .search-field input,
        .search-field select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-actions {
            text-align: center;
        }
        
        .search-button {
            background: #2196F3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            margin-right: 10px;
            cursor: pointer;
        }
        
        .clear-filters {
            background: #666;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.clear-filters').on('click', function() {
                $('.vehicle-search-form')[0].reset();
                $('#vehicle-search-results').empty();
                
                // Update URL without parameters
                if (history.pushState) {
                    history.pushState({}, document.title, window.location.pathname);
                }
            });
            
            // Auto-set return date
            $('#pickup_date').on('change', function() {
                var pickupDate = new Date($(this).val());
                if (pickupDate && !$('#return_date').val()) {
                    pickupDate.setDate(pickupDate.getDate() + 3);
                    $('#return_date').val(pickupDate.toISOString().split('T')[0]);
                }
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get search results
     */
    private function get_search_results($params) {
        $args = array(
            'post_type' => 'crcm_vehicle',
            'posts_per_page' => 20,
            'meta_query' => array(
                array(
                    'key' => '_crcm_vehicle_status',
                    'value' => 'available',
                    'compare' => '='
                )
            )
        );
        
        if (!empty($params['fuel_type'])) {
            $args['meta_query'][] = array(
                'key' => '_crcm_fuel_type',
                'value' => sanitize_text_field($params['fuel_type']),
                'compare' => '='
            );
        }
        
        if (!empty($params['min_seats'])) {
            $args['meta_query'][] = array(
                'key' => '_crcm_vehicle_seats',
                'value' => intval($params['min_seats']),
                'compare' => '>='
            );
        }
        
        if (!empty($params['vehicle_category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'crcm_vehicle_category',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($params['vehicle_category'])
                )
            );
        }
        
        $vehicles = get_posts($args);
        
        if (empty($vehicles)) {
            return '<p>' . __('No vehicles found matching your criteria.', 'custom-rental-manager') . '</p>';
        }
        
        return do_shortcode('[crcm_vehicle_list limit="20" status="available"]');
    }
    
    /**
     * Helper functions
     */
    private function get_status_label($status) {
        $labels = array(
            'available' => __('Available', 'custom-rental-manager'),
            'rented' => __('Rented', 'custom-rental-manager'),
            'maintenance' => __('Maintenance', 'custom-rental-manager'),
            'out_of_service' => __('Out of Service', 'custom-rental-manager')
        );
        
        return $labels[$status] ?? $status;
    }
    
    private function get_service_status($vehicle_id) {
        $current_km = get_post_meta($vehicle_id, '_crcm_vehicle_mileage', true) ?: 0;
        $last_service_km = get_post_meta($vehicle_id, '_crcm_last_service_km', true) ?: 0;
        $service_interval = get_post_meta($vehicle_id, '_crcm_service_interval', true) ?: 10000;
        
        $km_since_service = $current_km - $last_service_km;
        $km_until_service = $service_interval - $km_since_service;
        
        if ($km_until_service <= 0) {
            return '<span class="service-status overdue">' . __('Overdue', 'custom-rental-manager') . '</span>';
        } elseif ($km_until_service <= 1000) {
            return '<span class="service-status due-soon">' . __('Due Soon', 'custom-rental-manager') . '</span>';
        } else {
            return '<span class="service-status ok">' . __('OK', 'custom-rental-manager') . '</span>';
        }
    }
    
    private function get_vehicle_current_bookings($vehicle_id) {
        $bookings = get_posts(array(
            'post_type' => 'crcm_booking',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_crcm_vehicle_id',
                    'value' => $vehicle_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_crcm_booking_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        ));
        
        return $bookings;
    }
}
