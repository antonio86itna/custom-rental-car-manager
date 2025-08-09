<?php
/**
 * Booking Manager Class - COMPLETE SYNCHRONIZATION & USER ROLES
 * 
 * Full integration with vehicle data, dynamic pricing, availability, extras,
 * insurance, and complete user role management.
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
        add_action('trashed_post', array($this, 'handle_trash_booking'));
        add_action('untrashed_post', array($this, 'handle_untrash_booking'));
        
        // AJAX handlers for dynamic booking creation
        add_action('wp_ajax_crcm_get_vehicle_booking_data', array($this, 'ajax_get_vehicle_booking_data'));
        add_action('wp_ajax_crcm_calculate_booking_total', array($this, 'ajax_calculate_booking_total'));
        add_action('wp_ajax_crcm_check_vehicle_availability', array($this, 'ajax_check_vehicle_availability'));
        add_action('wp_ajax_crcm_search_customers', array($this, 'ajax_search_customers'));
        
        // User management
        add_action('user_register', array($this, 'assign_default_customer_role'));
        add_filter('manage_users_columns', array($this, 'add_user_role_column'));
        add_action('manage_users_custom_column', array($this, 'show_user_role_column'), 10, 3);

        // Booking columns
        add_filter('manage_crcm_booking_posts_columns', array($this, 'booking_columns'));
        add_action('manage_crcm_booking_posts_custom_column', array($this, 'booking_column_content'), 10, 2);

        // Booking status scheduler
        add_action('crcm_booking_status_check', array($this, 'process_scheduled_statuses'));
        if (!wp_next_scheduled('crcm_booking_status_check')) {
            wp_schedule_event(time(), 'hourly', 'crcm_booking_status_check');
        }

        add_action('admin_notices', array($this, 'render_booking_lock_notice'));

    }

    /**
     * Process booking status transitions on scheduled event.
     *
     * Activates confirmed bookings whose pickup time has passed and
     * completes active bookings whose return time has passed.
     *
     * @return void
     */
    public function process_scheduled_statuses() {
        $now = current_time('timestamp');

        $bookings = get_posts(
            array(
                'post_type'      => 'crcm_booking',
                'post_status'    => array('publish', 'private'),
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => '_crcm_booking_status',
                        'value'   => array('pending', 'confirmed', 'active'),
                        'compare' => 'IN',
                    ),
                ),
            )
        );

        foreach ($bookings as $booking) {
            $booking_id = $booking->ID;
            $status     = get_post_meta($booking_id, '_crcm_booking_status', true);
            $data       = get_post_meta($booking_id, '_crcm_booking_data', true);

            if (!is_array($data)) {
                continue;
            }

            $pickup_time = strtotime(($data['pickup_date'] ?? '') . ' ' . ($data['pickup_time'] ?? ''));
            $return_time = strtotime(($data['return_date'] ?? '') . ' ' . ($data['return_time'] ?? ''));

            if ('confirmed' === $status && $pickup_time && $pickup_time <= $now) {
                $this->update_booking_status($booking_id, 'active');
            } elseif ('active' === $status && $return_time && $return_time <= $now) {
                $this->update_booking_status($booking_id, 'completed');
            } elseif ('pending' === $status && $pickup_time && $pickup_time <= $now) {
                $this->update_booking_status($booking_id, 'cancelled');
            }
        }
    }

    /**
     * Update booking status and log the transition.
     *
     * @param int    $booking_id Booking ID.
     * @param string $new_status New status slug.
     *
     * @return void
     */
    protected function update_booking_status($booking_id, $new_status) {
        $old_status = get_post_meta($booking_id, '_crcm_booking_status', true);

        if ($old_status === $new_status) {
            return;
        }

        update_post_meta(
            $booking_id,
            '_crcm_last_status_change',
            array(
                'old'  => $old_status,
                'new'  => $new_status,
                'time' => current_time('mysql'),
            )
        );

        update_post_meta($booking_id, '_crcm_booking_status', $new_status);

        do_action('crcm_booking_status_changed', $booking_id, $new_status, $old_status);
    }

    /**
     * Create a booking programmatically.
     *
     * @param array $data Booking data.
     * @return array|WP_Error
     */
    public function create_booking($data) {
        $vehicle_id = isset($data['vehicle_id']) ? intval($data['vehicle_id']) : 0;
        if ($vehicle_id <= 0) {
            return new WP_Error('invalid_vehicle', __('Invalid vehicle ID', 'custom-rental-manager'));
        }

        $pickup_date = sanitize_text_field($data['pickup_date'] ?? '');
        $return_date = sanitize_text_field($data['return_date'] ?? '');
        if (!$pickup_date || !$return_date || !strtotime($pickup_date) || !strtotime($return_date) || $return_date < $pickup_date) {
            return new WP_Error('invalid_dates', __('Invalid pickup or return date', 'custom-rental-manager'));
        }

        $customer_raw = $data['customer_data'] ?? array();
        $customer_data = array(
            'first_name'         => sanitize_text_field($customer_raw['first_name'] ?? ''),
            'last_name'          => sanitize_text_field($customer_raw['last_name'] ?? ''),
            'email'              => sanitize_email($customer_raw['email'] ?? ''),
            'phone'              => sanitize_text_field($customer_raw['phone'] ?? ''),
            'preferred_language' => sanitize_text_field($customer_raw['preferred_language'] ?? ''),
        );
        if (empty($customer_data['first_name']) || empty($customer_data['last_name']) || empty($customer_data['email']) || !is_email($customer_data['email'])) {
            return new WP_Error('invalid_customer', __('Invalid customer data', 'custom-rental-manager'));
        }

        $vehicle = get_post($vehicle_id);
        if (!$vehicle || $vehicle->post_type !== 'crcm_vehicle') {
            return new WP_Error('invalid_vehicle', __('Vehicle not found', 'custom-rental-manager'));
        }

        $vehicle_manager = crcm()->vehicle_manager;
        $available = $vehicle_manager->check_availability($vehicle_id, $pickup_date, $return_date);
        if ($available <= 0) {
            return new WP_Error('vehicle_unavailable', __('Vehicle not available for selected dates', 'custom-rental-manager'));
        }

        $booking_post = array(
            'post_type'   => 'crcm_booking',
            'post_status' => 'publish',
            'post_title'  => sprintf(__('Booking for %s %s', 'custom-rental-manager'), $customer_data['first_name'], $customer_data['last_name']),
        );
        $booking_id = wp_insert_post($booking_post, true);
        if (is_wp_error($booking_id)) {
            return $booking_id;
        }

        $booking_number = crcm_get_next_booking_number();
        update_post_meta($booking_id, '_crcm_booking_number', $booking_number);

        $booking_data = array(
            'vehicle_id'      => $vehicle_id,
            'pickup_date'     => $pickup_date,
            'return_date'     => $return_date,
            'pickup_time'     => sanitize_text_field($data['pickup_time'] ?? '09:00'),
            'return_time'     => sanitize_text_field($data['return_time'] ?? '18:00'),
            'pickup_location' => sanitize_text_field($data['pickup_location'] ?? ''),
            'return_location' => sanitize_text_field($data['return_location'] ?? ''),
            'home_delivery'   => !empty($data['home_delivery']),
            'delivery_address'=> sanitize_text_field($data['delivery_address'] ?? ''),
            'extras'          => array_map('sanitize_text_field', $data['extras'] ?? array()),
            'insurance_type'  => sanitize_text_field($data['insurance_type'] ?? 'basic'),
            'notes'           => sanitize_textarea_field($data['notes'] ?? ''),
        );
        update_post_meta($booking_id, '_crcm_booking_data', $booking_data);
        update_post_meta($booking_id, '_crcm_delivery_address', $booking_data['delivery_address']);
        update_post_meta($booking_id, '_crcm_customer_data', $customer_data);

        $this->update_booking_status($booking_id, 'pending');

        return array(
            'booking_id'     => $booking_id,
            'booking_number' => $booking_number,
            'booking_data'   => $booking_data,
            'customer_data'  => $customer_data,
            'status'         => 'pending',
        );
    }

    /**
     * Retrieve a booking.
     *
     * @param int $booking_id Booking ID.
     * @return array|WP_Error
     */
    public function get_booking($booking_id) {
        $booking_id = intval($booking_id);
        if ($booking_id <= 0) {
            return new WP_Error('invalid_booking_id', __('Invalid booking ID', 'custom-rental-manager'));
        }

        $post = get_post($booking_id);
        if (!$post || $post->post_type !== 'crcm_booking') {
            return new WP_Error('booking_not_found', __('Booking not found', 'custom-rental-manager'));
        }

        $booking_data = get_post_meta($booking_id, '_crcm_booking_data', true);
        if (!is_array($booking_data)) {
            $booking_data = array();
        }
        $booking_data = array_map('sanitize_text_field', $booking_data);
        $booking_data['home_delivery']   = !empty($booking_data['home_delivery']);
        $booking_data['delivery_address'] = sanitize_text_field($booking_data['delivery_address'] ?? get_post_meta($booking_id, '_crcm_delivery_address', true));
        if (isset($booking_data['extras']) && is_array($booking_data['extras'])) {
            $booking_data['extras'] = array_map('sanitize_text_field', $booking_data['extras']);
        }

        $customer_data = get_post_meta($booking_id, '_crcm_customer_data', true);
        if (!is_array($customer_data)) {
            $customer_data = array();
        }
        $customer_data = array(
            'first_name'         => sanitize_text_field($customer_data['first_name'] ?? ''),
            'last_name'          => sanitize_text_field($customer_data['last_name'] ?? ''),
            'email'              => sanitize_email($customer_data['email'] ?? ''),
            'phone'              => sanitize_text_field($customer_data['phone'] ?? ''),
            'preferred_language' => sanitize_text_field($customer_data['preferred_language'] ?? ''),
        );

        $pricing = get_post_meta($booking_id, '_crcm_pricing_breakdown', true);
        if (!is_array($pricing)) {
            $pricing = array();
        }
        if (!empty($pricing['line_items']) && is_array($pricing['line_items'])) {
            $clean_items = array();
            foreach ($pricing['line_items'] as $item) {
                $clean_items[] = array(
                    'name'       => sanitize_text_field($item['name'] ?? ''),
                    'qty'        => intval($item['qty'] ?? 0),
                    'amount'     => floatval($item['amount'] ?? 0),
                    'free'       => !empty($item['free']),
                    'type'       => in_array($item['type'] ?? 'flat', array('daily', 'flat'), true) ? $item['type'] : 'flat',
                    'base_rate'  => floatval($item['base_rate'] ?? 0),
                    'extra_rate' => floatval($item['extra_rate'] ?? 0),
                );
            }
            $pricing['line_items'] = $clean_items;
        }

        $status = get_post_meta($booking_id, '_crcm_booking_status', true) ?: 'pending';
        $booking_number = get_post_meta($booking_id, '_crcm_booking_number', true);

        return array(
            'booking_id'        => $booking_id,
            'booking_number'    => $booking_number,
            'status'            => $status,
            'booking_data'      => $booking_data,
            'pricing_breakdown' => $pricing,
            'customer_data'     => $customer_data,
        );
    }

    /**
     * Calculate booking pricing breakdown.
     *
     * @param array $data Booking data.
     * @return array
     */
    public function calculate_booking_pricing($data) {
        $vehicle_id  = intval($data['vehicle_id'] ?? 0);
        $pickup_date = sanitize_text_field($data['pickup_date'] ?? '');
        $return_date = sanitize_text_field($data['return_date'] ?? '');
        $pickup_time = sanitize_text_field($data['pickup_time'] ?? '');
        $return_time = sanitize_text_field($data['return_time'] ?? '');

        if (!$vehicle_id || !$pickup_date || !$return_date) {
            return array();
        }

        $rental_days = crcm_calculate_rental_days($pickup_date, $return_date, $pickup_time, $return_time, $vehicle_id);

        $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
        $daily_rate   = floatval($pricing_data['daily_rate'] ?? 0);

        $end_date = new DateTime($pickup_date);
        $end_date->add(new DateInterval('P' . $rental_days . 'D'));

        $base_total          = crcm_calculate_vehicle_pricing($vehicle_id, $pickup_date, $end_date->format('Y-m-d'));
        $base_without_extra  = $daily_rate * $rental_days;
        $extra_daily         = $rental_days > 0 ? max(0, ($base_total - $base_without_extra) / $rental_days) : 0;

        $line_items = array(
            array(
                'name'       => __('Noleggio', 'custom-rental-manager'),
                'qty'        => $rental_days,
                'amount'     => $base_total,
                'free'       => false,
                'type'       => 'daily',
                'base_rate'  => $daily_rate,
                'extra_rate' => $extra_daily,
            ),
        );

        return array(
            'base_total'      => $base_total,
            'extras_total'    => 0,
            'insurance_total' => 0,
            'tax_total'       => 0,
            'manual_discount' => 0,
            'discount_reason' => '',
            'final_total'     => $base_total,
            'line_items'      => $line_items,
        );
    }

    /**
     * Add meta boxes for booking post type
     */
    public function add_meta_boxes() {
        add_meta_box(
            'crcm_booking_details',
            '🎯 ' . __('Booking Details', 'custom-rental-manager'),
            array($this, 'booking_details_meta_box'),
            'crcm_booking',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crcm_booking_customer',
            '👤 ' . __('Customer Information', 'custom-rental-manager'),
            array($this, 'customer_meta_box'),
            'crcm_booking',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crcm_booking_vehicle',
            '🚗 ' . __('Vehicle Selection', 'custom-rental-manager'),
            array($this, 'vehicle_selection_meta_box'),
            'crcm_booking',
            'normal',
            'default'
        );
        
        add_meta_box(
            'crcm_booking_pricing',
            '💰 ' . __('Pricing & Extras', 'custom-rental-manager'),
            array($this, 'pricing_meta_box'),
            'crcm_booking',
            'normal',
            'default'
        );
        
        add_meta_box(
            'crcm_booking_status',
            '📊 ' . __('Booking Status', 'custom-rental-manager'),
            array($this, 'status_meta_box'),
            'crcm_booking',
            'side',
            'high'
        );
        
        add_meta_box(
            'crcm_booking_notes',
            '📝 ' . __('Notes & Comments', 'custom-rental-manager'),
            array($this, 'notes_meta_box'),
            'crcm_booking',
            'side',
            'default'
        );
    }
    
    /**
     * Main booking details meta box with dynamic synchronization
     */
    public function booking_details_meta_box($post) {
        wp_nonce_field('crcm_booking_meta_nonce', 'crcm_booking_meta_nonce_field');
        
        $booking_data = get_post_meta($post->ID, '_crcm_booking_data', true);
        
        // Default values
        if (empty($booking_data)) {
            $booking_data = array(
                'pickup_date' => date('Y-m-d'),
                'return_date' => date('Y-m-d', strtotime('+1 day')),
                'pickup_time' => '09:00',
                'return_time' => '18:00',
                'pickup_location' => 'ischia_porto',
                'return_location' => 'ischia_porto',
                'rental_days' => 1,
                'home_delivery' => false,
                'delivery_address' => '',
            );
        }

        $booking_data['home_delivery']   = !empty($booking_data['home_delivery']);
        $booking_data['delivery_address'] = $booking_data['delivery_address'] ?? '';
        
        // Get available locations from vehicle manager
        if (class_exists('CRCM_Vehicle_Manager')) {
            $vehicle_manager = new CRCM_Vehicle_Manager();
            $locations = $vehicle_manager->get_locations();
        } else {
            $locations = array(
                'ischia_porto' => array('name' => 'Ischia Porto', 'address' => 'Via Iasolino 94, Ischia'),
                'forio' => array('name' => 'Forio', 'address' => 'Via Filippo di Lustro 19, Forio')
            );
        }
        ?>
        
        <div class="crcm-booking-details-container">
            <div class="crcm-section-header">
                <h4><?php _e('Dettagli Prenotazione', 'custom-rental-manager'); ?></h4>
                <p class="description"><?php _e('Configura date, orari e luoghi per la prenotazione', 'custom-rental-manager'); ?></p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="pickup_date"><?php _e('Data Ritiro', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <input type="date" id="pickup_date" name="booking_data[pickup_date]" 
                               value="<?php echo esc_attr($booking_data['pickup_date']); ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required class="crcm-date-field" />
                    </td>
                </tr>
                
                <tr>
                    <th><label for="return_date"><?php _e('Data Riconsegna', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <input type="date" id="return_date" name="booking_data[return_date]" 
                               value="<?php echo esc_attr($booking_data['return_date']); ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required class="crcm-date-field" />
                        <p class="description" id="rental-days-display">
                            <?php printf(__('Giorni di noleggio: %d', 'custom-rental-manager'), $booking_data['rental_days'] ?? 1); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="pickup_time"><?php _e('Orario Ritiro', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <select id="pickup_time" name="booking_data[pickup_time]">
                            <?php for ($h = 8; $h <= 20; $h++): ?>
                                <?php for ($m = 0; $m < 60; $m += 30): ?>
                                    <?php $time = sprintf('%02d:%02d', $h, $m); ?>
                                    <option value="<?php echo $time; ?>" <?php selected($booking_data['pickup_time'] ?? '09:00', $time); ?>>
                                        <?php echo $time; ?>
                                    </option>
                                <?php endfor; ?>
                            <?php endfor; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="return_time"><?php _e('Orario Riconsegna', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <select id="return_time" name="booking_data[return_time]">
                            <?php for ($h = 8; $h <= 20; $h++): ?>
                                <?php for ($m = 0; $m < 60; $m += 30): ?>
                                    <?php $time = sprintf('%02d:%02d', $h, $m); ?>
                                    <option value="<?php echo $time; ?>" <?php selected($booking_data['return_time'] ?? '18:00', $time); ?>>
                                        <?php echo $time; ?>
                                    </option>
                                <?php endfor; ?>
                            <?php endfor; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="pickup_location"><?php _e('Luogo Ritiro', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <select id="pickup_location" name="booking_data[pickup_location]" required>
                            <?php foreach ($locations as $key => $location): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($booking_data['pickup_location'] ?? '', $key); ?>>
                                    <?php echo esc_html($location['name']); ?> - <?php echo esc_html($location['address']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="return_location"><?php _e('Luogo Riconsegna', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <select id="return_location" name="booking_data[return_location]" required>
                            <?php foreach ($locations as $key => $location): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($booking_data['return_location'] ?? '', $key); ?>>
                                    <?php echo esc_html($location['name']); ?> - <?php echo esc_html($location['address']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th><label for="home_delivery"><?php _e('Consegna a domicilio', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="home_delivery" name="booking_data[home_delivery]" <?php checked(!empty($booking_data['home_delivery'])); ?> />
                            <?php _e('Richiedi consegna a domicilio', 'custom-rental-manager'); ?>
                        </label>
                    </td>
                </tr>
                <tr id="delivery_address_row" style="<?php echo !empty($booking_data['home_delivery']) ? '' : 'display:none;'; ?>">
                    <th><label for="delivery_address"><?php _e('Indirizzo di consegna', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <input type="text" id="delivery_address" name="booking_data[delivery_address]" value="<?php echo esc_attr($booking_data['delivery_address'] ?? ''); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
        </div>

        <script>
        jQuery(function($){
            function toggleDeliveryFields(){
                if($('#home_delivery').is(':checked')){
                    $('#pickup_location, #return_location').prop('disabled', true);
                    $('#delivery_address_row').show();
                } else {
                    $('#pickup_location, #return_location').prop('disabled', false);
                    $('#delivery_address_row').hide();
                }
            }
            $('#home_delivery').on('change', toggleDeliveryFields);
            toggleDeliveryFields();
        });
        </script>

        <!-- Hidden field for rental days -->
        <input type="hidden" name="booking_data[rental_days]" value="<?php echo esc_attr($booking_data['rental_days'] ?? 1); ?>" />
        <?php
    }
    
    /**
     * Customer selection meta box with dynamic search
     */
    public function customer_meta_box($post) {
        $booking_data = get_post_meta($post->ID, '_crcm_booking_data', true);
        $selected_customer_id = $booking_data['customer_id'] ?? '';
        
        // Get selected customer info
        $selected_customer = null;
        if ($selected_customer_id) {
            $selected_customer = get_user_by('ID', $selected_customer_id);
        }
        ?>
        
        <div class="crcm-customer-container">
            <div class="crcm-section-header">
                <h4><?php _e('Selezione Cliente', 'custom-rental-manager'); ?></h4>
                <p class="description"><?php _e('Cerca e seleziona il cliente per questa prenotazione', 'custom-rental-manager'); ?></p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="customer_search"><?php _e('Cerca Cliente', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <div class="crcm-customer-search-container">
                            <input type="text" id="customer_search" placeholder="<?php _e('Digita nome, email o telefono del cliente...', 'custom-rental-manager'); ?>" 
                                   class="widefat" />
                            <div id="customer_search_results" class="crcm-search-results"></div>
                        </div>
                        
                        <div id="selected_customer_info" class="crcm-selected-customer" <?php echo $selected_customer ? '' : 'style="display: none;"'; ?>>
                            <?php if ($selected_customer): ?>
                                <div class="customer-card">
                                    <h4><?php echo esc_html($selected_customer->display_name); ?></h4>
                                    <p><strong>Email:</strong> <?php echo esc_html($selected_customer->user_email); ?></p>
                                    <p><strong>Ruolo:</strong> <?php echo esc_html(ucfirst(reset($selected_customer->roles))); ?></p>
                                    <?php
                                    $phone = get_user_meta($selected_customer->ID, 'phone', true);
                                    if ($phone): ?>
                                        <p><strong>Telefono:</strong> <?php echo esc_html($phone); ?></p>
                                    <?php endif; ?>
                                    <button type="button" class="button button-secondary" id="change_customer">
                                        <?php _e('Cambia Cliente', 'custom-rental-manager'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <input type="hidden" id="selected_customer_id" name="booking_data[customer_id]" 
                               value="<?php echo esc_attr($selected_customer_id); ?>" required />
                        
                        <p class="description">
                            <?php _e('Solo utenti con ruolo "Rental Customer" possono essere selezionati', 'custom-rental-manager'); ?>
                            <br>
                            <a href="<?php echo admin_url('user-new.php'); ?>" target="_blank">
                                <?php _e('Crea nuovo cliente →', 'custom-rental-manager'); ?>
                            </a>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php
    }
    
    /**
     * Vehicle selection meta box with dynamic data loading
     */
    public function vehicle_selection_meta_box($post) {
        $booking_data = get_post_meta($post->ID, '_crcm_booking_data', true);
        $selected_vehicle_id = $booking_data['vehicle_id'] ?? '';
        
        // Get available vehicles
        $vehicles = get_posts(array(
            'post_type' => 'crcm_vehicle',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        ?>
        
        <div class="crcm-vehicle-selection-container">
            <div class="crcm-section-header">
                <h4><?php _e('Selezione Veicolo', 'custom-rental-manager'); ?></h4>
                <p class="description"><?php _e('Seleziona il veicolo per questa prenotazione. I dati verranno caricati dinamicamente.', 'custom-rental-manager'); ?></p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="vehicle_id"><?php _e('Veicolo', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <select id="vehicle_id" name="booking_data[vehicle_id]" required class="widefat">
                            <option value=""><?php _e('Seleziona un veicolo...', 'custom-rental-manager'); ?></option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <?php
                                $vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
                                $pricing_data = get_post_meta($vehicle->ID, '_crcm_pricing_data', true);
                                $vehicle_type = $vehicle_data['vehicle_type'] ?? 'auto';
                                $daily_rate = $pricing_data['daily_rate'] ?? 0;
                                ?>
                                <option value="<?php echo $vehicle->ID; ?>" 
                                        data-type="<?php echo esc_attr($vehicle_type); ?>"
                                        data-rate="<?php echo esc_attr($daily_rate); ?>"
                                        <?php selected($selected_vehicle_id, $vehicle->ID); ?>>
                                    <?php echo esc_html($vehicle->post_title); ?> 
                                    (<?php echo ucfirst($vehicle_type); ?> - €<?php echo number_format($daily_rate, 2); ?>/giorno)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <!-- Vehicle Details Display -->
            <div id="vehicle_details_display" class="crcm-vehicle-details" style="<?php echo $selected_vehicle_id ? '' : 'display: none;'; ?>">
                <div class="crcm-section-header">
                    <h4><?php _e('Dettagli Veicolo Selezionato', 'custom-rental-manager'); ?></h4>
                </div>
                <div id="vehicle_details_content">
                    <?php if ($selected_vehicle_id): ?>
                        <?php $this->render_vehicle_details($selected_vehicle_id); ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Availability Check -->
            <div id="availability_check" class="crcm-availability-check" style="<?php echo $selected_vehicle_id ? '' : 'display: none;'; ?>">
                <div class="crcm-section-header">
                    <h4><?php _e('Controllo Disponibilità', 'custom-rental-manager'); ?></h4>
                </div>
                <div id="availability_status" class="crcm-availability-status">
                    <!-- Will be populated via AJAX -->
                </div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render vehicle details for display
     */
    private function render_vehicle_details($vehicle_id) {
        $vehicle = get_post($vehicle_id);
        $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
        $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
        $extras_data = get_post_meta($vehicle_id, '_crcm_extras_data', true);
        $insurance_data = get_post_meta($vehicle_id, '_crcm_insurance_data', true);
        $misc_data = get_post_meta($vehicle_id, '_crcm_misc_data', true);
        ?>
        
        <div class="crcm-vehicle-summary">
            <div class="vehicle-basic-info">
                <h5><?php echo esc_html($vehicle->post_title); ?></h5>
                <div class="vehicle-specs">
                    <?php if (isset($vehicle_data['seats'])): ?>
                        <span class="spec-item">👥 <?php echo $vehicle_data['seats']; ?> posti</span>
                    <?php endif; ?>
                    <?php if (isset($vehicle_data['engine_size'])): ?>
                        <span class="spec-item">🏍️ <?php echo $vehicle_data['engine_size']; ?></span>
                    <?php endif; ?>
                    <?php if (isset($vehicle_data['transmission'])): ?>
                        <span class="spec-item">⚙️ <?php echo ucfirst($vehicle_data['transmission']); ?></span>
                    <?php endif; ?>
                    <?php if (isset($vehicle_data['fuel_type'])): ?>
                        <span class="spec-item">⛽ <?php echo ucfirst($vehicle_data['fuel_type']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="vehicle-pricing">
                    <strong>💰 €<?php echo number_format($pricing_data['daily_rate'] ?? 0, 2); ?>/giorno</strong>
                </div>
            </div>
            
            <?php if (!empty($extras_data)): ?>
                <div class="vehicle-extras">
                    <h6><?php _e('Servizi Extra Disponibili', 'custom-rental-manager'); ?></h6>
                    <ul>
                        <?php foreach ($extras_data as $extra): ?>
                            <li>
                                <?php echo esc_html($extra['name']); ?> 
                                <span class="extra-price">+€<?php echo number_format($extra['daily_rate'], 2); ?>/giorno</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($insurance_data) && !empty($insurance_data['premium']['enabled'])): ?>
                <div class="vehicle-insurance">
                    <h6><?php _e('Assicurazione Premium Disponibile', 'custom-rental-manager'); ?></h6>
                    <p>
                        Franchigia €<?php echo number_format($insurance_data['premium']['deductible'], 0); ?> 
                        <span class="insurance-price">+€<?php echo number_format($insurance_data['premium']['daily_rate'], 2); ?>/giorno</span>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($misc_data)): ?>
                <div class="vehicle-policies">
                    <h6><?php _e('Politiche Veicolo', 'custom-rental-manager'); ?></h6>
                    <ul>
                        <li>Min/Max giorni: <?php echo $misc_data['min_rental_days'] ?? 1; ?>-<?php echo $misc_data['max_rental_days'] ?? 30; ?></li>
                        <?php if (!empty($misc_data['cancellation_enabled'])): ?>
                            <li>✅ Cancellazione gratuita fino a <?php echo $misc_data['cancellation_days'] ?? 5; ?> giorni prima</li>
                        <?php else: ?>
                            <li>❌ Cancellazione non consentita</li>
                        <?php endif; ?>
                        <?php if (!empty($misc_data['featured_vehicle'])): ?>
                            <li>⭐ Veicolo in evidenza</li>
                        <?php endif; ?>
                        <?php if (!empty($misc_data['late_return_rule']) && !empty($misc_data['late_return_time'])): ?>
                            <li><?php printf(__('Riconsegna tardiva entro %s', 'custom-rental-manager'), esc_html($misc_data['late_return_time'])); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Pricing and extras meta box with dynamic calculation
     */
    public function pricing_meta_box($post) {
        $booking_data = get_post_meta($post->ID, '_crcm_booking_data', true);
        $pricing_breakdown = get_post_meta($post->ID, '_crcm_pricing_breakdown', true);
        $currency_symbol = crcm_get_setting('currency_symbol', '€');
        
        // Default values
        if (empty($pricing_breakdown)) {
            $pricing_breakdown = array(
                'base_total'        => 0,
                'extras_total'      => 0,
                'insurance_total'   => 0,
                'tax_total'         => 0,
                'manual_discount'   => 0,
                'discount_reason'   => '',
                'final_total'       => 0,
                'selected_extras'   => array(),
                'selected_insurance'=> 'basic',
                'line_items'        => array(),
            );
        }
        ?>
        
        <div class="crcm-pricing-container">
            <div class="crcm-section-header">
                <h4><?php _e('Prezzi e Servizi Extra', 'custom-rental-manager'); ?></h4>
                <p class="description"><?php _e('Seleziona servizi extra e visualizza il riepilogo prezzi', 'custom-rental-manager'); ?></p>
            </div>
            
            <!-- Extra Services Selection -->
            <div id="extras-selection" class="crcm-extras-selection" style="display: none;">
                <h5><?php _e('Servizi Extra', 'custom-rental-manager'); ?></h5>
                <div id="extras-list">
                    <!-- Will be populated via AJAX when vehicle is selected -->
                </div>
            </div>
            
            <!-- Insurance Selection -->
            <div id="insurance-selection" class="crcm-insurance-selection" style="display: none;">
                <h5><?php _e('Opzioni Assicurative', 'custom-rental-manager'); ?></h5>
                <div id="insurance-options">
                    <!-- Will be populated via AJAX when vehicle is selected -->
                </div>
            </div>
            
            <!-- Manual Discount -->
            <div class="crcm-discount-section">
                <h5><?php _e('Sconto Manuale', 'custom-rental-manager'); ?></h5>
                <table class="form-table">
                    <tr>
                        <th><label for="manual_discount"><?php _e('Sconto (€)', 'custom-rental-manager'); ?></label></th>
                        <td>
                            <input type="number" id="manual_discount" name="pricing_breakdown[manual_discount]" 
                                   value="<?php echo esc_attr($pricing_breakdown['manual_discount'] ?? 0); ?>" 
                                   step="0.01" min="0" class="small-text" />
                            <p class="description"><?php _e('Sconto fisso in euro da applicare al totale', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="discount_reason"><?php _e('Motivo Sconto', 'custom-rental-manager'); ?></label></th>
                        <td>
                            <input type="text" id="discount_reason" name="pricing_breakdown[discount_reason]" 
                                   value="<?php echo esc_attr($pricing_breakdown['discount_reason'] ?? ''); ?>" 
                                   class="widefat" placeholder="<?php _e('Es: Cliente fedele, promozione speciale...', 'custom-rental-manager'); ?>" />
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Pricing Breakdown -->
            <div class="crcm-pricing-breakdown">
                <h5><?php _e('Riepilogo Prezzi', 'custom-rental-manager'); ?></h5>
                <?php if (!empty($booking_data['home_delivery']) && !empty($booking_data['delivery_address'])) : ?>
                    <p><strong><?php _e('Indirizzo di consegna', 'custom-rental-manager'); ?>:</strong> <?php echo esc_html($booking_data['delivery_address']); ?></p>
                <?php endif; ?>
                <table class="crcm-pricing-table">
                    <tbody id="pricing-breakdown-content">
                        <?php if (!empty($pricing_breakdown['line_items'])) : ?>
                            <?php foreach ($pricing_breakdown['line_items'] as $item) : ?>
                                <tr class="pricing-row line-item">
                                    <td>
                                        <?php
                                        $label = crcm_format_line_item_label($item, $currency_symbol);
                                        echo $label;
                                        if (!empty($item['free'])) :
                                            ?> (<?php _e('Incluso', 'custom-rental-manager'); ?>)<?php
                                        endif;
                                        ?>
                                    </td>
                                    <td class="price-cell"><?php echo esc_html($currency_symbol); ?><span><?php echo esc_html(number_format((float) ($item['amount'] ?? 0), 2)); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr class="pricing-row discount-row" style="<?php echo (!empty($pricing_breakdown['manual_discount']) ? '' : 'display: none;'); ?>">
                            <td><?php _e('Sconto applicato', 'custom-rental-manager'); ?></td>
                            <td class="price-cell discount">-<?php echo esc_html($currency_symbol); ?><span id="discount-total"><?php echo esc_html(number_format((float) ($pricing_breakdown['manual_discount'] ?? 0), 2)); ?></span></td>
                        </tr>
                        <tr class="pricing-row total-row">
                            <td><strong><?php _e('TOTALE', 'custom-rental-manager'); ?></strong></td>
                            <td class="price-cell"><strong><?php echo esc_html($currency_symbol); ?><span id="final-total"><?php echo esc_html(number_format((float) ($pricing_breakdown['final_total'] ?? 0), 2)); ?></span></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Hidden fields for storing pricing data -->
            <input type="hidden" name="pricing_breakdown[line_items]" id="line_items_input" value="<?php echo esc_attr(wp_json_encode($pricing_breakdown['line_items'] ?? array())); ?>" />
            <input type="hidden" name="pricing_breakdown[base_total]" id="base_total_input" value="<?php echo esc_attr($pricing_breakdown['base_total']); ?>" />
            <input type="hidden" name="pricing_breakdown[extras_total]" id="extras_total_input" value="<?php echo esc_attr($pricing_breakdown['extras_total']); ?>" />
            <input type="hidden" name="pricing_breakdown[insurance_total]" id="insurance_total_input" value="<?php echo esc_attr($pricing_breakdown['insurance_total']); ?>" />
            <input type="hidden" name="pricing_breakdown[final_total]" id="final_total_input" value="<?php echo esc_attr($pricing_breakdown['final_total']); ?>" />
        </div>

        <?php
    }
    
    /**
     * Status meta box
     */
    public function status_meta_box($post) {
        $booking_status = get_post_meta($post->ID, '_crcm_booking_status', true);
        if (empty($booking_status)) {
            $booking_status = 'pending';
        }
        
        $statuses = array(
            'pending' => __('In Attesa', 'custom-rental-manager'),
            'confirmed' => __('Confermata', 'custom-rental-manager'),
            'active' => __('In Corso', 'custom-rental-manager'),
            'completed' => __('Completata', 'custom-rental-manager'),
            'cancelled' => __('Cancellata', 'custom-rental-manager'),
        );
        ?>
        
        <div class="crcm-status-container">
            <table class="form-table">
                <tr>
                    <th><label for="booking_status"><?php _e('Stato', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <select id="booking_status" name="booking_status" class="widefat">
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($booking_status, $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <div class="status-info">
                <p class="description">
                    <strong>In Attesa:</strong> Prenotazione creata<br>
                    <strong>Confermata:</strong> Pagamento ricevuto<br>
                    <strong>In Corso:</strong> Veicolo ritirato<br>
                    <strong>Completata:</strong> Veicolo riconsegnato<br>
                    <strong>Cancellata:</strong> Prenotazione annullata
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Notes meta box
     */
    public function notes_meta_box($post) {
        $notes = get_post_meta($post->ID, '_crcm_booking_notes', true);
        $internal_notes = get_post_meta($post->ID, '_crcm_booking_internal_notes', true);
        ?>
        
        <div class="crcm-notes-container">
            <table class="form-table">
                <tr>
                    <th><label for="booking_notes"><?php _e('Note Cliente', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <textarea id="booking_notes" name="booking_notes" rows="4" class="widefat"><?php echo esc_textarea($notes); ?></textarea>
                        <p class="description"><?php _e('Note visibili al cliente', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="internal_notes"><?php _e('Note Interne', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <textarea id="internal_notes" name="internal_notes" rows="4" class="widefat"><?php echo esc_textarea($internal_notes); ?></textarea>
                        <p class="description"><?php _e('Note riservate allo staff', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * AJAX: Search customers with role filter
     */
    public function ajax_search_customers() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');

        if ( ! current_user_can('crcm_manage_bookings') && ! current_user_can('manage_options') ) {
            wp_send_json_error('Permission denied');
        }

        $query = sanitize_text_field($_POST['query'] ?? '');

        if (strlen($query) < 2) {
            wp_send_json_error('Query too short');
        }

        // Search users with customer role
        $users = get_users(array(
            'role' => 'crcm_customer',
            'search' => '*' . $query . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'number' => 10,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));

        $results = array();
        foreach ($users as $user) {
            $results[] = array(
                'ID' => $user->ID,
                'display_name' => $user->display_name,
                'user_email' => $user->user_email,
                'phone' => get_user_meta($user->ID, 'phone', true),
            );
        }

        wp_send_json_success($results);
    }
    
    /**
     * AJAX: Get vehicle booking data
     */
    public function ajax_get_vehicle_booking_data() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');

        if ( ! current_user_can('crcm_manage_bookings') && ! current_user_can('manage_options') ) {
            wp_send_json_error('Permission denied');
        }

        $vehicle_id = absint($_POST['vehicle_id'] ?? 0);

        if (!$vehicle_id) {
            wp_send_json_error('Invalid vehicle ID');
        }
        
        $vehicle = get_post($vehicle_id);
        if (!$vehicle || $vehicle->post_type !== 'crcm_vehicle') {
            wp_send_json_error('Vehicle not found');
        }
        
        $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
        $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
        $extras_data = get_post_meta($vehicle_id, '_crcm_extras_data', true);
        $insurance_data = get_post_meta($vehicle_id, '_crcm_insurance_data', true);
        $misc_data = get_post_meta($vehicle_id, '_crcm_misc_data', true);
        
        ob_start();
        $this->render_vehicle_details($vehicle_id);
        $details_html = ob_get_clean();
        
        wp_send_json_success(array(
            'details' => $details_html,
            'vehicle_data' => $vehicle_data,
            'pricing' => $pricing_data,
            'extras' => $extras_data ?: array(),
            'insurance' => $insurance_data ?: array(),
            'misc' => $misc_data ?: array(),
        ));
    }

    /**
     * AJAX: Calculate booking total with custom rates.
     *
     * @return void
     */
    public function ajax_calculate_booking_total() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');

        if ( ! current_user_can('crcm_manage_bookings') && ! current_user_can('manage_options') ) {
            wp_send_json_error('Permission denied');
        }

        $vehicle_id  = absint($_POST['vehicle_id'] ?? 0);
        $pickup_date = sanitize_text_field($_POST['pickup_date'] ?? '');
        $return_date = sanitize_text_field($_POST['return_date'] ?? '');
        $pickup_time = sanitize_text_field($_POST['pickup_time'] ?? '');
        $return_time = sanitize_text_field($_POST['return_time'] ?? '');

        if (!$vehicle_id || !$pickup_date || !$return_date) {
            wp_send_json_error('Missing parameters');
        }

        if (!function_exists('crcm_calculate_vehicle_pricing')) {
            wp_send_json_error('Pricing function not available');
        }

        $rental_days = crcm_calculate_rental_days($pickup_date, $return_date, $pickup_time, $return_time, $vehicle_id);

        $end_date = new DateTime($pickup_date);
        $end_date->add(new DateInterval('P' . $rental_days . 'D'));
        $base_total = crcm_calculate_vehicle_pricing($vehicle_id, $pickup_date, $end_date->format('Y-m-d'));

        wp_send_json_success(array(
            'base_total'  => $base_total,
            'rental_days' => $rental_days,
        ));
    }

    /**
     * AJAX: Check vehicle availability
     */
    public function ajax_check_vehicle_availability() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');

        if ( ! current_user_can('crcm_manage_bookings') && ! current_user_can('manage_options') ) {
            wp_send_json_error('Permission denied');
        }

        $vehicle_id  = absint($_POST['vehicle_id'] ?? 0);
        $pickup_date = sanitize_text_field($_POST['pickup_date'] ?? '');
        $return_date = sanitize_text_field($_POST['return_date'] ?? '');

        if (!$vehicle_id || !$pickup_date || !$return_date) {
            wp_send_json_error('Missing required parameters');
        }
        
        // Use vehicle manager to check availability
        if (class_exists('CRCM_Vehicle_Manager')) {
            $vehicle_manager = new CRCM_Vehicle_Manager();
            $available_quantity = $vehicle_manager->check_availability($vehicle_id, $pickup_date, $return_date);
            
            $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
            $total_quantity = isset($vehicle_data['quantity']) ? intval($vehicle_data['quantity']) : 0;
            
            wp_send_json_success(array(
                'available_quantity' => $available_quantity,
                'total_quantity' => $total_quantity,
                'is_available' => $available_quantity > 0,
            ));
        } else {
            wp_send_json_error('Vehicle manager not available');
        }
    }

    /**
     * Render notice and disable fields for locked bookings.
     *
     * @return void
     */
    public function render_booking_lock_notice() {
        global $pagenow, $post;

        if ('post.php' !== $pagenow || ! $post || 'crcm_booking' !== $post->post_type) {
            return;
        }

        $status = get_post_meta($post->ID, '_crcm_booking_status', true);
        if (!in_array($status, array('confirmed', 'active'), true)) {
            return;
        }

        $template = CRCM_PLUGIN_PATH . 'templates/admin/booking-edit.php';
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Compare old and new booking data.
     *
     * @param array $old_data Previous booking data.
     * @param array $new_data Updated booking data.
     *
     * @return array
     */
    private function compare_booking_data($old_data, $new_data) {
        $changes = array();

        foreach ($new_data as $key => $value) {
            $old_value = $old_data[$key] ?? '';

            if (is_array($value)) {
                $value     = implode(', ', $value);
                $old_value = is_array($old_value) ? implode(', ', $old_value) : $old_value;
            }

            if ($value !== $old_value) {
                $changes[$key] = array(
                    'old' => $old_value,
                    'new' => $value,
                );
            }
        }

        return $changes;
    }

    /**
     * Save booking meta data
     */
    public function save_booking_meta($post_id) {
        // Verify nonce
        $nonce = $_POST['crcm_booking_meta_nonce_field'] ?? '';
        if (!$nonce || !wp_verify_nonce($nonce, 'crcm_booking_meta_nonce')) {
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
        
        $old_booking_data = get_post_meta($post_id, '_crcm_booking_data', true);
        if (!is_array($old_booking_data)) {
            $old_booking_data = array();
        }

        // Save booking data
        if (isset($_POST['booking_data'])) {
            $posted_data = array();
            $raw_data    = $_POST['booking_data'];

            $posted_data['home_delivery']   = !empty($raw_data['home_delivery']) ? '1' : '';
            $posted_data['delivery_address'] = sanitize_text_field($raw_data['delivery_address'] ?? '');

            foreach ($raw_data as $key => $value) {
                if (in_array($key, array('home_delivery', 'delivery_address'), true)) {
                    continue;
                }
                $posted_data[$key] = sanitize_text_field($value);
            }

            $booking_data = array_merge($old_booking_data, $posted_data);

            update_post_meta($post_id, '_crcm_booking_data', $booking_data);
            update_post_meta($post_id, '_crcm_delivery_address', $posted_data['delivery_address']);

            $changes = $this->compare_booking_data($old_booking_data, $booking_data);
            if (!empty($changes)) {
                crcm()->email_manager->send_booking_update_notification($post_id, $changes);
            }

            $previous_customer_id = (int) get_post_meta($post_id, '_crcm_customer_user_id', true);
            $customer_user_id = !empty($booking_data['customer_id']) ? (int) $booking_data['customer_id'] : 0;
            update_post_meta($post_id, '_crcm_customer_user_id', $customer_user_id);

            if ($customer_user_id) {
                $user = get_user_by('ID', $customer_user_id);
                if ($user) {
                    $profile_data = get_user_meta($customer_user_id, 'crcm_profile_data', true);
                    $customer_data = array(
                        'user_id'    => $customer_user_id,
                        'first_name' => $profile_data['first_name'] ?? get_user_meta($customer_user_id, 'first_name', true),
                        'last_name'  => $profile_data['last_name'] ?? get_user_meta($customer_user_id, 'last_name', true),
                        'email'      => $user->user_email,
                        'phone'      => $profile_data['phone'] ?? '',
                    );
                    update_post_meta($post_id, '_crcm_customer_data', array_map('sanitize_text_field', $customer_data));
                }

                if ($customer_user_id !== $previous_customer_id) {
                    $total = (int) get_user_meta($customer_user_id, 'crcm_total_bookings', true);
                    update_user_meta($customer_user_id, 'crcm_total_bookings', $total + 1);
                    update_user_meta($customer_user_id, 'crcm_last_booking_date', current_time('mysql'));
                }
            }

            if ($previous_customer_id && $previous_customer_id !== $customer_user_id) {
                $prev_total = (int) get_user_meta($previous_customer_id, 'crcm_total_bookings', true);
                $prev_total = max(0, $prev_total - 1);
                update_user_meta($previous_customer_id, 'crcm_total_bookings', $prev_total);
            }
        }
        
        // Save pricing breakdown
        if (isset($_POST['pricing_breakdown'])) {
            $pricing_breakdown = array();
            foreach ($_POST['pricing_breakdown'] as $key => $value) {
                if ('line_items' === $key) {
                    $items = json_decode(wp_unslash($value), true);
                    $pricing_breakdown['line_items'] = array();
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $pricing_breakdown['line_items'][] = array(
                                'name'       => sanitize_text_field($item['name'] ?? ''),
                                'qty'        => intval($item['qty'] ?? 0),
                                'amount'     => floatval($item['amount'] ?? 0),
                                'free'       => !empty($item['free']),
                                'type'       => in_array($item['type'] ?? 'flat', array('daily', 'flat'), true) ? $item['type'] : 'flat',
                                'base_rate'  => floatval($item['base_rate'] ?? 0),
                                'extra_rate' => floatval($item['extra_rate'] ?? 0),
                            );
                        }
                    }
                } elseif (is_array($value)) {
                    $pricing_breakdown[$key] = array_map('sanitize_text_field', $value);
                } else {
                    $pricing_breakdown[$key] = sanitize_text_field($value);
                }
            }
            update_post_meta($post_id, '_crcm_pricing_breakdown', $pricing_breakdown);
        }
        
        // Save booking status
        if (isset($_POST['booking_status'])) {
            $new_status      = sanitize_text_field($_POST['booking_status']);
            $previous_status = get_post_meta($post_id, '_crcm_booking_status', true);
            $this->update_booking_status($post_id, $new_status);
            if (empty($previous_status) && 'pending' === $new_status) {
                do_action('crcm_booking_created', $post_id);
            }
        }
        
        // Save notes
        if (isset($_POST['booking_notes'])) {
            update_post_meta($post_id, '_crcm_booking_notes', sanitize_textarea_field($_POST['booking_notes']));
        }
        
        if (isset($_POST['internal_notes'])) {
            update_post_meta($post_id, '_crcm_booking_internal_notes', sanitize_textarea_field($_POST['internal_notes']));
        }
    }

    /**
     * Handle customer booking count when a booking is trashed.
     *
     * @param int $post_id Post ID being trashed.
     * @return void
     */
    public function handle_trash_booking($post_id) {
        if (get_post_type($post_id) !== 'crcm_booking') {
            return;
        }

        $customer_user_id = (int) get_post_meta($post_id, '_crcm_customer_user_id', true);
        if ($customer_user_id) {
            $total = (int) get_user_meta($customer_user_id, 'crcm_total_bookings', true);
            $total = max(0, $total - 1);
            update_user_meta($customer_user_id, 'crcm_total_bookings', $total);
        }
    }

    /**
     * Restore customer booking count when a booking is untrashed.
     *
     * @param int $post_id Post ID being restored.
     * @return void
     */
    public function handle_untrash_booking($post_id) {
        if (get_post_type($post_id) !== 'crcm_booking') {
            return;
        }

        $customer_user_id = (int) get_post_meta($post_id, '_crcm_customer_user_id', true);
        if ($customer_user_id) {
            $total = (int) get_user_meta($customer_user_id, 'crcm_total_bookings', true);
            update_user_meta($customer_user_id, 'crcm_total_bookings', $total + 1);
            update_user_meta($customer_user_id, 'crcm_last_booking_date', current_time('mysql'));
        }
    }
    
    /**
     * Assign default customer role to new users
     */
    public function assign_default_customer_role($user_id) {
        $user = new WP_User($user_id);
        
        // Only assign if user has no role (new registration)
        if (empty($user->roles)) {
            $user->set_role('crcm_customer');
        }
    }
    
    /**
     * Add role column to users table
     */
    public function add_user_role_column($columns) {
        $columns['crcm_role'] = __('Rental Role', 'custom-rental-manager');
        return $columns;
    }
    
    /**
     * Show role in users table
     */
    public function show_user_role_column($value, $column_name, $user_id) {
        if ($column_name === 'crcm_role') {
            $user = get_user_by('ID', $user_id);
            $roles = $user->roles;
            
            if (in_array('crcm_customer', $roles)) {
                return '<span class="crcm-role-badge customer">🙋‍♂️ Customer</span>';
            } elseif (in_array('crcm_manager', $roles)) {
                return '<span class="crcm-role-badge manager">👨‍💼 Manager</span>';
            } elseif (in_array('administrator', $roles)) {
                return '<span class="crcm-role-badge admin">👑 Admin</span>';
            }
            
            return '-';
        }
        
        return $value;
    }
    
    /**
     * Custom columns for booking list
     */
    public function booking_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['crcm_customer'] = __('Customer', 'custom-rental-manager');
        $new_columns['crcm_vehicle'] = __('Vehicle', 'custom-rental-manager');
        $new_columns['crcm_dates'] = __('Dates', 'custom-rental-manager');
        $new_columns['crcm_total'] = __('Total', 'custom-rental-manager');
        $new_columns['crcm_status'] = __('Status', 'custom-rental-manager');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Display custom column content
     */
    public function booking_column_content($column, $post_id) {
        $booking_data = get_post_meta($post_id, '_crcm_booking_data', true);
        $pricing_breakdown = get_post_meta($post_id, '_crcm_pricing_breakdown', true);
        $booking_status = get_post_meta($post_id, '_crcm_booking_status', true);
        
        switch ($column) {
            case 'crcm_customer':
                if (isset($booking_data['customer_id'])) {
                    $customer = get_user_by('ID', $booking_data['customer_id']);
                    if ($customer) {
                        echo '<strong>' . esc_html($customer->display_name) . '</strong><br>';
                        echo '<small>' . esc_html($customer->user_email) . '</small>';
                    }
                }
                break;
                
            case 'crcm_vehicle':
                if (isset($booking_data['vehicle_id'])) {
                    $vehicle = get_post($booking_data['vehicle_id']);
                    if ($vehicle) {
                        echo '<a href="' . get_edit_post_link($vehicle->ID) . '">';
                        echo esc_html($vehicle->post_title);
                        echo '</a>';
                    }
                }
                break;
                
            case 'crcm_dates':
                if (isset($booking_data['pickup_date'], $booking_data['return_date'])) {
                    $pickup = date('d/m/Y', strtotime($booking_data['pickup_date']));
                    $return = date('d/m/Y', strtotime($booking_data['return_date']));
                    echo '<strong>Ritiro:</strong> ' . $pickup . '<br>';
                    echo '<strong>Riconsegna:</strong> ' . $return;
                    
                    if (isset($booking_data['rental_days'])) {
                        echo '<br><small>' . $booking_data['rental_days'] . ' giorni</small>';
                    }
                }
                break;
                
            case 'crcm_total':
                if (isset($pricing_breakdown['final_total'])) {
                    echo '<strong>€' . number_format($pricing_breakdown['final_total'], 2) . '</strong>';
                }
                break;
                
            case 'crcm_status':
                $status_labels = array(
                    'pending' => 'In Attesa',
                    'confirmed' => 'Confermata',
                    'active' => 'In Corso',
                    'completed' => 'Completata',
                    'cancelled' => 'Cancellata',
                );
                
                $status = $booking_status ?: 'pending';
                $label = $status_labels[$status] ?? $status;
                
                echo '<span class="crcm-status-badge ' . esc_attr($status) . '">' . esc_html($label) . '</span>';
                break;
        }
    }
    
    
}

