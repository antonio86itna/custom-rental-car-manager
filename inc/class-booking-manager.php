<?php
/**
 * Booking Manager Class
 * 
 * Handles all booking-related operations including creation, updates,
 * status management, cancellations, and customer communications.
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
     * Booking statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_data'));
        add_filter('manage_crcm_booking_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_crcm_booking_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_action('post_row_actions', array($this, 'add_row_actions'), 10, 2);
        add_action('wp_ajax_crcm_update_booking_status', array($this, 'ajax_update_booking_status'));
    }

    /**
     * Add meta boxes for booking post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'crcm_booking_details',
            __('Booking Details', 'custom-rental-manager'),
            array($this, 'render_booking_details_meta_box'),
            'crcm_booking',
            'normal',
            'high'
        );

        add_meta_box(
            'crcm_customer_details',
            __('Customer Information', 'custom-rental-manager'),
            array($this, 'render_customer_details_meta_box'),
            'crcm_booking',
            'normal',
            'high'
        );

        add_meta_box(
            'crcm_payment_details',
            __('Payment & Pricing', 'custom-rental-manager'),
            array($this, 'render_payment_details_meta_box'),
            'crcm_booking',
            'side',
            'default'
        );

        add_meta_box(
            'crcm_booking_actions',
            __('Booking Actions', 'custom-rental-manager'),
            array($this, 'render_booking_actions_meta_box'),
            'crcm_booking',
            'side',
            'high'
        );
    }

    /**
     * Render booking details meta box
     */
    public function render_booking_details_meta_box($post) {
        wp_nonce_field('crcm_booking_meta', 'crcm_booking_meta_nonce');

        $booking_data = get_post_meta($post->ID, '_crcm_booking_data', true);
        $defaults = array(
            'vehicle_id' => 0,
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

        $booking_data = wp_parse_args($booking_data, $defaults);

        // Get available vehicles
        $vehicles = get_posts(array(
            'post_type' => 'crcm_vehicle',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));

        // Get locations
        $locations = get_terms(array(
            'taxonomy' => 'crcm_location',
            'hide_empty' => false,
        ));

        // Get extras
        $extras = get_posts(array(
            'post_type' => 'crcm_extra',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));
        ?>
        <table class="form-table">
            <tr>
                <th><label for="crcm_vehicle_id"><?php _e('Vehicle', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_vehicle_id" name="crcm_booking[vehicle_id]" required>
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
                    <input type="date" id="crcm_pickup_date" name="crcm_booking[pickup_date]" value="<?php echo esc_attr($booking_data['pickup_date']); ?>" required />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_return_date"><?php _e('Return Date', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="date" id="crcm_return_date" name="crcm_booking[return_date]" value="<?php echo esc_attr($booking_data['return_date']); ?>" required />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_pickup_time"><?php _e('Pickup Time', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="time" id="crcm_pickup_time" name="crcm_booking[pickup_time]" value="<?php echo esc_attr($booking_data['pickup_time']); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_return_time"><?php _e('Return Time', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="time" id="crcm_return_time" name="crcm_booking[return_time]" value="<?php echo esc_attr($booking_data['return_time']); ?>" />
                    <p class="description"><?php printf(__('Late returns after %s will incur an extra day charge', 'custom-rental-manager'), crcm()->get_setting('late_return_time', '10:00')); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Delivery Options', 'custom-rental-manager'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="crcm_booking[home_delivery]" value="1" <?php checked($booking_data['home_delivery'], true); ?> />
                        <?php _e('Home Delivery (Free)', 'custom-rental-manager'); ?>
                    </label>
                    <div id="delivery-address-field" style="margin-top: 10px; <?php echo $booking_data['home_delivery'] ? '' : 'display:none;'; ?>">
                        <input type="text" name="crcm_booking[delivery_address]" value="<?php echo esc_attr($booking_data['delivery_address']); ?>" placeholder="<?php _e('Delivery address', 'custom-rental-manager'); ?>" style="width: 100%;" />
                    </div>
                </td>
            </tr>
            <tr id="location-fields" style="<?php echo $booking_data['home_delivery'] ? 'display:none;' : ''; ?>">
                <th><?php _e('Pickup/Return Locations', 'custom-rental-manager'); ?></th>
                <td>
                    <div style="margin-bottom: 10px;">
                        <label><?php _e('Pickup Location:', 'custom-rental-manager'); ?></label>
                        <select name="crcm_booking[pickup_location]" style="width: 100%;">
                            <option value=""><?php _e('Select Location', 'custom-rental-manager'); ?></option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo esc_attr($location->term_id); ?>" <?php selected($booking_data['pickup_location'], $location->term_id); ?>>
                                    <?php echo esc_html($location->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label><?php _e('Return Location:', 'custom-rental-manager'); ?></label>
                        <select name="crcm_booking[return_location]" style="width: 100%;">
                            <option value=""><?php _e('Select Location', 'custom-rental-manager'); ?></option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo esc_attr($location->term_id); ?>" <?php selected($booking_data['return_location'], $location->term_id); ?>>
                                    <?php echo esc_html($location->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <th><?php _e('Insurance Type', 'custom-rental-manager'); ?></th>
                <td>
                    <label>
                        <input type="radio" name="crcm_booking[insurance_type]" value="basic" <?php checked($booking_data['insurance_type'], 'basic'); ?> />
                        <?php _e('Basic Insurance (Included)', 'custom-rental-manager'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="crcm_booking[insurance_type]" value="premium" <?php checked($booking_data['insurance_type'], 'premium'); ?> />
                        <?php _e('Premium Insurance (Reduced Deductible)', 'custom-rental-manager'); ?>
                    </label>
                </td>
            </tr>
            <?php if (!empty($extras)): ?>
            <tr>
                <th><?php _e('Extra Services', 'custom-rental-manager'); ?></th>
                <td>
                    <?php foreach ($extras as $extra): ?>
                        <?php 
                        $extra_data = get_post_meta($extra->ID, '_crcm_extra_data', true);
                        $price = $extra_data['price'] ?? 0;
                        ?>
                        <label style="display: block; margin-bottom: 5px;">
                            <input type="checkbox" name="crcm_booking[extras][]" value="<?php echo esc_attr($extra->ID); ?>" <?php checked(in_array($extra->ID, $booking_data['extras'])); ?> />
                            <?php echo esc_html($extra->post_title); ?>
                            <?php if ($price > 0): ?>
                                (<?php echo crcm()->get_setting('currency_symbol', '€') . number_format($price, 2); ?>/<?php _e('day', 'custom-rental-manager'); ?>)
                            <?php else: ?>
                                (<?php _e('Free', 'custom-rental-manager'); ?>)
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><label for="crcm_notes"><?php _e('Notes', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea id="crcm_notes" name="crcm_booking[notes]" rows="3" style="width: 100%;"><?php echo esc_textarea($booking_data['notes']); ?></textarea>
                </td>
            </tr>
        </table>

        <script>
        jQuery(document).ready(function($) {
            $('input[name="crcm_booking[home_delivery]"]').change(function() {
                if ($(this).is(':checked')) {
                    $('#delivery-address-field').show();
                    $('#location-fields').hide();
                } else {
                    $('#delivery-address-field').hide();
                    $('#location-fields').show();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render customer details meta box
     */
    public function render_customer_details_meta_box($post) {
        $customer_data = get_post_meta($post->ID, '_crcm_customer_data', true);
        $defaults = array(
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'date_of_birth' => '',
            'license_number' => '',
            'license_country' => '',
            'address' => '',
            'city' => '',
            'postal_code' => '',
            'country' => '',
            'emergency_contact' => '',
            'emergency_phone' => '',
        );

        $customer_data = wp_parse_args($customer_data, $defaults);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="crcm_first_name"><?php _e('First Name', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_first_name" name="crcm_customer[first_name]" value="<?php echo esc_attr($customer_data['first_name']); ?>" required />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_last_name"><?php _e('Last Name', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_last_name" name="crcm_customer[last_name]" value="<?php echo esc_attr($customer_data['last_name']); ?>" required />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_email"><?php _e('Email', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="email" id="crcm_email" name="crcm_customer[email]" value="<?php echo esc_attr($customer_data['email']); ?>" required />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_phone"><?php _e('Phone', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="tel" id="crcm_phone" name="crcm_customer[phone]" value="<?php echo esc_attr($customer_data['phone']); ?>" required />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_date_of_birth"><?php _e('Date of Birth', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="date" id="crcm_date_of_birth" name="crcm_customer[date_of_birth]" value="<?php echo esc_attr($customer_data['date_of_birth']); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_license_number"><?php _e('License Number', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_license_number" name="crcm_customer[license_number]" value="<?php echo esc_attr($customer_data['license_number']); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_license_country"><?php _e('License Country', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="crcm_license_country" name="crcm_customer[license_country]">
                        <option value=""><?php _e('Select Country', 'custom-rental-manager'); ?></option>
                        <option value="IT" <?php selected($customer_data['license_country'], 'IT'); ?>><?php _e('Italy', 'custom-rental-manager'); ?></option>
                        <option value="DE" <?php selected($customer_data['license_country'], 'DE'); ?>><?php _e('Germany', 'custom-rental-manager'); ?></option>
                        <option value="FR" <?php selected($customer_data['license_country'], 'FR'); ?>><?php _e('France', 'custom-rental-manager'); ?></option>
                        <option value="ES" <?php selected($customer_data['license_country'], 'ES'); ?>><?php _e('Spain', 'custom-rental-manager'); ?></option>
                        <option value="UK" <?php selected($customer_data['license_country'], 'UK'); ?>><?php _e('United Kingdom', 'custom-rental-manager'); ?></option>
                        <option value="US" <?php selected($customer_data['license_country'], 'US'); ?>><?php _e('United States', 'custom-rental-manager'); ?></option>
                        <option value="OTHER" <?php selected($customer_data['license_country'], 'OTHER'); ?>><?php _e('Other', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="crcm_address"><?php _e('Address', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_address" name="crcm_customer[address]" value="<?php echo esc_attr($customer_data['address']); ?>" style="width: 100%;" />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_city"><?php _e('City', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_city" name="crcm_customer[city]" value="<?php echo esc_attr($customer_data['city']); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_postal_code"><?php _e('Postal Code', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_postal_code" name="crcm_customer[postal_code]" value="<?php echo esc_attr($customer_data['postal_code']); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_country"><?php _e('Country', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_country" name="crcm_customer[country]" value="<?php echo esc_attr($customer_data['country']); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_emergency_contact"><?php _e('Emergency Contact', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="crcm_emergency_contact" name="crcm_customer[emergency_contact]" value="<?php echo esc_attr($customer_data['emergency_contact']); ?>" />
                </td>
            </tr>
            <tr>
                <th><label for="crcm_emergency_phone"><?php _e('Emergency Phone', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="tel" id="crcm_emergency_phone" name="crcm_customer[emergency_phone]" value="<?php echo esc_attr($customer_data['emergency_phone']); ?>" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render payment details meta box
     */
    public function render_payment_details_meta_box($post) {
        $payment_data = get_post_meta($post->ID, '_crcm_payment_data', true);
        $defaults = array(
            'rental_cost' => 0,
            'insurance_cost' => 0,
            'extras_cost' => 0,
            'total_cost' => 0,
            'deposit_amount' => 0,
            'paid_amount' => 0,
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'stripe_payment_intent' => '',
            'refund_amount' => 0,
            'refund_reason' => '',
        );

        $payment_data = wp_parse_args($payment_data, $defaults);
        $currency_symbol = crcm()->get_setting('currency_symbol', '€');
        ?>
        <table class="form-table" style="margin: 0;">
            <tr>
                <th><?php _e('Rental Cost', 'custom-rental-manager'); ?></th>
                <td>
                    <input type="number" name="crcm_payment[rental_cost]" value="<?php echo esc_attr($payment_data['rental_cost']); ?>" step="0.01" style="width: 100%;" />
                    <?php echo $currency_symbol; ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Insurance Cost', 'custom-rental-manager'); ?></th>
                <td>
                    <input type="number" name="crcm_payment[insurance_cost]" value="<?php echo esc_attr($payment_data['insurance_cost']); ?>" step="0.01" style="width: 100%;" />
                    <?php echo $currency_symbol; ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Extras Cost', 'custom-rental-manager'); ?></th>
                <td>
                    <input type="number" name="crcm_payment[extras_cost]" value="<?php echo esc_attr($payment_data['extras_cost']); ?>" step="0.01" style="width: 100%;" />
                    <?php echo $currency_symbol; ?>
                </td>
            </tr>
            <tr>
                <th><strong><?php _e('Total Cost', 'custom-rental-manager'); ?></strong></th>
                <td>
                    <strong>
                        <input type="number" name="crcm_payment[total_cost]" value="<?php echo esc_attr($payment_data['total_cost']); ?>" step="0.01" style="width: 100%;" />
                        <?php echo $currency_symbol; ?>
                    </strong>
                </td>
            </tr>
            <tr>
                <th><?php _e('Paid Amount', 'custom-rental-manager'); ?></th>
                <td>
                    <input type="number" name="crcm_payment[paid_amount]" value="<?php echo esc_attr($payment_data['paid_amount']); ?>" step="0.01" style="width: 100%;" />
                    <?php echo $currency_symbol; ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Payment Method', 'custom-rental-manager'); ?></th>
                <td>
                    <select name="crcm_payment[payment_method]" style="width: 100%;">
                        <option value="stripe" <?php selected($payment_data['payment_method'], 'stripe'); ?>><?php _e('Stripe', 'custom-rental-manager'); ?></option>
                        <option value="cash" <?php selected($payment_data['payment_method'], 'cash'); ?>><?php _e('Cash', 'custom-rental-manager'); ?></option>
                        <option value="bank_transfer" <?php selected($payment_data['payment_method'], 'bank_transfer'); ?>><?php _e('Bank Transfer', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php _e('Payment Status', 'custom-rental-manager'); ?></th>
                <td>
                    <select name="crcm_payment[payment_status]" style="width: 100%;">
                        <option value="pending" <?php selected($payment_data['payment_status'], 'pending'); ?>><?php _e('Pending', 'custom-rental-manager'); ?></option>
                        <option value="completed" <?php selected($payment_data['payment_status'], 'completed'); ?>><?php _e('Completed', 'custom-rental-manager'); ?></option>
                        <option value="failed" <?php selected($payment_data['payment_status'], 'failed'); ?>><?php _e('Failed', 'custom-rental-manager'); ?></option>
                        <option value="refunded" <?php selected($payment_data['payment_status'], 'refunded'); ?>><?php _e('Refunded', 'custom-rental-manager'); ?></option>
                        <option value="partial_refund" <?php selected($payment_data['payment_status'], 'partial_refund'); ?>><?php _e('Partial Refund', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            <?php if (!empty($payment_data['stripe_payment_intent'])): ?>
            <tr>
                <th><?php _e('Stripe Payment ID', 'custom-rental-manager'); ?></th>
                <td>
                    <code style="font-size: 11px;"><?php echo esc_html($payment_data['stripe_payment_intent']); ?></code>
                </td>
            </tr>
            <?php endif; ?>
            <?php 
            $balance = $payment_data['total_cost'] - $payment_data['paid_amount'];
            if ($balance != 0): 
            ?>
            <tr>
                <th><strong><?php _e('Balance Due', 'custom-rental-manager'); ?></strong></th>
                <td>
                    <strong style="color: <?php echo $balance > 0 ? '#d63384' : '#198754'; ?>;">
                        <?php echo $currency_symbol . number_format(abs($balance), 2); ?>
                        <?php echo $balance > 0 ? __('Due', 'custom-rental-manager') : __('Overpaid', 'custom-rental-manager'); ?>
                    </strong>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    /**
     * Render booking actions meta box
     */
    public function render_booking_actions_meta_box($post) {
        $booking_status = get_post_meta($post->ID, '_crcm_booking_status', true);
        $booking_number = get_post_meta($post->ID, '_crcm_booking_number', true);

        if (empty($booking_status)) {
            $booking_status = self::STATUS_PENDING;
        }
        ?>
        <div class="crcm-booking-actions">
            <?php if (!empty($booking_number)): ?>
            <p><strong><?php _e('Booking Number:', 'custom-rental-manager'); ?></strong><br>
            <code style="font-size: 14px; color: #0073aa;"><?php echo esc_html($booking_number); ?></code></p>
            <?php endif; ?>

            <p><strong><?php _e('Current Status:', 'custom-rental-manager'); ?></strong><br>
            <span class="crcm-status-badge crcm-status-<?php echo esc_attr($booking_status); ?>">
                <?php echo esc_html($this->get_status_label($booking_status)); ?>
            </span></p>

            <p><strong><?php _e('Change Status:', 'custom-rental-manager'); ?></strong></p>
            <select id="crcm_booking_status" name="crcm_booking_status" style="width: 100%;">
                <option value="<?php echo self::STATUS_PENDING; ?>" <?php selected($booking_status, self::STATUS_PENDING); ?>><?php _e('Pending', 'custom-rental-manager'); ?></option>
                <option value="<?php echo self::STATUS_CONFIRMED; ?>" <?php selected($booking_status, self::STATUS_CONFIRMED); ?>><?php _e('Confirmed', 'custom-rental-manager'); ?></option>
                <option value="<?php echo self::STATUS_ACTIVE; ?>" <?php selected($booking_status, self::STATUS_ACTIVE); ?>><?php _e('Active (Picked Up)', 'custom-rental-manager'); ?></option>
                <option value="<?php echo self::STATUS_COMPLETED; ?>" <?php selected($booking_status, self::STATUS_COMPLETED); ?>><?php _e('Completed', 'custom-rental-manager'); ?></option>
                <option value="<?php echo self::STATUS_CANCELLED; ?>" <?php selected($booking_status, self::STATUS_CANCELLED); ?>><?php _e('Cancelled', 'custom-rental-manager'); ?></option>
                <option value="<?php echo self::STATUS_REFUNDED; ?>" <?php selected($booking_status, self::STATUS_REFUNDED); ?>><?php _e('Refunded', 'custom-rental-manager'); ?></option>
            </select>

            <div style="margin-top: 15px;">
                <button type="button" id="crcm-send-email" class="button button-secondary" style="width: 100%; margin-bottom: 5px;">
                    <?php _e('Send Email to Customer', 'custom-rental-manager'); ?>
                </button>

                <?php if ($booking_status === self::STATUS_CONFIRMED): ?>
                <button type="button" id="crcm-generate-contract" class="button button-secondary" style="width: 100%; margin-bottom: 5px;">
                    <?php _e('Generate Contract', 'custom-rental-manager'); ?>
                </button>
                <?php endif; ?>

                <?php if (in_array($booking_status, array(self::STATUS_CONFIRMED, self::STATUS_ACTIVE))): ?>
                <button type="button" id="crcm-process-refund" class="button button-secondary" style="width: 100%;">
                    <?php _e('Process Refund', 'custom-rental-manager'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .crcm-status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .crcm-status-pending { background: #fef3cd; color: #856404; }
        .crcm-status-confirmed { background: #d1ecf1; color: #0c5460; }
        .crcm-status-active { background: #d4edda; color: #155724; }
        .crcm-status-completed { background: #e2e3e5; color: #383d41; }
        .crcm-status-cancelled { background: #f8d7da; color: #721c24; }
        .crcm-status-refunded { background: #fce4ec; color: #ad1457; }
        </style>
        <?php
    }

    /**
     * Save meta data
     */
    public function save_meta_data($post_id) {
        // Verify nonce
        if (!isset($_POST['crcm_booking_meta_nonce']) || !wp_verify_nonce($_POST['crcm_booking_meta_nonce'], 'crcm_booking_meta')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check post type
        if (get_post_type($post_id) !== 'crcm_booking') {
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save booking data
        if (isset($_POST['crcm_booking'])) {
            $booking_data = array();
            $booking_data['vehicle_id'] = intval($_POST['crcm_booking']['vehicle_id']);
            $booking_data['pickup_date'] = sanitize_text_field($_POST['crcm_booking']['pickup_date']);
            $booking_data['return_date'] = sanitize_text_field($_POST['crcm_booking']['return_date']);
            $booking_data['pickup_time'] = sanitize_text_field($_POST['crcm_booking']['pickup_time']);
            $booking_data['return_time'] = sanitize_text_field($_POST['crcm_booking']['return_time']);
            $booking_data['pickup_location'] = intval($_POST['crcm_booking']['pickup_location']);
            $booking_data['return_location'] = intval($_POST['crcm_booking']['return_location']);
            $booking_data['home_delivery'] = isset($_POST['crcm_booking']['home_delivery']);
            $booking_data['delivery_address'] = sanitize_textarea_field($_POST['crcm_booking']['delivery_address']);
            $booking_data['extras'] = array_map('intval', $_POST['crcm_booking']['extras'] ?? array());
            $booking_data['insurance_type'] = sanitize_text_field($_POST['crcm_booking']['insurance_type']);
            $booking_data['notes'] = sanitize_textarea_field($_POST['crcm_booking']['notes']);

            update_post_meta($post_id, '_crcm_booking_data', $booking_data);

            // Save individual meta for easier querying
            update_post_meta($post_id, '_crcm_vehicle_id', $booking_data['vehicle_id']);
            update_post_meta($post_id, '_crcm_pickup_date', $booking_data['pickup_date']);
            update_post_meta($post_id, '_crcm_return_date', $booking_data['return_date']);
        }

        // Save customer data
        if (isset($_POST['crcm_customer'])) {
            $customer_data = array();
            $customer_data['first_name'] = sanitize_text_field($_POST['crcm_customer']['first_name']);
            $customer_data['last_name'] = sanitize_text_field($_POST['crcm_customer']['last_name']);
            $customer_data['email'] = sanitize_email($_POST['crcm_customer']['email']);
            $customer_data['phone'] = sanitize_text_field($_POST['crcm_customer']['phone']);
            $customer_data['date_of_birth'] = sanitize_text_field($_POST['crcm_customer']['date_of_birth']);
            $customer_data['license_number'] = sanitize_text_field($_POST['crcm_customer']['license_number']);
            $customer_data['license_country'] = sanitize_text_field($_POST['crcm_customer']['license_country']);
            $customer_data['address'] = sanitize_text_field($_POST['crcm_customer']['address']);
            $customer_data['city'] = sanitize_text_field($_POST['crcm_customer']['city']);
            $customer_data['postal_code'] = sanitize_text_field($_POST['crcm_customer']['postal_code']);
            $customer_data['country'] = sanitize_text_field($_POST['crcm_customer']['country']);
            $customer_data['emergency_contact'] = sanitize_text_field($_POST['crcm_customer']['emergency_contact']);
            $customer_data['emergency_phone'] = sanitize_text_field($_POST['crcm_customer']['emergency_phone']);

            update_post_meta($post_id, '_crcm_customer_data', $customer_data);
        }

        // Save payment data
        if (isset($_POST['crcm_payment'])) {
            $payment_data = array();
            $payment_data['rental_cost'] = floatval($_POST['crcm_payment']['rental_cost']);
            $payment_data['insurance_cost'] = floatval($_POST['crcm_payment']['insurance_cost']);
            $payment_data['extras_cost'] = floatval($_POST['crcm_payment']['extras_cost']);
            $payment_data['total_cost'] = floatval($_POST['crcm_payment']['total_cost']);
            $payment_data['paid_amount'] = floatval($_POST['crcm_payment']['paid_amount']);
            $payment_data['payment_method'] = sanitize_text_field($_POST['crcm_payment']['payment_method']);
            $payment_data['payment_status'] = sanitize_text_field($_POST['crcm_payment']['payment_status']);

            // Preserve existing stripe data
            $existing_payment_data = get_post_meta($post_id, '_crcm_payment_data', true);
            if (is_array($existing_payment_data)) {
                $payment_data['stripe_payment_intent'] = $existing_payment_data['stripe_payment_intent'] ?? '';
                $payment_data['refund_amount'] = $existing_payment_data['refund_amount'] ?? 0;
                $payment_data['refund_reason'] = $existing_payment_data['refund_reason'] ?? '';
            }

            update_post_meta($post_id, '_crcm_payment_data', $payment_data);
        }

        // Save booking status
        if (isset($_POST['crcm_booking_status'])) {
            $old_status = get_post_meta($post_id, '_crcm_booking_status', true);
            $new_status = sanitize_text_field($_POST['crcm_booking_status']);

            update_post_meta($post_id, '_crcm_booking_status', $new_status);

            // Trigger status change actions
            if ($old_status !== $new_status) {
                do_action('crcm_booking_status_changed', $post_id, $new_status, $old_status);
            }
        }

        // Generate booking number if new booking
        $booking_number = get_post_meta($post_id, '_crcm_booking_number', true);
        if (empty($booking_number)) {
            $booking_number = $this->generate_booking_number();
            update_post_meta($post_id, '_crcm_booking_number', $booking_number);
        }
    }

    /**
     * Add custom columns to booking list
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['crcm_booking_number'] = __('Booking #', 'custom-rental-manager');
        $new_columns['crcm_customer'] = __('Customer', 'custom-rental-manager');
        $new_columns['crcm_vehicle'] = __('Vehicle', 'custom-rental-manager');
        $new_columns['crcm_dates'] = __('Rental Dates', 'custom-rental-manager');
        $new_columns['crcm_status'] = __('Status', 'custom-rental-manager');
        $new_columns['crcm_total'] = __('Total', 'custom-rental-manager');
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    /**
     * Display custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'crcm_booking_number':
                $booking_number = get_post_meta($post_id, '_crcm_booking_number', true);
                if ($booking_number) {
                    echo '<strong>' . esc_html($booking_number) . '</strong>';
                } else {
                    echo '-';
                }
                break;

            case 'crcm_customer':
                $customer_data = get_post_meta($post_id, '_crcm_customer_data', true);
                if ($customer_data) {
                    echo esc_html($customer_data['first_name'] . ' ' . $customer_data['last_name']);
                    if (!empty($customer_data['email'])) {
                        echo '<br><small>' . esc_html($customer_data['email']) . '</small>';
                    }
                }
                break;

            case 'crcm_vehicle':
                $booking_data = get_post_meta($post_id, '_crcm_booking_data', true);
                if ($booking_data && !empty($booking_data['vehicle_id'])) {
                    $vehicle = get_post($booking_data['vehicle_id']);
                    if ($vehicle) {
                        echo '<a href="' . get_edit_post_link($vehicle->ID) . '">' . esc_html($vehicle->post_title) . '</a>';
                    }
                }
                break;

            case 'crcm_dates':
                $booking_data = get_post_meta($post_id, '_crcm_booking_data', true);
                if ($booking_data) {
                    $pickup = date_i18n('M j, Y', strtotime($booking_data['pickup_date']));
                    $return = date_i18n('M j, Y', strtotime($booking_data['return_date']));
                    $days = ceil((strtotime($booking_data['return_date']) - strtotime($booking_data['pickup_date'])) / DAY_IN_SECONDS);

                    echo esc_html($pickup) . '<br><small>to ' . esc_html($return) . ' (' . $days . ' days)</small>';
                }
                break;

            case 'crcm_status':
                $status = get_post_meta($post_id, '_crcm_booking_status', true);
                if (empty($status)) {
                    $status = self::STATUS_PENDING;
                }
                echo '<span class="crcm-status-badge crcm-status-' . esc_attr($status) . '">' . esc_html($this->get_status_label($status)) . '</span>';
                break;

            case 'crcm_total':
                $payment_data = get_post_meta($post_id, '_crcm_payment_data', true);
                if ($payment_data && !empty($payment_data['total_cost'])) {
                    echo crcm()->get_setting('currency_symbol', '€') . number_format($payment_data['total_cost'], 2);

                    $paid = $payment_data['paid_amount'] ?? 0;
                    if ($paid < $payment_data['total_cost']) {
                        $balance = $payment_data['total_cost'] - $paid;
                        echo '<br><small style="color: #d63384;">Due: ' . crcm()->get_setting('currency_symbol', '€') . number_format($balance, 2) . '</small>';
                    }
                } else {
                    echo '-';
                }
                break;
        }
    }

    /**
     * Add row actions
     */
    public function add_row_actions($actions, $post) {
        if ($post->post_type === 'crcm_booking') {
            $booking_number = get_post_meta($post->ID, '_crcm_booking_number', true);

            if ($booking_number) {
                $actions['view_booking'] = '<a href="#" onclick="alert('View booking details: ' . esc_js($booking_number) . '')">' . __('View Details', 'custom-rental-manager') . '</a>';
            }

            $actions['send_email'] = '<a href="#" onclick="alert('Send email to customer')">' . __('Email Customer', 'custom-rental-manager') . '</a>';
        }

        return $actions;
    }

    /**
     * AJAX handler for updating booking status
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

        // Trigger status change actions
        do_action('crcm_booking_status_changed', $booking_id, $new_status, $old_status);

        wp_send_json_success(array(
            'message' => __('Booking status updated successfully', 'custom-rental-manager'),
            'new_status' => $this->get_status_label($new_status),
        ));
    }

    /**
     * Create a new booking
     */
    public function create_booking($booking_data) {
        // Validate required fields
        if (empty($booking_data['vehicle_id']) || empty($booking_data['pickup_date']) || empty($booking_data['return_date'])) {
            return new WP_Error('missing_data', __('Missing required booking data', 'custom-rental-manager'));
        }

        // Check vehicle availability
        $vehicle_manager = new CRCM_Vehicle_Manager();
        $available = $vehicle_manager->check_availability(
            $booking_data['vehicle_id'],
            $booking_data['pickup_date'],
            $booking_data['return_date']
        );

        if ($available <= 0) {
            return new WP_Error('not_available', __('Vehicle is not available for the selected dates', 'custom-rental-manager'));
        }

        // Calculate pricing
        $pricing = $this->calculate_booking_pricing($booking_data);

        // Create booking post
        $booking_post = array(
            'post_type' => 'crcm_booking',
            'post_status' => 'publish',
            'post_title' => sprintf(__('Booking - %s %s', 'custom-rental-manager'), 
                $booking_data['customer_data']['first_name'], 
                $booking_data['customer_data']['last_name']
            ),
        );

        $booking_id = wp_insert_post($booking_post);

        if (is_wp_error($booking_id)) {
            return $booking_id;
        }

        // Save booking data
        update_post_meta($booking_id, '_crcm_booking_data', $booking_data);
        update_post_meta($booking_id, '_crcm_customer_data', $booking_data['customer_data']);
        update_post_meta($booking_id, '_crcm_payment_data', $pricing);
        update_post_meta($booking_id, '_crcm_booking_status', self::STATUS_PENDING);
        update_post_meta($booking_id, '_crcm_vehicle_id', $booking_data['vehicle_id']);
        update_post_meta($booking_id, '_crcm_pickup_date', $booking_data['pickup_date']);
        update_post_meta($booking_id, '_crcm_return_date', $booking_data['return_date']);

        // Generate booking number
        $booking_number = $this->generate_booking_number();
        update_post_meta($booking_id, '_crcm_booking_number', $booking_number);

        return array(
            'booking_id' => $booking_id,
            'booking_number' => $booking_number,
            'pricing' => $pricing,
        );
    }

    /**
     * Calculate booking pricing
     */
    public function calculate_booking_pricing($booking_data) {
        $vehicle_manager = new CRCM_Vehicle_Manager();

        $pickup_date = $booking_data['pickup_date'];
        $return_date = $booking_data['return_date'];
        $vehicle_id = $booking_data['vehicle_id'];

        $rental_days = ceil((strtotime($return_date) - strtotime($pickup_date)) / DAY_IN_SECONDS);
        $daily_rate = $vehicle_manager->calculate_daily_rate($vehicle_id, $pickup_date, $return_date);

        $rental_cost = $daily_rate * $rental_days;

        // Calculate insurance cost
        $vehicle = $vehicle_manager->get_vehicle($vehicle_id);
        $insurance_cost = 0;
        $insurance_type = $booking_data['insurance_type'] ?? 'basic';

        if ($insurance_type === 'premium' && $vehicle) {
            $insurance_rate = $vehicle['pricing_data']['insurance_premium'] ?? 0;
            $insurance_cost = $insurance_rate * $rental_days;
        }

        // Calculate extras cost
        $extras_cost = 0;
        $extras = $booking_data['extras'] ?? array();

        foreach ($extras as $extra_id) {
            $extra_data = get_post_meta($extra_id, '_crcm_extra_data', true);
            $extra_price = $extra_data['price'] ?? 0;
            $extras_cost += $extra_price * $rental_days;
        }

        // Check for late return extra day
        if (crcm()->get_setting('late_return_extra_day', true)) {
            $return_time = $booking_data['return_time'] ?? '18:00';
            $late_time = crcm()->get_setting('late_return_time', '10:00');

            if ($return_time > $late_time) {
                $rental_cost += $daily_rate; // Add one extra day
            }
        }

        $total_cost = $rental_cost + $insurance_cost + $extras_cost;

        return array(
            'rental_cost' => $rental_cost,
            'insurance_cost' => $insurance_cost,
            'extras_cost' => $extras_cost,
            'total_cost' => $total_cost,
            'deposit_amount' => $total_cost * 0.3, // 30% deposit
            'paid_amount' => 0,
            'payment_method' => 'stripe',
            'payment_status' => 'pending',
            'stripe_payment_intent' => '',
            'refund_amount' => 0,
            'refund_reason' => '',
        );
    }

    /**
     * Generate unique booking number
     */
    public function generate_booking_number() {
        $prefix = 'ISCHIA';
        $year = date('Y');
        $month = date('m');

        // Get last booking number for this month
        global $wpdb;
        $last_number = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING(meta_value, -4) AS UNSIGNED)) 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_crcm_booking_number' 
             AND meta_value LIKE %s",
            $prefix . $year . $month . '%'
        ));

        $next_number = ($last_number) ? $last_number + 1 : 1;

        return $prefix . $year . $month . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get status label
     */
    public function get_status_label($status) {
        $labels = array(
            self::STATUS_PENDING => __('Pending', 'custom-rental-manager'),
            self::STATUS_CONFIRMED => __('Confirmed', 'custom-rental-manager'),
            self::STATUS_ACTIVE => __('Active', 'custom-rental-manager'),
            self::STATUS_COMPLETED => __('Completed', 'custom-rental-manager'),
            self::STATUS_CANCELLED => __('Cancelled', 'custom-rental-manager'),
            self::STATUS_REFUNDED => __('Refunded', 'custom-rental-manager'),
        );

        return $labels[$status] ?? $status;
    }

    /**
     * Get booking by ID
     */
    public function get_booking($booking_id) {
        $booking = get_post($booking_id);

        if (!$booking || $booking->post_type !== 'crcm_booking') {
            return null;
        }

        return array(
            'id' => $booking->ID,
            'booking_number' => get_post_meta($booking_id, '_crcm_booking_number', true),
            'status' => get_post_meta($booking_id, '_crcm_booking_status', true),
            'booking_data' => get_post_meta($booking_id, '_crcm_booking_data', true),
            'customer_data' => get_post_meta($booking_id, '_crcm_customer_data', true),
            'payment_data' => get_post_meta($booking_id, '_crcm_payment_data', true),
            'created_date' => $booking->post_date,
        );
    }
}
