<?php
/**
 * Booking Manager Class - UPDATED WITH NEW LOCATIONS
 * 
 * Updated with the two specific locations for Costabilerent.
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
     * Predefined locations for Costabilerent
     */
    private $locations = array(
        'ischia_porto' => array(
            'name' => 'Ischia Porto',
            'address' => 'Via Iasolino 94, Ischia',
            'short_name' => 'Ischia Porto'
        ),
        'forio' => array(
            'name' => 'Forio',
            'address' => 'Via Filippo di Lustro 19, Forio',
            'short_name' => 'Forio'
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_booking_meta'));
        add_action('wp_ajax_crcm_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_nopriv_crcm_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_crcm_cancel_booking', array($this, 'ajax_cancel_booking'));
        add_action('wp_ajax_crcm_get_booking_details', array($this, 'ajax_get_booking_details'));
        add_filter('manage_crcm_booking_posts_columns', array($this, 'booking_columns'));
        add_action('manage_crcm_booking_posts_custom_column', array($this, 'booking_column_content'), 10, 2);
        add_action('post_submitbox_misc_actions', array($this, 'booking_status_metabox'));
        
        // Status change hooks
        add_action('transition_post_status', array($this, 'on_booking_status_change'), 10, 3);
        
        // Customer auto-registration hooks
        add_action('crcm_booking_created', array($this, 'auto_register_customer'), 10, 2);
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
            'crcm_customer_selection',
            __('Customer Selection', 'custom-rental-manager'),
            array($this, 'customer_selection_meta_box'),
            'crcm_booking',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crcm_customer_details',
            __('Customer Information', 'custom-rental-manager'),
            array($this, 'customer_details_meta_box'),
            'crcm_booking',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crcm_payment_details',
            __('Payment Information', 'custom-rental-manager'),
            array($this, 'payment_details_meta_box'),
            'crcm_booking',
            'side',
            'high'
        );
        
        add_meta_box(
            'crcm_booking_notes',
            __('Booking Notes', 'custom-rental-manager'),
            array($this, 'booking_notes_meta_box'),
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
                'special_requests' => '',
            );
        }
        
        // Get available vehicles
        $vehicles = get_posts(array(
            'post_type' => 'crcm_vehicle',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        ?>
        
        <table class="form-table">
            <tr>
                <th><label for="booking_number"><?php _e('Booking Number', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="booking_number" value="<?php echo esc_attr($booking_number); ?>" readonly />
                    <p class="description"><?php _e('Automatically generated unique booking number', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="vehicle_id"><?php _e('Vehicle', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <select id="vehicle_id" name="booking_data[vehicle_id]" required>
                        <option value=""><?php _e('Select Vehicle', 'custom-rental-manager'); ?></option>
                        <?php foreach ($vehicles as $vehicle): 
                            $vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
                            $vehicle_type = isset($vehicle_data['vehicle_type']) ? $vehicle_data['vehicle_type'] : 'auto';
                            $type_label = $vehicle_type === 'auto' ? 'Auto' : 'Scooter';
                        ?>
                            <option value="<?php echo $vehicle->ID; ?>" <?php selected($booking_data['vehicle_id'], $vehicle->ID); ?>>
                                [<?php echo $type_label; ?>] <?php echo esc_html($vehicle->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="pickup_date"><?php _e('Pickup Date', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <input type="date" id="pickup_date" name="booking_data[pickup_date]" 
                           value="<?php echo esc_attr($booking_data['pickup_date']); ?>" required />
                </td>
            </tr>
            
            <tr>
                <th><label for="return_date"><?php _e('Return Date', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <input type="date" id="return_date" name="booking_data[return_date]" 
                           value="<?php echo esc_attr($booking_data['return_date']); ?>" required />
                </td>
            </tr>
            
            <tr>
                <th><label for="pickup_time"><?php _e('Pickup Time', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="pickup_time" name="booking_data[pickup_time]">
                        <?php for ($h = 8; $h <= 19; $h++): 
                            $time = sprintf('%02d:00', $h);
                        ?>
                            <option value="<?php echo $time; ?>" <?php selected($booking_data['pickup_time'], $time); ?>>
                                <?php echo $time; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="return_time"><?php _e('Return Time', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="return_time" name="booking_data[return_time]">
                        <?php for ($h = 8; $h <= 19; $h++): 
                            $time = sprintf('%02d:00', $h);
                        ?>
                            <option value="<?php echo $time; ?>" <?php selected($booking_data['return_time'], $time); ?>>
                                <?php echo $time; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="pickup_location"><?php _e('Pickup Location', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="pickup_location" name="booking_data[pickup_location]">
                        <option value=""><?php _e('Select Location', 'custom-rental-manager'); ?></option>
                        <?php foreach ($this->locations as $key => $location): ?>
                            <option value="<?php echo $key; ?>" <?php selected($booking_data['pickup_location'], $key); ?>>
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php foreach ($this->locations as $key => $location): ?>
                            <small><strong><?php echo esc_html($location['name']); ?>:</strong> <?php echo esc_html($location['address']); ?></small><br>
                        <?php endforeach; ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th><label for="return_location"><?php _e('Return Location', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="return_location" name="booking_data[return_location]">
                        <option value=""><?php _e('Same as pickup', 'custom-rental-manager'); ?></option>
                        <?php foreach ($this->locations as $key => $location): ?>
                            <option value="<?php echo $key; ?>" <?php selected($booking_data['return_location'], $key); ?>>
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="home_delivery"><?php _e('Home Delivery', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="checkbox" id="home_delivery" name="booking_data[home_delivery]" value="1" 
                           <?php checked($booking_data['home_delivery'], 1); ?> />
                    <label for="home_delivery"><?php _e('Enable home delivery service', 'custom-rental-manager'); ?></label>
                </td>
            </tr>
            
            <tr class="delivery-address-row" style="<?php echo empty($booking_data['home_delivery']) ? 'display: none;' : ''; ?>">
                <th><label for="delivery_address"><?php _e('Delivery Address', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea id="delivery_address" name="booking_data[delivery_address]" rows="3" cols="50"><?php echo esc_textarea($booking_data['delivery_address']); ?></textarea>
                </td>
            </tr>
            
            <tr>
                <th><label for="special_requests"><?php _e('Special Requests', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea id="special_requests" name="booking_data[special_requests]" rows="3" cols="50"><?php echo esc_textarea($booking_data['special_requests']); ?></textarea>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            $('#home_delivery').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.delivery-address-row').show();
                } else {
                    $('.delivery-address-row').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Customer selection meta box - Only show rental customers
     */
    public function customer_selection_meta_box($post) {
        $selected_customer_id = get_post_meta($post->ID, '_crcm_customer_user_id', true);
        
        // Get all users with crcm_customer role
        $customers = get_users(array(
            'role' => 'crcm_customer',
            'orderby' => 'display_name',
            'order' => 'ASC',
        ));
        ?>
        
        <table class="form-table">
            <tr>
                <th><label for="customer_user_id"><?php _e('Select Customer', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="customer_user_id" name="customer_user_id" style="width: 100%; max-width: 400px;">
                        <option value=""><?php _e('Select existing customer or create new below', 'custom-rental-manager'); ?></option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer->ID; ?>" <?php selected($selected_customer_id, $customer->ID); ?>>
                                <?php echo esc_html($customer->display_name . ' (' . $customer->user_email . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Select an existing customer or leave empty to create a new customer account from the information below.', 'custom-rental-manager'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <style>
        #customer_user_id {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        </style>
        <?php
    }
    
    /**
     * Customer details meta box
     */
    public function customer_details_meta_box($post) {
        $customer_data = get_post_meta($post->ID, '_crcm_customer_data', true);
        
        // Default values
        if (empty($customer_data)) {
            $customer_data = array(
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'date_of_birth' => '',
                'address' => '',
                'city' => '',
                'postal_code' => '',
                'country' => 'IT',
                'license_number' => '',
                'license_expiry' => '',
                'emergency_contact' => '',
                'emergency_phone' => '',
            );
        }
        ?>
        
        <table class="form-table">
            <tr>
                <th><label for="first_name"><?php _e('First Name', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <input type="text" id="first_name" name="customer_data[first_name]" 
                           value="<?php echo esc_attr($customer_data['first_name']); ?>" required />
                </td>
            </tr>
            
            <tr>
                <th><label for="last_name"><?php _e('Last Name', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <input type="text" id="last_name" name="customer_data[last_name]" 
                           value="<?php echo esc_attr($customer_data['last_name']); ?>" required />
                </td>
            </tr>
            
            <tr>
                <th><label for="email"><?php _e('Email', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <input type="email" id="email" name="customer_data[email]" 
                           value="<?php echo esc_attr($customer_data['email']); ?>" required />
                    <p class="description"><?php _e('Will be used for customer account creation if no existing customer is selected', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="phone"><?php _e('Phone', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <input type="tel" id="phone" name="customer_data[phone]" 
                           value="<?php echo esc_attr($customer_data['phone']); ?>" required />
                </td>
            </tr>
            
            <tr>
                <th><label for="date_of_birth"><?php _e('Date of Birth', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <input type="date" id="date_of_birth" name="customer_data[date_of_birth]" 
                           value="<?php echo esc_attr($customer_data['date_of_birth']); ?>" required />
                </td>
            </tr>
            
            <tr>
                <th><label for="address"><?php _e('Address', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea id="address" name="customer_data[address]" rows="3" cols="50"><?php echo esc_textarea($customer_data['address']); ?></textarea>
                </td>
            </tr>
            
            <tr>
                <th><label for="city"><?php _e('City', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="city" name="customer_data[city]" 
                           value="<?php echo esc_attr($customer_data['city']); ?>" />
                </td>
            </tr>
            
            <tr>
                <th><label for="license_number"><?php _e('License Number', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <input type="text" id="license_number" name="customer_data[license_number]" 
                           value="<?php echo esc_attr($customer_data['license_number']); ?>" required />
                </td>
            </tr>
            
            <tr>
                <th><label for="license_expiry"><?php _e('License Expiry', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="date" id="license_expiry" name="customer_data[license_expiry]" 
                           value="<?php echo esc_attr($customer_data['license_expiry']); ?>" />
                </td>
            </tr>
            
            <tr>
                <th><label for="emergency_contact"><?php _e('Emergency Contact', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" id="emergency_contact" name="customer_data[emergency_contact]" 
                           value="<?php echo esc_attr($customer_data['emergency_contact']); ?>" />
                </td>
            </tr>
            
            <tr>
                <th><label for="emergency_phone"><?php _e('Emergency Phone', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="tel" id="emergency_phone" name="customer_data[emergency_phone]" 
                           value="<?php echo esc_attr($customer_data['emergency_phone']); ?>" />
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Payment details meta box
     */
    public function payment_details_meta_box($post) {
        $payment_data = get_post_meta($post->ID, '_crcm_payment_data', true);
        
        // Default values
        if (empty($payment_data)) {
            $payment_data = array(
                'subtotal' => 0,
                'extras_total' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'deposit_amount' => 0,
                'security_deposit' => 0,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'deposit_paid' => false,
                'balance_due' => 0,
                'currency' => 'EUR',
            );
        }
        ?>
        
        <table class="form-table">
            <tr>
                <th><label for="subtotal"><?php _e('Subtotal', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" step="0.01" id="subtotal" name="payment_data[subtotal]" 
                           value="<?php echo esc_attr($payment_data['subtotal']); ?>" />
                </td>
            </tr>
            
            <tr>
                <th><label for="extras_total"><?php _e('Extras Total', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" step="0.01" id="extras_total" name="payment_data[extras_total]" 
                           value="<?php echo esc_attr($payment_data['extras_total']); ?>" />
                </td>
            </tr>
            
            <tr>
                <th><label for="tax_amount"><?php _e('Tax Amount', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" step="0.01" id="tax_amount" name="payment_data[tax_amount]" 
                           value="<?php echo esc_attr($payment_data['tax_amount']); ?>" />
                </td>
            </tr>
            
            <tr>
                <th><label for="total_amount"><?php _e('Total Amount', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" step="0.01" id="total_amount" name="payment_data[total_amount]" 
                           value="<?php echo esc_attr($payment_data['total_amount']); ?>" />
                </td>
            </tr>
            
            <tr>
                <th><label for="security_deposit"><?php _e('Security Deposit', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" step="0.01" id="security_deposit" name="payment_data[security_deposit]" 
                           value="<?php echo esc_attr($payment_data['security_deposit']); ?>" />
                </td>
            </tr>
            
            <tr>
                <th><label for="payment_method"><?php _e('Payment Method', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="payment_method" name="payment_data[payment_method]">
                        <option value="cash" <?php selected($payment_data['payment_method'], 'cash'); ?>><?php _e('Cash', 'custom-rental-manager'); ?></option>
                        <option value="card" <?php selected($payment_data['payment_method'], 'card'); ?>><?php _e('Credit Card', 'custom-rental-manager'); ?></option>
                        <option value="stripe" <?php selected($payment_data['payment_method'], 'stripe'); ?>><?php _e('Stripe', 'custom-rental-manager'); ?></option>
                        <option value="transfer" <?php selected($payment_data['payment_method'], 'transfer'); ?>><?php _e('Bank Transfer', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="payment_status"><?php _e('Payment Status', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="payment_status" name="payment_data[payment_status]">
                        <option value="pending" <?php selected($payment_data['payment_status'], 'pending'); ?>><?php _e('Pending', 'custom-rental-manager'); ?></option>
                        <option value="deposit_paid" <?php selected($payment_data['payment_status'], 'deposit_paid'); ?>><?php _e('Deposit Paid', 'custom-rental-manager'); ?></option>
                        <option value="fully_paid" <?php selected($payment_data['payment_status'], 'fully_paid'); ?>><?php _e('Fully Paid', 'custom-rental-manager'); ?></option>
                        <option value="refunded" <?php selected($payment_data['payment_status'], 'refunded'); ?>><?php _e('Refunded', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        
        <div class="crcm-pricing-summary">
            <h4><?php _e('Pricing Summary', 'custom-rental-manager'); ?></h4>
            <div class="pricing-info">
                <p><strong><?php _e('Daily Rate:', 'custom-rental-manager'); ?></strong> <span id="daily-rate-display">-</span></p>
                <p><strong><?php _e('Rental Days:', 'custom-rental-manager'); ?></strong> <span id="rental-days-display">-</span></p>
                <p><strong><?php _e('Total:', 'custom-rental-manager'); ?></strong> <span id="total-display">€0.00</span></p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            function updatePricingSummary() {
                const vehicleId = $('#vehicle_id').val();
                const pickupDate = $('#pickup_date').val();
                const returnDate = $('#return_date').val();
                
                if (vehicleId && pickupDate && returnDate) {
                    // Calculate days
                    const pickup = new Date(pickupDate);
                    const returnD = new Date(returnDate);
                    const timeDiff = returnD.getTime() - pickup.getTime();
                    const days = Math.max(1, Math.ceil(timeDiff / (1000 * 3600 * 24)));
                    
                    $('#rental-days-display').text(days);
                    
                    // You could add AJAX call here to get vehicle pricing
                    // For now, just show the days calculation
                }
            }
            
            $('#vehicle_id, #pickup_date, #return_date').on('change', updatePricingSummary);
            updatePricingSummary();
        });
        </script>
        <?php
    }
    
    /**
     * Booking notes meta box
     */
    public function booking_notes_meta_box($post) {
        $notes = get_post_meta($post->ID, '_crcm_booking_notes', true);
        ?>
        <textarea name="booking_notes" rows="5" cols="50" style="width: 100%;"><?php echo esc_textarea($notes); ?></textarea>
        <p class="description"><?php _e('Internal notes visible only to admin', 'custom-rental-manager'); ?></p>
        <?php
    }
    
    /**
     * Add booking status to submit box
     */
    public function booking_status_metabox() {
        global $post;
        
        if ($post->post_type !== 'crcm_booking') {
            return;
        }
        
        $booking_status = get_post_meta($post->ID, '_crcm_booking_status', true);
        if (empty($booking_status)) {
            $booking_status = 'pending';
        }
        
        $statuses = array(
            'pending' => __('Pending', 'custom-rental-manager'),
            'confirmed' => __('Confirmed', 'custom-rental-manager'),
            'active' => __('Active', 'custom-rental-manager'),
            'completed' => __('Completed', 'custom-rental-manager'),
            'cancelled' => __('Cancelled', 'custom-rental-manager'),
        );
        ?>
        
        <div class="misc-pub-section">
            <label for="booking_status"><?php _e('Booking Status:', 'custom-rental-manager'); ?></label>
            <select name="booking_status" id="booking_status">
                <?php foreach ($statuses as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($booking_status, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }
    
    /**
     * Save booking meta data with customer account integration
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
        
        // Generate booking number if not exists
        $booking_number = get_post_meta($post_id, '_crcm_booking_number', true);
        if (empty($booking_number)) {
            $booking_number = $this->generate_booking_number();
            update_post_meta($post_id, '_crcm_booking_number', $booking_number);
        }
        
        // Save customer user ID
        if (isset($_POST['customer_user_id'])) {
            $customer_user_id = intval($_POST['customer_user_id']);
            update_post_meta($post_id, '_crcm_customer_user_id', $customer_user_id);
        }
        
        // Save booking data
        if (isset($_POST['booking_data'])) {
            $booking_data = array();
            foreach ($_POST['booking_data'] as $key => $value) {
                if (is_array($value)) {
                    $booking_data[$key] = array_map('sanitize_text_field', $value);
                } else {
                    $booking_data[$key] = sanitize_text_field($value);
                }
            }
            update_post_meta($post_id, '_crcm_booking_data', $booking_data);
        }
        
        // Save customer data and potentially create account
        if (isset($_POST['customer_data'])) {
            $customer_data = array();
            foreach ($_POST['customer_data'] as $key => $value) {
                $customer_data[$key] = sanitize_text_field($value);
            }
            update_post_meta($post_id, '_crcm_customer_data', $customer_data);
            
            // Create customer account if no existing customer selected
            $selected_customer_id = isset($_POST['customer_user_id']) ? intval($_POST['customer_user_id']) : 0;
            if (empty($selected_customer_id) && !empty($customer_data['email'])) {
                $new_customer_id = $this->create_customer_account($customer_data);
                if ($new_customer_id) {
                    update_post_meta($post_id, '_crcm_customer_user_id', $new_customer_id);
                }
            }
        }
        
        // Save payment data
        if (isset($_POST['payment_data'])) {
            $payment_data = array();
            foreach ($_POST['payment_data'] as $key => $value) {
                if (in_array($key, array('subtotal', 'extras_total', 'tax_amount', 'total_amount', 'deposit_amount', 'security_deposit', 'balance_due'))) {
                    $payment_data[$key] = floatval($value);
                } else {
                    $payment_data[$key] = sanitize_text_field($value);
                }
            }
            update_post_meta($post_id, '_crcm_payment_data', $payment_data);
        }
        
        // Save booking status
        if (isset($_POST['booking_status'])) {
            $old_status = get_post_meta($post_id, '_crcm_booking_status', true);
            $new_status = sanitize_text_field($_POST['booking_status']);
            
            update_post_meta($post_id, '_crcm_booking_status', $new_status);
            
            // Trigger status change hook if status changed
            if ($old_status !== $new_status) {
                do_action('crcm_booking_status_changed', $post_id, $new_status, $old_status);
            }
        }
        
        // Save booking notes
        if (isset($_POST['booking_notes'])) {
            update_post_meta($post_id, '_crcm_booking_notes', sanitize_textarea_field($_POST['booking_notes']));
        }
    }
    
    /**
     * Create customer account with crcm_customer role
     */
    private function create_customer_account($customer_data) {
        // Check if email exists
        if (email_exists($customer_data['email'])) {
            return false;
        }
        
        // Generate username from email
        $username = sanitize_user($customer_data['email']);
        if (username_exists($username)) {
            $username = sanitize_user($customer_data['first_name'] . '.' . $customer_data['last_name']);
            if (username_exists($username)) {
                $username = sanitize_user($customer_data['email'] . '.' . time());
            }
        }
        
        // Generate temporary password
        $password = wp_generate_password(12, false);
        
        // Create user
        $user_id = wp_create_user($username, $password, $customer_data['email']);
        
        if (is_wp_error($user_id)) {
            return false;
        }
        
        // Update user profile
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $customer_data['first_name'],
            'last_name' => $customer_data['last_name'],
            'display_name' => $customer_data['first_name'] . ' ' . $customer_data['last_name'],
        ));
        
        // Set customer role
        $user = new WP_User($user_id);
        $user->set_role('crcm_customer');
        
        // Save additional customer meta
        update_user_meta($user_id, 'phone', $customer_data['phone']);
        update_user_meta($user_id, 'date_of_birth', $customer_data['date_of_birth']);
        update_user_meta($user_id, 'address', $customer_data['address']);
        update_user_meta($user_id, 'city', $customer_data['city']);
        update_user_meta($user_id, 'license_number', $customer_data['license_number']);
        update_user_meta($user_id, 'license_expiry', $customer_data['license_expiry']);
        update_user_meta($user_id, 'emergency_contact', $customer_data['emergency_contact']);
        update_user_meta($user_id, 'emergency_phone', $customer_data['emergency_phone']);
        
        // Send welcome email with password
        wp_send_new_user_notifications($user_id, 'user');
        
        return $user_id;
    }
    
    /**
     * Auto-register customer from frontend booking
     */
    public function auto_register_customer($booking_id, $customer_data) {
        if (empty($customer_data['email'])) {
            return false;
        }
        
        // Check if user already exists
        $existing_user = get_user_by('email', $customer_data['email']);
        if ($existing_user) {
            // Update booking with existing customer
            update_post_meta($booking_id, '_crcm_customer_user_id', $existing_user->ID);
            return $existing_user->ID;
        }
        
        // Create new customer account
        $user_id = $this->create_customer_account($customer_data);
        if ($user_id) {
            update_post_meta($booking_id, '_crcm_customer_user_id', $user_id);
        }
        
        return $user_id;
    }
    
    /**
     * Generate unique booking number
     */
    private function generate_booking_number() {
        $prefix = 'CBR'; // Costabilerent
        $year = date('y');
        $month = date('m');
        
        // Get the last booking number for this month
        global $wpdb;
        $last_number = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} 
             WHERE meta_key = '_crcm_booking_number' 
             AND meta_value LIKE %s 
             ORDER BY meta_value DESC 
             LIMIT 1",
            $prefix . $year . $month . '%'
        ));
        
        if ($last_number) {
            $sequence = intval(substr($last_number, -4)) + 1;
        } else {
            $sequence = 1;
        }
        
        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Custom columns for booking list
     */
    public function booking_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['crcm_booking_number'] = __('Booking #', 'custom-rental-manager');
        $new_columns['crcm_customer'] = __('Customer', 'custom-rental-manager');
        $new_columns['crcm_vehicle'] = __('Vehicle', 'custom-rental-manager');
        $new_columns['crcm_dates'] = __('Rental Period', 'custom-rental-manager');
        $new_columns['crcm_status'] = __('Status', 'custom-rental-manager');
        $new_columns['crcm_total'] = __('Total', 'custom-rental-manager');
        $new_columns['date'] = $columns['date'];
        
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
        $booking_number = get_post_meta($post_id, '_crcm_booking_number', true);
        $customer_user_id = get_post_meta($post_id, '_crcm_customer_user_id', true);
        
        switch ($column) {
            case 'crcm_booking_number':
                echo esc_html($booking_number);
                break;
                
            case 'crcm_customer':
                if ($customer_data && isset($customer_data['first_name'], $customer_data['last_name'])) {
                    echo esc_html($customer_data['first_name'] . ' ' . $customer_data['last_name']);
                    
                    // Show customer role badge
                    if ($customer_user_id) {
                        $user = get_user_by('ID', $customer_user_id);
                        if ($user && in_array('crcm_customer', $user->roles)) {
                            echo '<br><span class="crcm-customer-badge">Customer Account</span>';
                        }
                    }
                    
                    if (isset($customer_data['email'])) {
                        echo '<br><small>' . esc_html($customer_data['email']) . '</small>';
                    }
                }
                break;
                
            case 'crcm_vehicle':
                if ($booking_data && isset($booking_data['vehicle_id'])) {
                    $vehicle = get_post($booking_data['vehicle_id']);
                    if ($vehicle) {
                        echo esc_html($vehicle->post_title);
                        
                        // Show vehicle type
                        $vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
                        if (isset($vehicle_data['vehicle_type'])) {
                            $type_label = $vehicle_data['vehicle_type'] === 'auto' ? 'Auto' : 'Scooter';
                            echo '<br><small>' . $type_label . '</small>';
                        }
                    }
                }
                break;
                
            case 'crcm_dates':
                if ($booking_data && isset($booking_data['pickup_date'], $booking_data['return_date'])) {
                    echo esc_html(date_i18n('M j', strtotime($booking_data['pickup_date'])));
                    echo ' - ';
                    echo esc_html(date_i18n('M j, Y', strtotime($booking_data['return_date'])));
                }
                break;
                
            case 'crcm_status':
                if ($booking_status) {
                    echo crcm_get_status_badge($booking_status);
                }
                break;
                
            case 'crcm_total':
                if ($payment_data && isset($payment_data['total_amount'])) {
                    echo '€' . number_format($payment_data['total_amount'], 2);
                }
                break;
        }
    }
    
    /**
     * AJAX create booking with auto customer registration
     */
    public function ajax_create_booking() {
        check_ajax_referer('crcm_nonce', 'nonce');
        
        // Validate required fields
        $required_fields = array('vehicle_id', 'pickup_date', 'return_date', 'first_name', 'last_name', 'email');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(sprintf(__('Field %s is required.', 'custom-rental-manager'), $field));
            }
        }
        
        // Create booking post
        $booking_id = wp_insert_post(array(
            'post_type' => 'crcm_booking',
            'post_status' => 'publish',
            'post_title' => sprintf(__('Booking - %s', 'custom-rental-manager'), date('Y-m-d H:i')),
        ));
        
        if (is_wp_error($booking_id)) {
            wp_send_json_error(__('Failed to create booking.', 'custom-rental-manager'));
        }
        
        // Prepare customer data
        $customer_data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'date_of_birth' => sanitize_text_field($_POST['date_of_birth'] ?? ''),
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'license_number' => sanitize_text_field($_POST['license_number'] ?? ''),
            'emergency_contact' => sanitize_text_field($_POST['emergency_contact'] ?? ''),
            'emergency_phone' => sanitize_text_field($_POST['emergency_phone'] ?? ''),
        );
        
        // Save booking data
        $booking_data = array(
            'vehicle_id' => intval($_POST['vehicle_id']),
            'pickup_date' => sanitize_text_field($_POST['pickup_date']),
            'return_date' => sanitize_text_field($_POST['return_date']),
            'pickup_time' => sanitize_text_field($_POST['pickup_time'] ?? '09:00'),
            'return_time' => sanitize_text_field($_POST['return_time'] ?? '18:00'),
            'pickup_location' => sanitize_text_field($_POST['pickup_location'] ?? ''),
            'return_location' => sanitize_text_field($_POST['return_location'] ?? ''),
            'home_delivery' => isset($_POST['home_delivery']) ? 1 : 0,
            'delivery_address' => sanitize_textarea_field($_POST['delivery_address'] ?? ''),
            'extras' => isset($_POST['extras']) ? array_map('sanitize_text_field', $_POST['extras']) : array(),
            'special_requests' => sanitize_textarea_field($_POST['special_requests'] ?? ''),
        );
        
        update_post_meta($booking_id, '_crcm_booking_data', $booking_data);
        update_post_meta($booking_id, '_crcm_customer_data', $customer_data);
        
        // Set initial status
        update_post_meta($booking_id, '_crcm_booking_status', 'pending');
        
        // Generate booking number
        $booking_number = $this->generate_booking_number();
        update_post_meta($booking_id, '_crcm_booking_number', $booking_number);
        
        // Auto-register customer
        $customer_user_id = $this->auto_register_customer($booking_id, $customer_data);
        
        // Trigger booking created action
        do_action('crcm_booking_created', $booking_id, $customer_data);
        
        wp_send_json_success(array(
            'booking_id' => $booking_id,
            'booking_number' => $booking_number,
            'customer_id' => $customer_user_id,
            'message' => __('Booking created successfully! Customer account has been created.', 'custom-rental-manager'),
        ));
    }
    
    /**
     * AJAX cancel booking
     */
    public function ajax_cancel_booking() {
        check_ajax_referer('crcm_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id']);
        
        if (!$booking_id || get_post_type($booking_id) !== 'crcm_booking') {
            wp_send_json_error(__('Invalid booking ID.', 'custom-rental-manager'));
        }
        
        // Check if user can cancel this booking
        $customer_user_id = get_post_meta($booking_id, '_crcm_customer_user_id', true);
        $current_user = wp_get_current_user();
        
        if (!current_user_can('manage_options') && $current_user->ID != $customer_user_id) {
            wp_send_json_error(__('You do not have permission to cancel this booking.', 'custom-rental-manager'));
        }
        
        // Update status to cancelled
        update_post_meta($booking_id, '_crcm_booking_status', 'cancelled');
        
        wp_send_json_success(__('Booking cancelled successfully.', 'custom-rental-manager'));
    }
    
    /**
     * Handle booking status change
     */
    public function on_booking_status_change($new_status, $old_status, $post) {
        if ($post->post_type !== 'crcm_booking') {
            return;
        }
        
        // Send email notifications based on status change
        if (function_exists('crcm') && crcm()->email_manager) {
            $booking_status = get_post_meta($post->ID, '_crcm_booking_status', true);
            
            switch ($booking_status) {
                case 'confirmed':
                    crcm()->email_manager->send_booking_confirmation($post->ID);
                    break;
                case 'cancelled':
                    crcm()->email_manager->send_cancellation_email($post->ID);
                    break;
            }
        }
    }
    
    /**
     * AJAX get booking details
     */
    public function ajax_get_booking_details() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');
        
        $booking_id = intval($_POST['booking_id']);
        
        if (!$booking_id || get_post_type($booking_id) !== 'crcm_booking') {
            wp_send_json_error(__('Invalid booking ID.', 'custom-rental-manager'));
        }
        
        $booking_data = get_post_meta($booking_id, '_crcm_booking_data', true);
        $customer_data = get_post_meta($booking_id, '_crcm_customer_data', true);
        $payment_data = get_post_meta($booking_id, '_crcm_payment_data', true);
        $booking_status = get_post_meta($booking_id, '_crcm_booking_status', true);
        $booking_number = get_post_meta($booking_id, '_crcm_booking_number', true);
        
        $vehicle = get_post($booking_data['vehicle_id']);
        
        ob_start();
        ?>
        <div class="crcm-booking-details">
            <h3><?php printf(__('Booking Details - %s', 'custom-rental-manager'), $booking_number); ?></h3>
            
            <table class="widefat fixed striped">
                <tbody>
                    <tr>
                        <td><strong><?php _e('Customer:', 'custom-rental-manager'); ?></strong></td>
                        <td><?php echo esc_html($customer_data['first_name'] . ' ' . $customer_data['last_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Vehicle:', 'custom-rental-manager'); ?></strong></td>
                        <td><?php echo $vehicle ? esc_html($vehicle->post_title) : __('Unknown', 'custom-rental-manager'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Dates:', 'custom-rental-manager'); ?></strong></td>
                        <td>
                            <?php echo esc_html(date_i18n('M j, Y', strtotime($booking_data['pickup_date']))); ?> - 
                            <?php echo esc_html(date_i18n('M j, Y', strtotime($booking_data['return_date']))); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Status:', 'custom-rental-manager'); ?></strong></td>
                        <td><?php echo crcm_get_status_badge($booking_status); ?></td>
                    </tr>
                    <?php if ($payment_data && isset($payment_data['total_amount'])): ?>
                    <tr>
                        <td><strong><?php _e('Total:', 'custom-rental-manager'); ?></strong></td>
                        <td>€<?php echo number_format($payment_data['total_amount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p>
                <a href="<?php echo get_edit_post_link($booking_id); ?>" class="button button-primary">
                    <?php _e('Edit Booking', 'custom-rental-manager'); ?>
                </a>
            </p>
        </div>
        <?php
        $content = ob_get_clean();
        wp_send_json_success($content);
    }
    
    /**
     * Get locations array
     */
    public function get_locations() {
        return $this->locations;
    }
}

// Add CSS for customer badge
add_action('admin_head', function() {
    ?>
    <style>
    .crcm-customer-badge {
        display: inline-block;
        background: #27ae60;
        color: white;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }
    </style>
    <?php
});
