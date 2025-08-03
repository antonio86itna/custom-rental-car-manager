<?php
/**
 * Vehicle Manager Class
 * 
 * Handles all vehicle-related operations including CRUD operations,
 * availability management, and pricing.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRCM_Vehicle_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_vehicle_meta'));
        add_action('wp_ajax_crcm_search_vehicles', array($this, 'ajax_search_vehicles'));
        add_action('wp_ajax_nopriv_crcm_search_vehicles', array($this, 'ajax_search_vehicles'));
        add_filter('manage_crcm_vehicle_posts_columns', array($this, 'vehicle_columns'));
        add_action('manage_crcm_vehicle_posts_custom_column', array($this, 'vehicle_column_content'), 10, 2);
    }

    /**
     * Add meta boxes for vehicle post type
     */
    public function add_meta_boxes() {
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
            __('Pricing & Availability', 'custom-rental-manager'),
            array($this, 'pricing_meta_box'),
            'crcm_vehicle',
            'normal',
            'high'
        );

        add_meta_box(
            'crcm_vehicle_gallery',
            __('Vehicle Gallery', 'custom-rental-manager'),
            array($this, 'gallery_meta_box'),
            'crcm_vehicle',
            'side',
            'default'
        );

        add_meta_box(
            'crcm_vehicle_features',
            __('Features & Specifications', 'custom-rental-manager'),
            array($this, 'features_meta_box'),
            'crcm_vehicle',
            'normal',
            'default'
        );
    }

    /**
     * Vehicle details meta box
     */
    public function vehicle_details_meta_box($post) {
        wp_nonce_field('crcm_vehicle_meta_nonce', 'crcm_vehicle_meta_nonce_field');

        $vehicle_data = get_post_meta($post->ID, '_crcm_vehicle_data', true);

        // Default values
        if (empty($vehicle_data)) {
            $vehicle_data = array(
                'brand' => '',
                'model' => '',
                'year' => date('Y'),
                'color' => '',
                'license_plate' => '',
                'engine_size' => '',
                'fuel_type' => 'gasoline',
                'transmission' => 'manual',
                'seats' => 2,
                'doors' => 2,
                'condition' => 'excellent',
                'mileage' => 0,
                'vin' => '',
                'registration_date' => '',
                'insurance_expiry' => '',
                'maintenance_due' => '',
            );
        }
        ?>
        <table class="form-table crcm-form-table">
            <tr>
                <th><label for="crcm_brand"><?php _e('Brand', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_brand" name="vehicle_data[brand]" value="<?php echo esc_attr($vehicle_data['brand']); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_model"><?php _e('Model', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_model" name="vehicle_data[model]" value="<?php echo esc_attr($vehicle_data['model']); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_year"><?php _e('Year', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_year" name="vehicle_data[year]" value="<?php echo esc_attr($vehicle_data['year']); ?>" min="1900" max="<?php echo date('Y') + 1; ?>" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_color"><?php _e('Color', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_color" name="vehicle_data[color]" value="<?php echo esc_attr($vehicle_data['color']); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_license_plate"><?php _e('License Plate', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_license_plate" name="vehicle_data[license_plate]" value="<?php echo esc_attr($vehicle_data['license_plate']); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_engine_size"><?php _e('Engine Size (cc)', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_engine_size" name="vehicle_data[engine_size]" value="<?php echo esc_attr($vehicle_data['engine_size']); ?>" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_fuel_type"><?php _e('Fuel Type', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_fuel_type" name="vehicle_data[fuel_type]">
                        <option value="gasoline" <?php selected($vehicle_data['fuel_type'], 'gasoline'); ?>><?php _e('Gasoline', 'custom-rental-manager'); ?></option>
                        <option value="diesel" <?php selected($vehicle_data['fuel_type'], 'diesel'); ?>><?php _e('Diesel', 'custom-rental-manager'); ?></option>
                        <option value="electric" <?php selected($vehicle_data['fuel_type'], 'electric'); ?>><?php _e('Electric', 'custom-rental-manager'); ?></option>
                        <option value="hybrid" <?php selected($vehicle_data['fuel_type'], 'hybrid'); ?>><?php _e('Hybrid', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_transmission"><?php _e('Transmission', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_transmission" name="vehicle_data[transmission]">
                        <option value="manual" <?php selected($vehicle_data['transmission'], 'manual'); ?>><?php _e('Manual', 'custom-rental-manager'); ?></option>
                        <option value="automatic" <?php selected($vehicle_data['transmission'], 'automatic'); ?>><?php _e('Automatic', 'custom-rental-manager'); ?></option>
                        <option value="cvt" <?php selected($vehicle_data['transmission'], 'cvt'); ?>><?php _e('CVT', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_seats"><?php _e('Number of Seats', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_seats" name="vehicle_data[seats]" value="<?php echo esc_attr($vehicle_data['seats']); ?>" min="1" max="50" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_doors"><?php _e('Number of Doors', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_doors" name="vehicle_data[doors]" value="<?php echo esc_attr($vehicle_data['doors']); ?>" min="0" max="10" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_condition"><?php _e('Condition', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_condition" name="vehicle_data[condition]">
                        <option value="excellent" <?php selected($vehicle_data['condition'], 'excellent'); ?>><?php _e('Excellent', 'custom-rental-manager'); ?></option>
                        <option value="good" <?php selected($vehicle_data['condition'], 'good'); ?>><?php _e('Good', 'custom-rental-manager'); ?></option>
                        <option value="fair" <?php selected($vehicle_data['condition'], 'fair'); ?>><?php _e('Fair', 'custom-rental-manager'); ?></option>
                        <option value="poor" <?php selected($vehicle_data['condition'], 'poor'); ?>><?php _e('Poor', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_mileage"><?php _e('Mileage (km)', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_mileage" name="vehicle_data[mileage]" value="<?php echo esc_attr($vehicle_data['mileage']); ?>" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Pricing meta box
     */
    public function pricing_meta_box($post) {
        $pricing_data = get_post_meta($post->ID, '_crcm_pricing_data', true);

        // Default values
        if (empty($pricing_data)) {
            $pricing_data = array(
                'daily_rate' => 0,
                'weekly_rate' => 0,
                'monthly_rate' => 0,
                'weekly_discount' => 10,
                'monthly_discount' => 20,
                'security_deposit' => 200,
                'available_quantity' => 1,
                'min_rental_days' => 1,
                'max_rental_days' => 30,
            );
        }
        ?>
        <table class="form-table crcm-form-table">
            <tr>
                <th><label for="crcm_daily_rate"><?php _e('Daily Rate (€)', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_daily_rate" name="pricing_data[daily_rate]" value="<?php echo esc_attr($pricing_data['daily_rate']); ?>" step="0.01" min="0" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_weekly_discount"><?php _e('Weekly Discount (%)', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_weekly_discount" name="pricing_data[weekly_discount]" value="<?php echo esc_attr($pricing_data['weekly_discount']); ?>" step="0.1" min="0" max="100" />
                    <p class="description"><?php _e('Discount for rentals of 7+ days', 'custom-rental-manager'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_monthly_discount"><?php _e('Monthly Discount (%)', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_monthly_discount" name="pricing_data[monthly_discount]" value="<?php echo esc_attr($pricing_data['monthly_discount']); ?>" step="0.1" min="0" max="100" />
                    <p class="description"><?php _e('Discount for rentals of 30+ days', 'custom-rental-manager'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_security_deposit"><?php _e('Security Deposit (€)', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_security_deposit" name="pricing_data[security_deposit]" value="<?php echo esc_attr($pricing_data['security_deposit']); ?>" step="0.01" min="0" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_available_quantity"><?php _e('Available Quantity', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_available_quantity" name="pricing_data[available_quantity]" value="<?php echo esc_attr($pricing_data['available_quantity']); ?>" min="0" />
                    <p class="description"><?php _e('How many of this vehicle are available for rent', 'custom-rental-manager'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_min_rental_days"><?php _e('Minimum Rental Days', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_min_rental_days" name="pricing_data[min_rental_days]" value="<?php echo esc_attr($pricing_data['min_rental_days']); ?>" min="1" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_max_rental_days"><?php _e('Maximum Rental Days', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_max_rental_days" name="pricing_data[max_rental_days]" value="<?php echo esc_attr($pricing_data['max_rental_days']); ?>" min="1" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Gallery meta box
     */
    public function gallery_meta_box($post) {
        $gallery_ids = get_post_meta($post->ID, '_crcm_gallery_ids', true);
        $gallery_ids = !empty($gallery_ids) ? explode(',', $gallery_ids) : array();
        ?>
        <div class="crcm-gallery-container">
            <div class="crcm-gallery-images">
                <?php foreach ($gallery_ids as $attachment_id): 
                    if (empty($attachment_id)) continue;
                    $image_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                    if ($image_url): ?>
                        <div class="crcm-gallery-image" data-id="<?php echo esc_attr($attachment_id); ?>">
                            <img src="<?php echo esc_url($image_url); ?>" alt="" />
                            <button type="button" class="crcm-remove-image">&times;</button>
                        </div>
                    <?php endif;
                endforeach; ?>
            </div>

            <button type="button" class="button crcm-add-images">
                <?php _e('Add Images', 'custom-rental-manager'); ?>
            </button>

            <input type="hidden" id="crcm_gallery_ids" name="gallery_ids" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>" />
        </div>
        <?php
    }

    /**
     * Features meta box
     */
    public function features_meta_box($post) {
        $features = get_post_meta($post->ID, '_crcm_vehicle_features', true);
        if (!is_array($features)) {
            $features = array();
        }

        $available_features = array(
            'air_conditioning' => __('Air Conditioning', 'custom-rental-manager'),
            'gps' => __('GPS Navigation', 'custom-rental-manager'),
            'bluetooth' => __('Bluetooth', 'custom-rental-manager'),
            'usb_charging' => __('USB Charging', 'custom-rental-manager'),
            'helmet_included' => __('Helmet Included', 'custom-rental-manager'),
            'storage_box' => __('Storage Box', 'custom-rental-manager'),
            'phone_holder' => __('Phone Holder', 'custom-rental-manager'),
            'anti_theft' => __('Anti-theft System', 'custom-rental-manager'),
            'abs_brakes' => __('ABS Brakes', 'custom-rental-manager'),
            'led_lights' => __('LED Lights', 'custom-rental-manager'),
        );
        ?>
        <div class="crcm-features-grid">
            <?php foreach ($available_features as $key => $label): ?>
                <label class="crcm-feature-item">
                    <input type="checkbox" name="vehicle_features[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $features)); ?> />
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <style>
        .crcm-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .crcm-feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }

        .crcm-feature-item:hover {
            background: #f0f0f0;
        }
        </style>
        <?php
    }

    /**
     * Save vehicle meta data
     */
    public function save_vehicle_meta($post_id) {
        // Verify nonce
        if (!isset($_POST['crcm_vehicle_meta_nonce_field']) || !wp_verify_nonce($_POST['crcm_vehicle_meta_nonce_field'], 'crcm_vehicle_meta_nonce')) {
            return;
        }

        // Check if user has permission to edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Only save for vehicle post type
        if (get_post_type($post_id) !== 'crcm_vehicle') {
            return;
        }

        // Save vehicle data
        if (isset($_POST['vehicle_data'])) {
            $vehicle_data = array();
            foreach ($_POST['vehicle_data'] as $key => $value) {
                $vehicle_data[$key] = sanitize_text_field($value);
            }
            update_post_meta($post_id, '_crcm_vehicle_data', $vehicle_data);
        }

        // Save pricing data
        if (isset($_POST['pricing_data'])) {
            $pricing_data = array();
            foreach ($_POST['pricing_data'] as $key => $value) {
                $pricing_data[$key] = floatval($value);
            }
            update_post_meta($post_id, '_crcm_pricing_data', $pricing_data);
        }

        // Save gallery
        if (isset($_POST['gallery_ids'])) {
            update_post_meta($post_id, '_crcm_gallery_ids', sanitize_text_field($_POST['gallery_ids']));
        }

        // Save features
        if (isset($_POST['vehicle_features'])) {
            $features = array_map('sanitize_text_field', $_POST['vehicle_features']);
            update_post_meta($post_id, '_crcm_vehicle_features', $features);
        } else {
            update_post_meta($post_id, '_crcm_vehicle_features', array());
        }
    }

    /**
     * Custom columns for vehicle list
     */
    public function vehicle_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['crcm_image'] = __('Image', 'custom-rental-manager');
        $new_columns['crcm_type'] = __('Type', 'custom-rental-manager');
        $new_columns['crcm_brand_model'] = __('Brand & Model', 'custom-rental-manager');
        $new_columns['crcm_daily_rate'] = __('Daily Rate', 'custom-rental-manager');
        $new_columns['crcm_availability'] = __('Available', 'custom-rental-manager');
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    /**
     * Display custom column content
     */
    public function vehicle_column_content($column, $post_id) {
        $vehicle_data = get_post_meta($post_id, '_crcm_vehicle_data', true);
        $pricing_data = get_post_meta($post_id, '_crcm_pricing_data', true);

        switch ($column) {
            case 'crcm_image':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(60, 60));
                } else {
                    echo '<div style="width:60px;height:60px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;"><span class="dashicons dashicons-car"></span></div>';
                }
                break;

            case 'crcm_type':
                $terms = get_the_terms($post_id, 'crcm_vehicle_type');
                if ($terms && !is_wp_error($terms)) {
                    echo esc_html($terms[0]->name);
                }
                break;

            case 'crcm_brand_model':
                if ($vehicle_data && isset($vehicle_data['brand'], $vehicle_data['model'])) {
                    echo esc_html($vehicle_data['brand'] . ' ' . $vehicle_data['model']);
                    if (isset($vehicle_data['year'])) {
                        echo '<br><small>(' . esc_html($vehicle_data['year']) . ')</small>';
                    }
                }
                break;

            case 'crcm_daily_rate':
                if ($pricing_data && isset($pricing_data['daily_rate'])) {
                    echo '€' . number_format($pricing_data['daily_rate'], 2);
                }
                break;

            case 'crcm_availability':
                if ($pricing_data && isset($pricing_data['available_quantity'])) {
                    $available = intval($pricing_data['available_quantity']);
                    $color = $available > 0 ? 'green' : 'red';
                    echo '<span style="color:' . $color . ';">' . $available . '</span>';
                }
                break;
        }
    }

    /**
     * Search available vehicles
     */
    public function search_available_vehicles($pickup_date, $return_date, $vehicle_type = '') {
        $args = array(
            'post_type' => 'crcm_vehicle',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        if (!empty($vehicle_type)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'crcm_vehicle_type',
                    'field' => 'term_id',
                    'terms' => $vehicle_type,
                ),
            );
        }

        $vehicles = get_posts($args);
        $available_vehicles = array();

        foreach ($vehicles as $vehicle) {
            $available_quantity = $this->check_availability($vehicle->ID, $pickup_date, $return_date);

            if ($available_quantity > 0) {
                $vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
                $pricing_data = get_post_meta($vehicle->ID, '_crcm_pricing_data', true);

                $available_vehicles[] = array(
                    'id' => $vehicle->ID,
                    'title' => $vehicle->post_title,
                    'permalink' => get_permalink($vehicle->ID),
                    'thumbnail' => get_the_post_thumbnail_url($vehicle->ID, 'medium'),
                    'daily_rate' => $pricing_data['daily_rate'] ?? 0,
                    'brand' => $vehicle_data['brand'] ?? '',
                    'model' => $vehicle_data['model'] ?? '',
                    'seats' => $vehicle_data['seats'] ?? 0,
                    'transmission' => $vehicle_data['transmission'] ?? '',
                    'available_quantity' => $available_quantity,
                );
            }
        }

        return $available_vehicles;
    }

    /**
     * Check vehicle availability
     */
    public function check_availability($vehicle_id, $pickup_date, $return_date) {
        $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
        $total_quantity = isset($pricing_data['available_quantity']) ? intval($pricing_data['available_quantity']) : 0;

        if ($total_quantity <= 0) {
            return 0;
        }

        // Count bookings that overlap with the requested dates
        $overlapping_bookings = get_posts(array(
            'post_type' => 'crcm_booking',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_crcm_booking_data',
                    'value' => 'vehicle_id";i:' . $vehicle_id,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_crcm_booking_status',
                    'value' => array('pending', 'confirmed', 'active'),
                    'compare' => 'IN',
                ),
            ),
        ));

        $booked_quantity = 0;

        foreach ($overlapping_bookings as $booking) {
            $booking_data = get_post_meta($booking->ID, '_crcm_booking_data', true);

            if (!$booking_data || !isset($booking_data['pickup_date'], $booking_data['return_date'])) {
                continue;
            }

            // Check if dates overlap
            if ($this->dates_overlap($pickup_date, $return_date, $booking_data['pickup_date'], $booking_data['return_date'])) {
                $booked_quantity++;
            }
        }

        return max(0, $total_quantity - $booked_quantity);
    }

    /**
     * Check if two date ranges overlap
     */
    private function dates_overlap($start1, $end1, $start2, $end2) {
        return $start1 < $end2 && $end1 > $start2;
    }

    /**
     * AJAX search vehicles
     */
    public function ajax_search_vehicles() {
        check_ajax_referer('crcm_nonce', 'nonce');

        $pickup_date = sanitize_text_field($_POST['pickup_date'] ?? '');
        $return_date = sanitize_text_field($_POST['return_date'] ?? '');
        $vehicle_type = sanitize_text_field($_POST['vehicle_type'] ?? '');

        if (empty($pickup_date) || empty($return_date)) {
            wp_send_json_error(__('Please select pickup and return dates.', 'custom-rental-manager'));
        }

        $vehicles = $this->search_available_vehicles($pickup_date, $return_date, $vehicle_type);

        wp_send_json_success($vehicles);
    }
}
