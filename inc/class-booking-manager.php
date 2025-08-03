<?php
/**
 * Booking Manager Class
 * 
 * Handles all booking operations including creation, management,
 * status updates, and pricing calculations.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRCM_Booking_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_booking_meta'));
        add_action('wp_ajax_crcm_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_nopriv_crcm_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_crcm_update_booking_status', array($this, 'ajax_update_booking_status'));
        add_filter('manage_crcm_booking_posts_columns', array($this, 'booking_columns'));
        add_action('manage_crcm_booking_posts_custom_column', array($this, 'booking_column_content'), 10, 2);
    }

    /**
     * Add meta boxes for booking post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'crcm_booking_details',
            __('Booking Details', 'custom-rental-manager'),
            array($this, 'booking_details_meta_box'),
            'crcm_booking',
            'normal',
            'high'
        );

        add_meta_box(
            'crcm_customer_info',
            __('Customer Information', 'custom-rental-manager'),
            array($this, 'customer_info_meta_box'),
            'crcm_booking',
            'normal',
            'high'
        );

        add_meta_box(
            'crcm_payment_info',
            __('Payment Information', 'custom-rental-manager'),
            array($this, 'payment_info_meta_box'),
            'crcm_booking',
            'side',
            'high'
        );

        add_meta_box(
            'crcm_booking_actions',
            __('Booking Actions', 'custom-rental-manager'),
            array($this, 'booking_actions_meta_box'),
            'crcm_booking',
            'side',
            'default'
        );
    }

    /**
     * Booking details meta box
     */
    public function booking_details_meta_box($post) {
        wp_nonce_field('crcm_booking_meta_nonce', 'crcm_booking_meta_nonce_field');

        $booking_data = get_post_meta($post->ID, '_crcm_booking_data', true);
        $booking_status = get_post_meta($post->ID, '_crcm_booking_status', true);
        $booking_number = get_post_meta($post->ID, '_crcm_booking_number', true);

        // Default values
        if (empty($booking_data)) {
            $booking_data = array(
                'vehicle_id' => '',
                'pickup_date' => '',
                'return_date' => '',
                'pickup_time' => '09:00',
                'return_time' => '18:00',
                'pickup_location' => '',
                'return_location' => '',
                'home_delivery' => false,
                'delivery_address' => '',
                'extras' => array(),
                'insurance_type' => 'basic',
                'notes' => '',
            );
        }

        if (empty($booking_status)) {
            $booking_status = 'pending';
        }

        // Get vehicles for dropdown
        $vehicles = get_posts(array(
            'post_type' => 'crcm_vehicle',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        // Get locations
        $locations = get_terms(array(
            'taxonomy' => 'crcm_location',
            'hide_empty' => false,
        ));
        ?>
        <table class="form-table crcm-form-table">
            <tr>
                <th><label for="crcm_booking_number"><?php _e('Booking Number', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_booking_number" name="crcm_booking_number" value="<?php echo esc_attr($booking_number); ?>" class="regular-text" readonly />
                    <p class="description"><?php _e('Auto-generated booking number', 'custom-rental-manager'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_booking_status"><?php _e('Booking Status', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_booking_status" name="crcm_booking_status">
                        <option value="pending" <?php selected($booking_status, 'pending'); ?>><?php _e('Pending', 'custom-rental-manager'); ?></option>
                        <option value="confirmed" <?php selected($booking_status, 'confirmed'); ?>><?php _e('Confirmed', 'custom-rental-manager'); ?></option>
                        <option value="active" <?php selected($booking_status, 'active'); ?>><?php _e('Active', 'custom-rental-manager'); ?></option>
                        <option value="completed" <?php selected($booking_status, 'completed'); ?>><?php _e('Completed', 'custom-rental-manager'); ?></option>
                        <option value="cancelled" <?php selected($booking_status, 'cancelled'); ?>><?php _e('Cancelled', 'custom-rental-manager'); ?></option>
                        <option value="refunded" <?php selected($booking_status, 'refunded'); ?>><?php _e('Refunded', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_vehicle_id"><?php _e('Vehicle', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_vehicle_id" name="booking_data[vehicle_id]" required>
                        <option value=""><?php _e('Select Vehicle', 'custom-rental-manager'); ?></option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo esc_attr($vehicle->ID); ?>" <?php selected($booking_data['vehicle_id'], $vehicle->ID); ?>>
                                <?php echo esc_html($vehicle->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_pickup_date"><?php _e('Pickup Date', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="date" id="crcm_pickup_date" name="booking_data[pickup_date]" value="<?php echo esc_attr($booking_data['pickup_date']); ?>" required />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_return_date"><?php _e('Return Date', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="date" id="crcm_return_date" name="booking_data[return_date]" value="<?php echo esc_attr($booking_data['return_date']); ?>" required />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_pickup_time"><?php _e('Pickup Time', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="time" id="crcm_pickup_time" name="booking_data[pickup_time]" value="<?php echo esc_attr($booking_data['pickup_time']); ?>" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_return_time"><?php _e('Return Time', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="time" id="crcm_return_time" name="booking_data[return_time]" value="<?php echo esc_attr($booking_data['return_time']); ?>" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_pickup_location"><?php _e('Pickup Location', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_pickup_location" name="booking_data[pickup_location]">
                        <option value=""><?php _e('Select Location', 'custom-rental-manager'); ?></option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo esc_attr($location->term_id); ?>" <?php selected($booking_data['pickup_location'], $location->term_id); ?>>
                                <?php echo esc_html($location->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_return_location"><?php _e('Return Location', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_return_location" name="booking_data[return_location]">
                        <option value=""><?php _e('Select Location', 'custom-rental-manager'); ?></option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo esc_attr($location->term_id); ?>" <?php selected($booking_data['return_location'], $location->term_id); ?>>
                                <?php echo esc_html($location->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label><?php _e('Home Delivery', 'custom-rental-manager'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="booking_data[home_delivery]" value="1" <?php checked($booking_data['home_delivery'], true); ?> />
                        <?php _e('Enable home delivery service', 'custom-rental-manager'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_delivery_address"><?php _e('Delivery Address', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea id="crcm_delivery_address" name="booking_data[delivery_address]" rows="3" class="large-text"><?php echo esc_textarea($booking_data['delivery_address']); ?></textarea>
                    <p class="description"><?php _e('Required if home delivery is enabled', 'custom-rental-manager'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_insurance_type"><?php _e('Insurance Type', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_insurance_type" name="booking_data[insurance_type]">
                        <option value="basic" <?php selected($booking_data['insurance_type'], 'basic'); ?>><?php _e('Basic (Included)', 'custom-rental-manager'); ?></option>
                        <option value="premium" <?php selected($booking_data['insurance_type'], 'premium'); ?>><?php _e('Premium (+€15/day)', 'custom-rental-manager'); ?></option>
                        <option value="full" <?php selected($booking_data['insurance_type'], 'full'); ?>><?php _e('Full Coverage (+€25/day)', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label><?php _e('Extras', 'custom-rental-manager'); ?></label></th>
                <td>
                    <?php
                    $available_extras = array(
                        'helmet' => array('name' => __('Helmet', 'custom-rental-manager'), 'price' => 5),
                        'child_seat' => array('name' => __('Child Seat', 'custom-rental-manager'), 'price' => 10),
                        'gps' => array('name' => __('GPS Navigation', 'custom-rental-manager'), 'price' => 8),
                        'phone_holder' => array('name' => __('Phone Holder', 'custom-rental-manager'), 'price' => 3),
                    );

                    $selected_extras = is_array($booking_data['extras']) ? $booking_data['extras'] : array();

                    foreach ($available_extras as $key => $extra): ?>
                        <label style="display: block; margin-bottom: 5px;">
                            <input type="checkbox" name="booking_data[extras][]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $selected_extras)); ?> />
                            <?php echo esc_html($extra['name']) . ' (+€' . $extra['price'] . '/day)'; ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_booking_notes"><?php _e('Notes', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea id="crcm_booking_notes" name="booking_data[notes]" rows="4" class="large-text"><?php echo esc_textarea($booking_data['notes']); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Customer information meta box
     */
    public function customer_info_meta_box($post) {
        $customer_data = get_post_meta($post->ID, '_crcm_customer_data', true);

        // Default values
        if (empty($customer_data)) {
            $customer_data = array(
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'date_of_birth' => '',
                'license_number' => '',
                'license_country' => 'IT',
                'address' => '',
                'city' => '',
                'postal_code' => '',
                'country' => 'IT',
            );
        }
        ?>
        <table class="form-table crcm-form-table">
            <tr>
                <th><label for="crcm_first_name"><?php _e('First Name', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_first_name" name="customer_data[first_name]" value="<?php echo esc_attr($customer_data['first_name']); ?>" class="regular-text" required />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_last_name"><?php _e('Last Name', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_last_name" name="customer_data[last_name]" value="<?php echo esc_attr($customer_data['last_name']); ?>" class="regular-text" required />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_email"><?php _e('Email', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="email" id="crcm_email" name="customer_data[email]" value="<?php echo esc_attr($customer_data['email']); ?>" class="regular-text" required />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_phone"><?php _e('Phone', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="tel" id="crcm_phone" name="customer_data[phone]" value="<?php echo esc_attr($customer_data['phone']); ?>" class="regular-text" required />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_date_of_birth"><?php _e('Date of Birth', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="date" id="crcm_date_of_birth" name="customer_data[date_of_birth]" value="<?php echo esc_attr($customer_data['date_of_birth']); ?>" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_license_number"><?php _e('License Number', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_license_number" name="customer_data[license_number]" value="<?php echo esc_attr($customer_data['license_number']); ?>" class="regular-text" required />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_license_country"><?php _e('License Country', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_license_country" name="customer_data[license_country]">
                        <?php
                        $countries = crcm_get_country_options();
                        foreach ($countries as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($customer_data['license_country'], $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="crcm_address"><?php _e('Address', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_address" name="customer_data[address]" value="<?php echo esc_attr($customer_data['address']); ?>" class="large-text" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_city"><?php _e('City', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_city" name="customer_data[city]" value="<?php echo esc_attr($customer_data['city']); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_postal_code"><?php _e('Postal Code', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_postal_code" name="customer_data[postal_code]" value="<?php echo esc_attr($customer_data['postal_code']); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="crcm_country"><?php _e('Country', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_country" name="customer_data[country]">
                        <?php foreach ($countries as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($customer_data['country'], $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Payment information meta box
     */
    public function payment_info_meta_box($post) {
        $payment_data = get_post_meta($post->ID, '_crcm_payment_data', true);

        // Default values
        if (empty($payment_data)) {
            $payment_data = array(
                'subtotal' => 0,
                'extras_cost' => 0,
                'insurance_cost' => 0,
                'delivery_cost' => 0,
                'total_cost' => 0,
                'deposit_amount' => 0,
                'paid_amount' => 0,
                'payment_status' => 'pending',
                'payment_method' => '',
                'stripe_payment_intent' => '',
                'refund_amount' => 0,
                'refund_reason' => '',
            );
        }

        $currency_symbol = crcm()->get_setting('currency_symbol', '€');
        ?>
        <table class="form-table crcm-form-table">
            <tr>
                <th><?php _e('Subtotal', 'custom-rental-manager'); ?></th>
                <td><strong><?php echo $currency_symbol . number_format($payment_data['subtotal'], 2); ?></strong></td>
            </tr>

            <tr>
                <th><?php _e('Extras Cost', 'custom-rental-manager'); ?></th>
                <td><?php echo $currency_symbol . number_format($payment_data['extras_cost'], 2); ?></td>
            </tr>

            <tr>
                <th><?php _e('Insurance Cost', 'custom-rental-manager'); ?></th>
                <td><?php echo $currency_symbol . number_format($payment_data['insurance_cost'], 2); ?></td>
            </tr>

            <tr>
                <th><?php _e('Delivery Cost', 'custom-rental-manager'); ?></th>
                <td><?php echo $currency_symbol . number_format($payment_data['delivery_cost'], 2); ?></td>
            </tr>

            <tr>
                <th><?php _e('Total Cost', 'custom-rental-manager'); ?></th>
                <td><strong style="font-size: 16px; color: #0073aa;"><?php echo $currency_symbol . number_format($payment_data['total_cost'], 2); ?></strong></td>
            </tr>

            <tr>
                <th><?php _e('Deposit Required', 'custom-rental-manager'); ?></th>
                <td><?php echo $currency_symbol . number_format($payment_data['deposit_amount'], 2); ?></td>
            </tr>

            <tr>
                <th><?php _e('Paid Amount', 'custom-rental-manager'); ?></th>
                <td>
                    <strong style="color: #46b450;"><?php echo $currency_symbol . number_format($payment_data['paid_amount'], 2); ?></strong>
                    <?php if ($payment_data['paid_amount'] < $payment_data['total_cost']): ?>
                        <br><small style="color: #d63638;">
                            <?php printf(__('Remaining: %s', 'custom-rental-manager'), $currency_symbol . number_format($payment_data['total_cost'] - $payment_data['paid_amount'], 2)); ?>
                        </small>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <th><?php _e('Payment Status', 'custom-rental-manager'); ?></th>
                <td>
                    <?php
                    $status_labels = array(
                        'pending' => __('Pending', 'custom-rental-manager'),
                        'partial' => __('Partial', 'custom-rental-manager'),
                        'completed' => __('Completed', 'custom-rental-manager'),
                        'refunded' => __('Refunded', 'custom-rental-manager'),
                        'failed' => __('Failed', 'custom-rental-manager'),
                    );

                    $status_colors = array(
                        'pending' => '#f0ad4e',
                        'partial' => '#5bc0de',
                        'completed' => '#5cb85c',
                        'refunded' => '#d9534f',
                        'failed' => '#d9534f',
                    );

                    $status = $payment_data['payment_status'];
                    $color = isset($status_colors[$status]) ? $status_colors[$status] : '#666';
                    ?>
                    <span style="background: <?php echo $color; ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold;">
                        <?php echo isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status); ?>
                    </span>
                </td>
            </tr>

            <tr>
                <th><?php _e('Payment Method', 'custom-rental-manager'); ?></th>
                <td><?php echo esc_html($payment_data['payment_method'] ? ucfirst($payment_data['payment_method']) : __('Not set', 'custom-rental-manager')); ?></td>
            </tr>

            <?php if (!empty($payment_data['stripe_payment_intent'])): ?>
            <tr>
                <th><?php _e('Stripe Payment ID', 'custom-rental-manager'); ?></th>
                <td><code><?php echo esc_html($payment_data['stripe_payment_intent']); ?></code></td>
            </tr>
            <?php endif; ?>

            <?php if ($payment_data['refund_amount'] > 0): ?>
            <tr>
                <th><?php _e('Refund Amount', 'custom-rental-manager'); ?></th>
                <td>
                    <strong style="color: #d63638;"><?php echo $currency_symbol . number_format($payment_data['refund_amount'], 2); ?></strong>
                    <?php if (!empty($payment_data['refund_reason'])): ?>
                        <br><small><?php echo esc_html($payment_data['refund_reason']); ?></small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    /**
     * Booking actions meta box
     */
    public function booking_actions_meta_box($post) {
        $booking_status = get_post_meta($post->ID, '_crcm_booking_status', true);
        $payment_data = get_post_meta($post->ID, '_crcm_payment_data', true);
        ?>
        <div class="crcm-booking-actions">
            <?php if ($booking_status === 'pending'): ?>
                <p>
                    <button type="button" class="button button-primary button-large" id="crcm-confirm-booking">
                        <?php _e('Confirm Booking', 'custom-rental-manager'); ?>
                    </button>
                </p>
            <?php endif; ?>

            <?php if ($booking_status === 'confirmed'): ?>
                <p>
                    <button type="button" class="button button-primary button-large" id="crcm-activate-booking">
                        <?php _e('Start Rental', 'custom-rental-manager'); ?>
                    </button>
                </p>
            <?php endif; ?>

            <?php if ($booking_status === 'active'): ?>
                <p>
                    <button type="button" class="button button-primary button-large" id="crcm-complete-booking">
                        <?php _e('Complete Rental', 'custom-rental-manager'); ?>
                    </button>
                </p>
            <?php endif; ?>

            <p>
                <button type="button" class="button" id="crcm-send-email">
                    <?php _e('Send Email to Customer', 'custom-rental-manager'); ?>
                </button>
            </p>

            <p>
                <button type="button" class="button" id="crcm-generate-contract">
                    <?php _e('Generate Contract', 'custom-rental-manager'); ?>
                </button>
            </p>

            <?php if ($payment_data && $payment_data['paid_amount'] > 0): ?>
            <p>
                <button type="button" class="button" id="crcm-process-refund">
                    <?php _e('Process Refund', 'custom-rental-manager'); ?>
                </button>
            </p>
            <?php endif; ?>

            <?php if (in_array($booking_status, array('pending', 'confirmed'))): ?>
            <p>
                <button type="button" class="button button-link-delete" id="crcm-cancel-booking">
                    <?php _e('Cancel Booking', 'custom-rental-manager'); ?>
                </button>
            </p>
            <?php endif; ?>
        </div>

        <style>
        .crcm-booking-actions .button-large {
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
        }

        .crcm-booking-actions .button {
            width: 100%;
            text-align: center;
            margin-bottom: 5px;
        }
        </style>
        <?php
    }

    /**
     * Save booking meta data
     */
    public function save_booking_meta($post_id) {
        // Verify nonce
        if (!isset($_POST['crcm_booking_meta_nonce_field']) || !wp_verify_nonce($_POST['crcm_booking_meta_nonce_field'], 'crcm_booking_meta_nonce')) {
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

        // Only save for booking post type
        if (get_post_type($post_id) !== 'crcm_booking') {
            return;
        }

        // Save booking data
        if (isset($_POST['booking_data'])) {
            $booking_data = array();
            foreach ($_POST['booking_data'] as $key => $value) {
                if ($key === 'extras' && is_array($value)) {
                    $booking_data[$key] = array_map('sanitize_text_field', $value);
                } elseif ($key === 'home_delivery') {
                    $booking_data[$key] = (bool) $value;
                } else {
                    $booking_data[$key] = sanitize_text_field($value);
                }
            }
            update_post_meta($post_id, '_crcm_booking_data', $booking_data);
        }

        // Save customer data
        if (isset($_POST['customer_data'])) {
            $customer_data = array();
            foreach ($_POST['customer_data'] as $key => $value) {
                $customer_data[$key] = sanitize_text_field($value);
            }
            update_post_meta($post_id, '_crcm_customer_data', $customer_data);
        }

        // Save booking status
        if (isset($_POST['crcm_booking_status'])) {
            $old_status = get_post_meta($post_id, '_crcm_booking_status', true);
            $new_status = sanitize_text_field($_POST['crcm_booking_status']);

            update_post_meta($post_id, '_crcm_booking_status', $new_status);

            // Trigger status change action if status changed
            if ($old_status !== $new_status) {
                do_action('crcm_booking_status_changed', $post_id, $new_status, $old_status);
            }
        }

        // Generate booking number if not exists
        $booking_number = get_post_meta($post_id, '_crcm_booking_number', true);
        if (empty($booking_number)) {
            $booking_number = $this->generate_booking_number();
            update_post_meta($post_id, '_crcm_booking_number', $booking_number);
        }

        // Save booking number if provided
        if (isset($_POST['crcm_booking_number']) && !empty($_POST['crcm_booking_number'])) {
            update_post_meta($post_id, '_crcm_booking_number', sanitize_text_field($_POST['crcm_booking_number']));
        }

        // Recalculate pricing
        $this->recalculate_booking_pricing($post_id);
    }

    /**
     * Set up custom columns for booking list
     */
    public function booking_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Booking Number', 'custom-rental-manager');
        $new_columns['crcm_customer'] = __('Customer', 'custom-rental-manager');
        $new_columns['crcm_vehicle'] = __('Vehicle', 'custom-rental-manager');
        $new_columns['crcm_dates'] = __('Rental Period', 'custom-rental-manager');
        $new_columns['crcm_status'] = __('Status', 'custom-rental-manager');
        $new_columns['crcm_total'] = __('Total', 'custom-rental-manager');
        $new_columns['date'] = __('Created', 'custom-rental-manager');

        return $new_columns;
    }

    /**
     * Display custom column content
     */
    public function booking_column_content($column, $post_id) {
        $booking_data = get_post_meta($post_id, '_crcm_booking_data', true);
        $customer_data = get_post_meta($post_id, '_crcm_customer_data', true);
        $payment_data = get_post_meta($post_id, '_crcm_payment_data', true);
        $booking_status = get_post_meta($post_id, '_crcm_booking_status', true);
        $currency_symbol = crcm()->get_setting('currency_symbol', '€');

        switch ($column) {
            case 'crcm_customer':
                if ($customer_data) {
                    echo esc_html($customer_data['first_name'] . ' ' . $customer_data['last_name']);
                    echo '<br><small>' . esc_html($customer_data['email']) . '</small>';
                }
                break;

            case 'crcm_vehicle':
                if ($booking_data && !empty($booking_data['vehicle_id'])) {
                    $vehicle = get_post($booking_data['vehicle_id']);
                    if ($vehicle) {
                        echo '<a href="' . get_edit_post_link($vehicle->ID) . '">' . esc_html($vehicle->post_title) . '</a>';
                    }
                }
                break;

            case 'crcm_dates':
                if ($booking_data) {
                    echo esc_html(crcm_format_date($booking_data['pickup_date']));
                    echo '<br><small>' . __('to', 'custom-rental-manager') . ' ' . esc_html(crcm_format_date($booking_data['return_date'])) . '</small>';
                }
                break;

            case 'crcm_status':
                if ($booking_status) {
                    echo crcm_get_status_badge($booking_status);
                }
                break;

            case 'crcm_total':
                if ($payment_data && isset($payment_data['total_cost'])) {
                    echo '<strong>' . $currency_symbol . number_format($payment_data['total_cost'], 2) . '</strong>';
                    if ($payment_data['paid_amount'] > 0) {
                        echo '<br><small style="color: #46b450;">' . __('Paid:', 'custom-rental-manager') . ' ' . $currency_symbol . number_format($payment_data['paid_amount'], 2) . '</small>';
                    }
                }
                break;
        }
    }

    /**
     * Create booking from frontend
     */
    public function ajax_create_booking() {
        check_ajax_referer('crcm_nonce', 'nonce');

        // Validate required fields
        $required_fields = array('vehicle_id', 'pickup_date', 'return_date', 'customer_data');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(__('Missing required field: ', 'custom-rental-manager') . $field);
            }
        }

        // Sanitize and prepare data
        $booking_data = array(
            'vehicle_id' => intval($_POST['vehicle_id']),
            'pickup_date' => sanitize_text_field($_POST['pickup_date']),
            'return_date' => sanitize_text_field($_POST['return_date']),
            'pickup_time' => sanitize_text_field($_POST['pickup_time'] ?? '09:00'),
            'return_time' => sanitize_text_field($_POST['return_time'] ?? '18:00'),
            'pickup_location' => intval($_POST['pickup_location'] ?? 0),
            'return_location' => intval($_POST['return_location'] ?? 0),
            'home_delivery' => (bool) ($_POST['home_delivery'] ?? false),
            'delivery_address' => sanitize_textarea_field($_POST['delivery_address'] ?? ''),
            'extras' => array_map('sanitize_text_field', $_POST['extras'] ?? array()),
            'insurance_type' => sanitize_text_field($_POST['insurance_type'] ?? 'basic'),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
        );

        $customer_data = array();
        if (isset($_POST['customer_data']) && is_array($_POST['customer_data'])) {
            foreach ($_POST['customer_data'] as $key => $value) {
                $customer_data[$key] = sanitize_text_field($value);
            }
        }

        // Validate availability
        $vehicle_manager = new CRCM_Vehicle_Manager();
        $available_quantity = $vehicle_manager->check_availability(
            $booking_data['vehicle_id'],
            $booking_data['pickup_date'],
            $booking_data['return_date']
        );

        if ($available_quantity < 1) {
            wp_send_json_error(__('Selected vehicle is not available for the chosen dates.', 'custom-rental-manager'));
        }

        // Create booking post
        $booking_post = array(
            'post_type' => 'crcm_booking',
            'post_status' => 'publish',
            'post_title' => sprintf(__('Booking - %s %s', 'custom-rental-manager'), $customer_data['first_name'], $customer_data['last_name']),
        );

        $booking_id = wp_insert_post($booking_post);

        if (is_wp_error($booking_id)) {
            wp_send_json_error(__('Failed to create booking.', 'custom-rental-manager'));
        }

        // Save booking data
        update_post_meta($booking_id, '_crcm_booking_data', $booking_data);
        update_post_meta($booking_id, '_crcm_customer_data', $customer_data);
        update_post_meta($booking_id, '_crcm_booking_status', 'pending');

        // Generate booking number
        $booking_number = $this->generate_booking_number();
        update_post_meta($booking_id, '_crcm_booking_number', $booking_number);

        // Calculate pricing
        $pricing = $this->calculate_booking_pricing($booking_data);
        update_post_meta($booking_id, '_crcm_payment_data', $pricing);

        // Create customer account if doesn't exist
        $user = get_user_by('email', $customer_data['email']);
        if (!$user) {
            $customer_user_id = crcm_create_customer_account($customer_data);
        }

        // Trigger booking created action
        do_action('crcm_booking_created', $booking_id);

        wp_send_json_success(array(
            'booking_id' => $booking_id,
            'booking_number' => $booking_number,
            'message' => __('Booking created successfully!', 'custom-rental-manager'),
            'redirect_url' => home_url('/booking-confirmation/?booking=' . $booking_number),
        ));
    }

    /**
     * Update booking status via AJAX
     */
    public function ajax_update_booking_status() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'custom-rental-manager'));
        }

        $booking_id = intval($_POST['booking_id']);
        $new_status = sanitize_text_field($_POST['status']);

        $old_status = get_post_meta($booking_id, '_crcm_booking_status', true);
        update_post_meta($booking_id, '_crcm_booking_status', $new_status);

        // Trigger status change action
        do_action('crcm_booking_status_changed', $booking_id, $new_status, $old_status);

        wp_send_json_success(array(
            'message' => __('Booking status updated successfully', 'custom-rental-manager'),
        ));
    }

    /**
     * Generate unique booking number
     */
    public function generate_booking_number() {
        $prefix = 'ISCHIA' . date('Y');
        $counter = get_option('crcm_booking_counter_' . date('Y'), 0);
        $counter++;

        update_option('crcm_booking_counter_' . date('Y'), $counter);

        return $prefix . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate booking pricing
     */
    public function calculate_booking_pricing($booking_data) {
        $vehicle_id = $booking_data['vehicle_id'];
        $pickup_date = $booking_data['pickup_date'];
        $return_date = $booking_data['return_date'];
        $extras = isset($booking_data['extras']) ? $booking_data['extras'] : array();
        $insurance_type = isset($booking_data['insurance_type']) ? $booking_data['insurance_type'] : 'basic';
        $home_delivery = isset($booking_data['home_delivery']) ? $booking_data['home_delivery'] : false;

        // Calculate rental days
        $rental_days = crcm_calculate_rental_days($pickup_date, $return_date);

        if ($rental_days <= 0) {
            return array(
                'subtotal' => 0,
                'extras_cost' => 0,
                'insurance_cost' => 0,
                'delivery_cost' => 0,
                'total_cost' => 0,
                'deposit_amount' => 0,
                'paid_amount' => 0,
                'payment_status' => 'pending',
            );
        }

        // Get vehicle pricing
        $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
        $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);

        $daily_rate = floatval($pricing_data['daily_rate'] ?? 0);

        // Apply weekly/monthly discounts
        $weekly_discount = floatval($pricing_data['weekly_discount'] ?? 0);
        $monthly_discount = floatval($pricing_data['monthly_discount'] ?? 0);

        $subtotal = $daily_rate * $rental_days;

        if ($rental_days >= 30 && $monthly_discount > 0) {
            $subtotal = $subtotal * (1 - $monthly_discount / 100);
        } elseif ($rental_days >= 7 && $weekly_discount > 0) {
            $subtotal = $subtotal * (1 - $weekly_discount / 100);
        }

        // Calculate extras cost
        $extras_cost = 0;
        $available_extras = array(
            'helmet' => 5,
            'child_seat' => 10,
            'gps' => 8,
            'phone_holder' => 3,
        );

        foreach ($extras as $extra) {
            if (isset($available_extras[$extra])) {
                $extras_cost += $available_extras[$extra] * $rental_days;
            }
        }

        // Calculate insurance cost
        $insurance_cost = 0;
        $insurance_rates = array(
            'basic' => 0,
            'premium' => 15,
            'full' => 25,
        );

        if (isset($insurance_rates[$insurance_type])) {
            $insurance_cost = $insurance_rates[$insurance_type] * $rental_days;
        }

        // Calculate delivery cost
        $delivery_cost = 0;
        if ($home_delivery) {
            $delivery_rate = crcm()->get_setting('home_delivery_rate', 25);
            $delivery_cost = floatval($delivery_rate);
        }

        // Calculate total
        $total_cost = $subtotal + $extras_cost + $insurance_cost + $delivery_cost;

        // Calculate deposit (typically 30% of total or minimum €200)
        $deposit_percentage = crcm()->get_setting('deposit_percentage', 30);
        $minimum_deposit = crcm()->get_setting('minimum_deposit', 200);
        $deposit_amount = max($total_cost * ($deposit_percentage / 100), $minimum_deposit);

        return array(
            'subtotal' => round($subtotal, 2),
            'extras_cost' => round($extras_cost, 2),
            'insurance_cost' => round($insurance_cost, 2),
            'delivery_cost' => round($delivery_cost, 2),
            'total_cost' => round($total_cost, 2),
            'deposit_amount' => round($deposit_amount, 2),
            'rental_days' => $rental_days,
            'daily_rate' => $daily_rate,
            'paid_amount' => 0,
            'payment_status' => 'pending',
            'payment_method' => '',
            'stripe_payment_intent' => '',
            'refund_amount' => 0,
            'refund_reason' => '',
        );
    }

    /**
     * Recalculate booking pricing
     */
    public function recalculate_booking_pricing($booking_id) {
        $booking_data = get_post_meta($booking_id, '_crcm_booking_data', true);

        if ($booking_data) {
            $pricing = $this->calculate_booking_pricing($booking_data);

            // Preserve existing payment data
            $existing_payment_data = get_post_meta($booking_id, '_crcm_payment_data', true);
            if ($existing_payment_data) {
                $pricing['paid_amount'] = $existing_payment_data['paid_amount'] ?? 0;
                $pricing['payment_status'] = $existing_payment_data['payment_status'] ?? 'pending';
                $pricing['payment_method'] = $existing_payment_data['payment_method'] ?? '';
                $pricing['stripe_payment_intent'] = $existing_payment_data['stripe_payment_intent'] ?? '';
                $pricing['refund_amount'] = $existing_payment_data['refund_amount'] ?? 0;
                $pricing['refund_reason'] = $existing_payment_data['refund_reason'] ?? '';
            }

            update_post_meta($booking_id, '_crcm_payment_data', $pricing);
        }
    }

    /**
     * Get booking by ID
     */
    public function get_booking($booking_id) {
        $booking = get_post($booking_id);

        if (!$booking || $booking->post_type !== 'crcm_booking') {
            return false;
        }

        $booking_data = get_post_meta($booking_id, '_crcm_booking_data', true);
        $customer_data = get_post_meta($booking_id, '_crcm_customer_data', true);
        $payment_data = get_post_meta($booking_id, '_crcm_payment_data', true);
        $booking_status = get_post_meta($booking_id, '_crcm_booking_status', true);
        $booking_number = get_post_meta($booking_id, '_crcm_booking_number', true);

        return array(
            'id' => $booking_id,
            'booking_number' => $booking_number,
            'status' => $booking_status,
            'booking_data' => $booking_data,
            'customer_data' => $customer_data,
            'payment_data' => $payment_data,
            'created_date' => $booking->post_date,
            'modified_date' => $booking->post_modified,
        );
    }

    /**
     * Get bookings with filters
     */
    public function get_bookings($args = array()) {
        $default_args = array(
            'post_type' => 'crcm_booking',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $default_args);

        $posts = get_posts($args);
        $bookings = array();

        foreach ($posts as $post) {
            $booking = $this->get_booking($post->ID);
            if ($booking) {
                $bookings[] = $booking;
            }
        }

        return $bookings;
    }
}
