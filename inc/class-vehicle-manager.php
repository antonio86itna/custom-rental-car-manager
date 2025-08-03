<?php
/**
 * Vehicle Manager Class
 * 
 * Handles all vehicle-related operations including CRUD operations,
 * availability checking, pricing rules, and search functionality.
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
        add_action('save_post', array($this, 'save_meta_data'));
        add_filter('manage_crcm_vehicle_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_crcm_vehicle_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }

    /**
     * Add meta boxes for vehicle post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'crcm_vehicle_details',
            __('Vehicle Details', 'custom-rental-manager'),
            array($this, 'render_vehicle_details_meta_box'),
            'crcm_vehicle',
            'normal',
            'high'
        );

        add_meta_box(
            'crcm_vehicle_pricing',
            __('Pricing & Availability', 'custom-rental-manager'),
            array($this, 'render_pricing_meta_box'),
            'crcm_vehicle',
            'normal',
            'high'
        );

        add_meta_box(
            'crcm_vehicle_gallery',
            __('Vehicle Gallery', 'custom-rental-manager'),
            array($this, 'render_gallery_meta_box'),
            'crcm_vehicle',
            'side',
            'default'
        );
    }

    /**
     * Render vehicle details meta box
     */
    public function render_vehicle_details_meta_box($post) {
        wp_nonce_field('crcm_vehicle_meta', 'crcm_vehicle_meta_nonce');

        $vehicle_data = get_post_meta($post->ID, '_crcm_vehicle_data', true);
        $defaults = array(
            'seats' => 4,
            'transmission' => 'manual',
            'fuel_type' => 'gasoline',
            'doors' => 4,
            'luggage' => 2,
            'air_conditioning' => true,
            'stock_quantity' => 1,
            'min_age' => 21,
            'license_type' => 'B',
            'deposit_amount' => 200,
        );

        $vehicle_data = wp_parse_args($vehicle_data, $defaults);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="crcm_seats"><?php _e('Number of Seats', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_seats" name="crcm_vehicle[seats]" value="<?php echo esc_attr($vehicle_data['seats']); ?>" min="1" max="9" class="small-text" />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_transmission"><?php _e('Transmission', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_transmission" name="crcm_vehicle[transmission]">
                        <option value="manual" <?php selected($vehicle_data['transmission'], 'manual'); ?>><?php _e('Manual', 'custom-rental-manager'); ?></option>
                        <option value="automatic" <?php selected($vehicle_data['transmission'], 'automatic'); ?>><?php _e('Automatic', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_fuel_type"><?php _e('Fuel Type', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_fuel_type" name="crcm_vehicle[fuel_type]">
                        <option value="gasoline" <?php selected($vehicle_data['fuel_type'], 'gasoline'); ?>><?php _e('Gasoline', 'custom-rental-manager'); ?></option>
                        <option value="diesel" <?php selected($vehicle_data['fuel_type'], 'diesel'); ?>><?php _e('Diesel', 'custom-rental-manager'); ?></option>
                        <option value="electric" <?php selected($vehicle_data['fuel_type'], 'electric'); ?>><?php _e('Electric', 'custom-rental-manager'); ?></option>
                        <option value="hybrid" <?php selected($vehicle_data['fuel_type'], 'hybrid'); ?>><?php _e('Hybrid', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_doors"><?php _e('Number of Doors', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_doors" name="crcm_vehicle[doors]" value="<?php echo esc_attr($vehicle_data['doors']); ?>" min="2" max="5" class="small-text" />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_luggage"><?php _e('Luggage Capacity', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_luggage" name="crcm_vehicle[luggage]" value="<?php echo esc_attr($vehicle_data['luggage']); ?>" min="0" max="10" class="small-text" />
                    <p class="description"><?php _e('Number of large suitcases', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_stock_quantity"><?php _e('Stock Quantity', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_stock_quantity" name="crcm_vehicle[stock_quantity]" value="<?php echo esc_attr($vehicle_data['stock_quantity']); ?>" min="1" max="100" class="small-text" />
                    <p class="description"><?php _e('Total number of vehicles of this model', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_min_age"><?php _e('Minimum Age', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_min_age" name="crcm_vehicle[min_age]" value="<?php echo esc_attr($vehicle_data['min_age']); ?>" min="18" max="30" class="small-text" />
                    <p class="description"><?php _e('Minimum age required to rent this vehicle', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_license_type"><?php _e('License Type Required', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_license_type" name="crcm_vehicle[license_type]">
                        <option value="B" <?php selected($vehicle_data['license_type'], 'B'); ?>><?php _e('B (Car)', 'custom-rental-manager'); ?></option>
                        <option value="A" <?php selected($vehicle_data['license_type'], 'A'); ?>><?php _e('A (Motorcycle)', 'custom-rental-manager'); ?></option>
                        <option value="A1" <?php selected($vehicle_data['license_type'], 'A1'); ?>><?php _e('A1 (Scooter 125cc)', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_deposit_amount"><?php _e('Security Deposit', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_deposit_amount" name="crcm_vehicle[deposit_amount]" value="<?php echo esc_attr($vehicle_data['deposit_amount']); ?>" min="0" step="0.01" />
                    <span><?php echo crcm()->get_setting('currency_symbol', '€'); ?></span>
                </td>
            </tr>
            <tr>
                <th><?php _e('Features', 'custom-rental-manager'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="crcm_vehicle[air_conditioning]" value="1" <?php checked($vehicle_data['air_conditioning'], true); ?> />
                        <?php _e('Air Conditioning', 'custom-rental-manager'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render pricing meta box
     */
    public function render_pricing_meta_box($post) {
        $pricing_data = get_post_meta($post->ID, '_crcm_pricing_data', true);
        $defaults = array(
            'base_price' => 0,
            'weekend_price' => 0,
            'weekly_discount' => 0,
            'monthly_discount' => 0,
            'insurance_basic' => 0,
            'insurance_premium' => 0,
        );

        $pricing_data = wp_parse_args($pricing_data, $defaults);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="crcm_base_price"><?php _e('Base Daily Rate', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_base_price" name="crcm_pricing[base_price]" value="<?php echo esc_attr($pricing_data['base_price']); ?>" min="0" step="0.01" />
                    <span><?php echo crcm()->get_setting('currency_symbol', '€'); ?></span>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_weekend_price"><?php _e('Weekend Surcharge', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_weekend_price" name="crcm_pricing[weekend_price]" value="<?php echo esc_attr($pricing_data['weekend_price']); ?>" min="0" step="0.01" />
                    <span><?php echo crcm()->get_setting('currency_symbol', '€'); ?></span>
                    <p class="description"><?php _e('Extra daily rate for Friday-Sunday', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_weekly_discount"><?php _e('Weekly Discount', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_weekly_discount" name="crcm_pricing[weekly_discount]" value="<?php echo esc_attr($pricing_data['weekly_discount']); ?>" min="0" max="50" step="0.01" />
                    <span>%</span>
                    <p class="description"><?php _e('Discount for rentals 7+ days', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_monthly_discount"><?php _e('Monthly Discount', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_monthly_discount" name="crcm_pricing[monthly_discount]" value="<?php echo esc_attr($pricing_data['monthly_discount']); ?>" min="0" max="50" step="0.01" />
                    <span>%</span>
                    <p class="description"><?php _e('Discount for rentals 30+ days', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_insurance_basic"><?php _e('Basic Insurance (Daily)', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_insurance_basic" name="crcm_pricing[insurance_basic]" value="<?php echo esc_attr($pricing_data['insurance_basic']); ?>" min="0" step="0.01" />
                    <span><?php echo crcm()->get_setting('currency_symbol', '€'); ?></span>
                    <p class="description"><?php _e('Basic insurance rate per day (0 = included)', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_insurance_premium"><?php _e('Premium Insurance (Daily)', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" id="crcm_insurance_premium" name="crcm_pricing[insurance_premium]" value="<?php echo esc_attr($pricing_data['insurance_premium']); ?>" min="0" step="0.01" />
                    <span><?php echo crcm()->get_setting('currency_symbol', '€'); ?></span>
                    <p class="description"><?php _e('Premium insurance with reduced deductible', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render gallery meta box
     */
    public function render_gallery_meta_box($post) {
        $gallery_ids = get_post_meta($post->ID, '_crcm_gallery_ids', true);
        if (!is_array($gallery_ids)) {
            $gallery_ids = array();
        }
        ?>
        <div id="crcm-gallery-container">
            <div id="crcm-gallery-images">
                <?php foreach ($gallery_ids as $image_id): ?>
                    <div class="crcm-gallery-image" data-id="<?php echo esc_attr($image_id); ?>">
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                        <button type="button" class="crcm-remove-image">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="crcm-add-gallery-images" class="button"><?php _e('Add Images', 'custom-rental-manager'); ?></button>
            <input type="hidden" id="crcm-gallery-ids" name="crcm_gallery_ids" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>" />
        </div>

        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;

            $('#crcm-add-gallery-images').click(function(e) {
                e.preventDefault();

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: '<?php _e('Choose Images', 'custom-rental-manager'); ?>',
                    button: {
                        text: '<?php _e('Add Images', 'custom-rental-manager'); ?>'
                    },
                    multiple: true
                });

                mediaUploader.on('select', function() {
                    var attachments = mediaUploader.state().get('selection').toJSON();
                    var currentIds = $('#crcm-gallery-ids').val().split(',').filter(Boolean);

                    attachments.forEach(function(attachment) {
                        if (currentIds.indexOf(attachment.id.toString()) === -1) {
                            currentIds.push(attachment.id);

                            var imageHtml = '<div class="crcm-gallery-image" data-id="' + attachment.id + '">' +
                                '<img src="' + attachment.sizes.thumbnail.url + '" alt="" />' +
                                '<button type="button" class="crcm-remove-image">&times;</button>' +
                                '</div>';

                            $('#crcm-gallery-images').append(imageHtml);
                        }
                    });

                    $('#crcm-gallery-ids').val(currentIds.join(','));
                });

                mediaUploader.open();
            });

            $(document).on('click', '.crcm-remove-image', function() {
                var imageDiv = $(this).parent();
                var imageId = imageDiv.data('id');
                var currentIds = $('#crcm-gallery-ids').val().split(',').filter(Boolean);
                var index = currentIds.indexOf(imageId.toString());

                if (index > -1) {
                    currentIds.splice(index, 1);
                    $('#crcm-gallery-ids').val(currentIds.join(','));
                }

                imageDiv.remove();
            });
        });
        </script>

        <style>
        #crcm-gallery-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .crcm-gallery-image {
            position: relative;
            display: inline-block;
        }

        .crcm-gallery-image img {
            display: block;
            max-width: 80px;
            height: 80px;
            object-fit: cover;
            border: 2px solid #ddd;
            border-radius: 4px;
        }

        .crcm-remove-image {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            line-height: 1;
        }

        .crcm-remove-image:hover {
            background: #c82333;
        }
        </style>
        <?php
    }

    /**
     * Save meta data
     */
    public function save_meta_data($post_id) {
        // Verify nonce
        if (!isset($_POST['crcm_vehicle_meta_nonce']) || !wp_verify_nonce($_POST['crcm_vehicle_meta_nonce'], 'crcm_vehicle_meta')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check post type
        if (get_post_type($post_id) !== 'crcm_vehicle') {
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save vehicle data
        if (isset($_POST['crcm_vehicle'])) {
            $vehicle_data = array();
            $vehicle_data['seats'] = intval($_POST['crcm_vehicle']['seats']);
            $vehicle_data['transmission'] = sanitize_text_field($_POST['crcm_vehicle']['transmission']);
            $vehicle_data['fuel_type'] = sanitize_text_field($_POST['crcm_vehicle']['fuel_type']);
            $vehicle_data['doors'] = intval($_POST['crcm_vehicle']['doors']);
            $vehicle_data['luggage'] = intval($_POST['crcm_vehicle']['luggage']);
            $vehicle_data['air_conditioning'] = isset($_POST['crcm_vehicle']['air_conditioning']);
            $vehicle_data['stock_quantity'] = intval($_POST['crcm_vehicle']['stock_quantity']);
            $vehicle_data['min_age'] = intval($_POST['crcm_vehicle']['min_age']);
            $vehicle_data['license_type'] = sanitize_text_field($_POST['crcm_vehicle']['license_type']);
            $vehicle_data['deposit_amount'] = floatval($_POST['crcm_vehicle']['deposit_amount']);

            update_post_meta($post_id, '_crcm_vehicle_data', $vehicle_data);
        }

        // Save pricing data
        if (isset($_POST['crcm_pricing'])) {
            $pricing_data = array();
            $pricing_data['base_price'] = floatval($_POST['crcm_pricing']['base_price']);
            $pricing_data['weekend_price'] = floatval($_POST['crcm_pricing']['weekend_price']);
            $pricing_data['weekly_discount'] = floatval($_POST['crcm_pricing']['weekly_discount']);
            $pricing_data['monthly_discount'] = floatval($_POST['crcm_pricing']['monthly_discount']);
            $pricing_data['insurance_basic'] = floatval($_POST['crcm_pricing']['insurance_basic']);
            $pricing_data['insurance_premium'] = floatval($_POST['crcm_pricing']['insurance_premium']);

            update_post_meta($post_id, '_crcm_pricing_data', $pricing_data);
        }

        // Save gallery
        if (isset($_POST['crcm_gallery_ids'])) {
            $gallery_ids = array_filter(array_map('intval', explode(',', $_POST['crcm_gallery_ids'])));
            update_post_meta($post_id, '_crcm_gallery_ids', $gallery_ids);
        }
    }

    /**
     * Add custom columns to vehicle list
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['crcm_image'] = __('Image', 'custom-rental-manager');
        $new_columns['crcm_type'] = __('Type', 'custom-rental-manager');
        $new_columns['crcm_specs'] = __('Specifications', 'custom-rental-manager');
        $new_columns['crcm_price'] = __('Daily Rate', 'custom-rental-manager');
        $new_columns['crcm_stock'] = __('Stock', 'custom-rental-manager');
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    /**
     * Display custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'crcm_image':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(60, 60));
                } else {
                    echo '<span class="dashicons dashicons-car" style="font-size: 40px; color: #ccc;"></span>';
                }
                break;

            case 'crcm_type':
                $terms = get_the_terms($post_id, 'crcm_vehicle_type');
                if ($terms && !is_wp_error($terms)) {
                    $type_names = wp_list_pluck($terms, 'name');
                    echo implode(', ', $type_names);
                } else {
                    echo '-';
                }
                break;

            case 'crcm_specs':
                $vehicle_data = get_post_meta($post_id, '_crcm_vehicle_data', true);
                if ($vehicle_data) {
                    printf(
                        '%d %s • %s • %d %s',
                        $vehicle_data['seats'],
                        __('seats', 'custom-rental-manager'),
                        ucfirst($vehicle_data['transmission']),
                        $vehicle_data['luggage'],
                        __('bags', 'custom-rental-manager')
                    );
                }
                break;

            case 'crcm_price':
                $pricing_data = get_post_meta($post_id, '_crcm_pricing_data', true);
                if ($pricing_data && $pricing_data['base_price']) {
                    echo crcm()->get_setting('currency_symbol', '€') . number_format($pricing_data['base_price'], 2);
                } else {
                    echo '-';
                }
                break;

            case 'crcm_stock':
                $vehicle_data = get_post_meta($post_id, '_crcm_vehicle_data', true);
                if ($vehicle_data) {
                    echo $vehicle_data['stock_quantity'];
                } else {
                    echo '0';
                }
                break;
        }
    }

    /**
     * Search available vehicles
     */
    public function search_available_vehicles($search_params) {
        $pickup_date = $search_params['pickup_date'] ?? '';
        $return_date = $search_params['return_date'] ?? '';
        $vehicle_type = $search_params['vehicle_type'] ?? '';
        $location = $search_params['pickup_location'] ?? '';

        // Validate dates
        if (empty($pickup_date) || empty($return_date)) {
            return array();
        }

        $pickup_timestamp = strtotime($pickup_date);
        $return_timestamp = strtotime($return_date);

        if ($pickup_timestamp >= $return_timestamp) {
            return array();
        }

        // Build query arguments
        $args = array(
            'post_type' => 'crcm_vehicle',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_crcm_pricing_data',
                    'compare' => 'EXISTS',
                ),
            ),
        );

        // Add vehicle type filter
        if (!empty($vehicle_type)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'crcm_vehicle_type',
                    'field' => 'slug',
                    'terms' => $vehicle_type,
                ),
            );
        }

        // Add location filter
        if (!empty($location)) {
            if (!isset($args['tax_query'])) {
                $args['tax_query'] = array();
            }
            $args['tax_query'][] = array(
                'taxonomy' => 'crcm_location',
                'field' => 'term_id',
                'terms' => intval($location),
            );
        }

        $vehicles = get_posts($args);
        $available_vehicles = array();

        foreach ($vehicles as $vehicle) {
            $vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
            $pricing_data = get_post_meta($vehicle->ID, '_crcm_pricing_data', true);

            if (!$vehicle_data || !$pricing_data) {
                continue;
            }

            // Check availability
            $available_quantity = $this->check_availability($vehicle->ID, $pickup_date, $return_date);

            if ($available_quantity > 0) {
                $gallery_ids = get_post_meta($vehicle->ID, '_crcm_gallery_ids', true);
                $images = array();

                if (is_array($gallery_ids)) {
                    foreach ($gallery_ids as $image_id) {
                        $images[] = wp_get_attachment_image_url($image_id, 'medium');
                    }
                }

                // Calculate pricing
                $rental_days = ceil(($return_timestamp - $pickup_timestamp) / DAY_IN_SECONDS);
                $daily_rate = $this->calculate_daily_rate($vehicle->ID, $pickup_date, $return_date);
                $total_cost = $daily_rate * $rental_days;

                $available_vehicles[] = array(
                    'id' => $vehicle->ID,
                    'title' => $vehicle->post_title,
                    'description' => $vehicle->post_content,
                    'vehicle_data' => $vehicle_data,
                    'pricing_data' => $pricing_data,
                    'featured_image' => get_the_post_thumbnail_url($vehicle->ID, 'medium'),
                    'gallery' => $images,
                    'available_quantity' => $available_quantity,
                    'daily_rate' => $daily_rate,
                    'total_cost' => $total_cost,
                    'rental_days' => $rental_days,
                );
            }
        }

        // Sort by price
        usort($available_vehicles, function($a, $b) {
            return $a['daily_rate'] <=> $b['daily_rate'];
        });

        return $available_vehicles;
    }

    /**
     * Check vehicle availability for date range
     */
    public function check_availability($vehicle_id, $start_date, $end_date) {
        global $wpdb;

        $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
        $total_stock = $vehicle_data['stock_quantity'] ?? 1;

        // Check existing bookings
        $booking_args = array(
            'post_type' => 'crcm_booking',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_crcm_vehicle_id',
                    'value' => $vehicle_id,
                    'compare' => '=',
                ),
                array(
                    'key' => '_crcm_booking_status',
                    'value' => array('confirmed', 'active', 'pending'),
                    'compare' => 'IN',
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => '_crcm_pickup_date',
                            'value' => $start_date,
                            'compare' => '<=',
                            'type' => 'DATE',
                        ),
                        array(
                            'key' => '_crcm_return_date',
                            'value' => $start_date,
                            'compare' => '>',
                            'type' => 'DATE',
                        ),
                    ),
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => '_crcm_pickup_date',
                            'value' => $end_date,
                            'compare' => '<',
                            'type' => 'DATE',
                        ),
                        array(
                            'key' => '_crcm_return_date',
                            'value' => $end_date,
                            'compare' => '>=',
                            'type' => 'DATE',
                        ),
                    ),
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => '_crcm_pickup_date',
                            'value' => $start_date,
                            'compare' => '>=',
                            'type' => 'DATE',
                        ),
                        array(
                            'key' => '_crcm_return_date',
                            'value' => $end_date,
                            'compare' => '<=',
                            'type' => 'DATE',
                        ),
                    ),
                ),
            ),
        );

        $conflicting_bookings = get_posts($booking_args);
        $booked_quantity = count($conflicting_bookings);

        // Check custom availability overrides
        $availability_table = $wpdb->prefix . 'crcm_availability';
        $custom_availability = $wpdb->get_results($wpdb->prepare(
            "SELECT date, available_quantity FROM $availability_table 
             WHERE vehicle_id = %d AND date BETWEEN %s AND %s",
            $vehicle_id, $start_date, $end_date
        ));

        $min_available = $total_stock - $booked_quantity;

        foreach ($custom_availability as $override) {
            $override_available = $override->available_quantity - $booked_quantity;
            if ($override_available < $min_available) {
                $min_available = $override_available;
            }
        }

        return max(0, $min_available);
    }

    /**
     * Calculate daily rate considering pricing rules
     */
    public function calculate_daily_rate($vehicle_id, $start_date, $end_date) {
        $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
        $base_rate = $pricing_data['base_price'] ?? 0;

        if ($base_rate <= 0) {
            return 0;
        }

        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        $rental_days = ceil(($end_timestamp - $start_timestamp) / DAY_IN_SECONDS);

        // Apply discounts
        $discount = 0;

        if ($rental_days >= 30 && !empty($pricing_data['monthly_discount'])) {
            $discount = $pricing_data['monthly_discount'];
        } elseif ($rental_days >= 7 && !empty($pricing_data['weekly_discount'])) {
            $discount = $pricing_data['weekly_discount'];
        }

        $discounted_rate = $base_rate * (1 - ($discount / 100));

        return $discounted_rate;
    }

    /**
     * Get vehicle by ID with all data
     */
    public function get_vehicle($vehicle_id) {
        $vehicle = get_post($vehicle_id);

        if (!$vehicle || $vehicle->post_type !== 'crcm_vehicle') {
            return null;
        }

        $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
        $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
        $gallery_ids = get_post_meta($vehicle_id, '_crcm_gallery_ids', true);

        $images = array();
        if (is_array($gallery_ids)) {
            foreach ($gallery_ids as $image_id) {
                $images[] = wp_get_attachment_image_url($image_id, 'large');
            }
        }

        return array(
            'id' => $vehicle->ID,
            'title' => $vehicle->post_title,
            'description' => $vehicle->post_content,
            'vehicle_data' => $vehicle_data,
            'pricing_data' => $pricing_data,
            'featured_image' => get_the_post_thumbnail_url($vehicle_id, 'large'),
            'gallery' => $images,
        );
    }
}
