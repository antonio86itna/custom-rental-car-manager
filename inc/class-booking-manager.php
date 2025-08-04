<?php
/**
 * Booking Manager Class - RESET EDITION
 * 
 * PERFETTO E FUNZIONANTE - Sistema di gestione prenotazioni
 * Completamente sincronizzato e dinamico
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CRCM_Booking_Manager {
    
    /**
     * Constructor - SAFE
     */
    public function __construct() {
        // ONLY hook save when needed
        add_action('save_post_crcm_booking', array($this, 'save_booking_data'), 10, 2);
        
        // AJAX hooks for dynamic functionality
        add_action('wp_ajax_crcm_get_vehicle_rate', array($this, 'ajax_get_vehicle_rate'));
        add_action('wp_ajax_crcm_calculate_total', array($this, 'ajax_calculate_total'));
        add_action('wp_ajax_crcm_check_availability', array($this, 'ajax_check_availability'));
    }
    
    /**
     * Add booking meta boxes - NO EARLY CAPABILITY CHECKS
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
            'crcm_rental_period',
            __('Rental Period', 'custom-rental-manager'),
            array($this, 'rental_period_meta_box'),
            'crcm_booking',
            'side',
            'high'
        );
        
        add_meta_box(
            'crcm_pricing_extras',
            __('Pricing & Extras', 'custom-rental-manager'),
            array($this, 'pricing_extras_meta_box'),
            'crcm_booking',
            'normal',
            'default'
        );
        
        add_meta_box(
            'crcm_booking_status',
            __('Booking Status', 'custom-rental-manager'),
            array($this, 'booking_status_meta_box'),
            'crcm_booking',
            'side',
            'high'
        );
        
        add_meta_box(
            'crcm_booking_notes',
            __('Notes & Documents', 'custom-rental-manager'),
            array($this, 'booking_notes_meta_box'),
            'crcm_booking',
            'normal',
            'default'
        );
    }
    
    /**
     * Booking details meta box - VEHICLE & BASIC INFO
     */
    public function booking_details_meta_box($post) {
        wp_nonce_field('crcm_booking_meta', 'crcm_booking_meta_nonce');
        
        // Get current values
        $vehicle_id = get_post_meta($post->ID, '_crcm_vehicle_id', true);
        $booking_reference = get_post_meta($post->ID, '_crcm_booking_reference', true);
        $pickup_location = get_post_meta($post->ID, '_crcm_pickup_location', true);
        $return_location = get_post_meta($post->ID, '_crcm_return_location', true);
        
        // Generate reference if empty
        if (empty($booking_reference)) {
            $booking_reference = 'CRCM-' . date('Y') . '-' . str_pad($post->ID, 4, '0', STR_PAD_LEFT);
        }
        
        // Get available vehicles
        $available_vehicles = get_posts(array(
            'post_type' => 'crcm_vehicle',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="booking_reference"><?php _e('Booking Reference', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="text" name="booking_reference" id="booking_reference" value="<?php echo esc_attr($booking_reference); ?>" class="regular-text" readonly style="background: #f0f0f0;">
                    <p class="description"><?php _e('Auto-generated booking reference', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="vehicle_id"><?php _e('Vehicle', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <select name="vehicle_id" id="vehicle_id" class="regular-text" required>
                        <option value=""><?php _e('Select Vehicle', 'custom-rental-manager'); ?></option>
                        <?php foreach ($available_vehicles as $vehicle): ?>
                            <?php
                            $status = get_post_meta($vehicle->ID, '_crcm_vehicle_status', true) ?: 'available';
                            $daily_rate = get_post_meta($vehicle->ID, '_crcm_daily_rate', true);
                            $vehicle_type = get_post_meta($vehicle->ID, '_crcm_vehicle_type', true);
                            $license_plate = get_post_meta($vehicle->ID, '_crcm_license_plate', true);
                            
                            $option_text = $vehicle->post_title;
                            if ($license_plate) {
                                $option_text .= ' (' . $license_plate . ')';
                            }
                            if ($daily_rate) {
                                $option_text .= ' - €' . number_format($daily_rate, 0) . '/day';
                            }
                            
                            $disabled = ($status !== 'available' && $vehicle->ID != $vehicle_id) ? 'disabled' : '';
                            $style = '';
                            if ($status === 'rented') $style = 'color: orange;';
                            elseif ($status === 'maintenance') $style = 'color: blue;';
                            elseif ($status === 'out_of_service') $style = 'color: red;';
                            ?>
                            <option value="<?php echo $vehicle->ID; ?>" <?php selected($vehicle_id, $vehicle->ID); ?> <?php echo $disabled; ?> style="<?php echo $style; ?>" data-rate="<?php echo $daily_rate; ?>" data-type="<?php echo $vehicle_type; ?>" data-status="<?php echo $status; ?>">
                                <?php echo esc_html($option_text); ?>
                                <?php if ($status !== 'available'): ?>
                                    - [<?php echo ucfirst($status); ?>]
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="vehicle-info" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px; display: none;">
                        <!-- Vehicle info will be populated by JavaScript -->
                    </div>
                </td>
            </tr>
            
            <tr>
                <th><label for="pickup_location"><?php _e('Pickup Location', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <select name="pickup_location" id="pickup_location" class="regular-text" required>
                        <option value=""><?php _e('Select Pickup Location', 'custom-rental-manager'); ?></option>
                        <option value="main_office" <?php selected($pickup_location, 'main_office'); ?>><?php _e('Main Office', 'custom-rental-manager'); ?></option>
                        <option value="secondary_office" <?php selected($pickup_location, 'secondary_office'); ?>><?php _e('Secondary Office', 'custom-rental-manager'); ?></option>
                        <option value="airport" <?php selected($pickup_location, 'airport'); ?>><?php _e('Airport', 'custom-rental-manager'); ?></option>
                        <option value="hotel_delivery" <?php selected($pickup_location, 'hotel_delivery'); ?>><?php _e('Hotel Delivery', 'custom-rental-manager'); ?></option>
                        <option value="home_delivery" <?php selected($pickup_location, 'home_delivery'); ?>><?php _e('Home Delivery', 'custom-rental-manager'); ?></option>
                        <option value="other" <?php selected($pickup_location, 'other'); ?>><?php _e('Other', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="return_location"><?php _e('Return Location', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <select name="return_location" id="return_location" class="regular-text" required>
                        <option value=""><?php _e('Select Return Location', 'custom-rental-manager'); ?></option>
                        <option value="main_office" <?php selected($return_location, 'main_office'); ?>><?php _e('Main Office', 'custom-rental-manager'); ?></option>
                        <option value="secondary_office" <?php selected($return_location, 'secondary_office'); ?>><?php _e('Secondary Office', 'custom-rental-manager'); ?></option>
                        <option value="airport" <?php selected($return_location, 'airport'); ?>><?php _e('Airport', 'custom-rental-manager'); ?></option>
                        <option value="hotel_pickup" <?php selected($return_location, 'hotel_pickup'); ?>><?php _e('Hotel Pickup', 'custom-rental-manager'); ?></option>
                        <option value="home_pickup" <?php selected($return_location, 'home_pickup'); ?>><?php _e('Home Pickup', 'custom-rental-manager'); ?></option>
                        <option value="other" <?php selected($return_location, 'other'); ?>><?php _e('Other', 'custom-rental-manager'); ?></option>
                    </select>
                    <br><br>
                    <label>
                        <input type="checkbox" id="same_location" <?php checked($pickup_location === $return_location && !empty($pickup_location)); ?>>
                        <?php _e('Same as pickup location', 'custom-rental-manager'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            // Vehicle selection handler
            $('#vehicle_id').on('change', function() {
                var vehicleId = $(this).val();
                var selectedOption = $(this).find('option:selected');
                
                if (vehicleId) {
                    var rate = selectedOption.data('rate');
                    var type = selectedOption.data('type');
                    var status = selectedOption.data('status');
                    
                    var infoHtml = '<h4><?php _e('Vehicle Information', 'custom-rental-manager'); ?></h4>';
                    infoHtml += '<p><strong><?php _e('Type:', 'custom-rental-manager'); ?></strong> ' + (type ? type.charAt(0).toUpperCase() + type.slice(1) : '-') + '</p>';
                    infoHtml += '<p><strong><?php _e('Daily Rate:', 'custom-rental-manager'); ?></strong> €' + (rate || 0) + '</p>';
                    infoHtml += '<p><strong><?php _e('Status:', 'custom-rental-manager'); ?></strong> <span style="color: ' + (status === 'available' ? 'green' : status === 'rented' ? 'orange' : status === 'maintenance' ? 'blue' : 'red') + ';">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span></p>';
                    
                    $('#vehicle-info').html(infoHtml).show();
                    
                    // Update pricing calculation
                    updateTotalPrice();
                } else {
                    $('#vehicle-info').hide();
                }
            });
            
            // Same location checkbox
            $('#same_location').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#return_location').val($('#pickup_location').val());
                }
            });
            
            $('#pickup_location').on('change', function() {
                if ($('#same_location').is(':checked')) {
                    $('#return_location').val($(this).val());
                }
            });
            
            // Auto-generate booking reference
            if (!$('#booking_reference').val() || $('#booking_reference').val().indexOf('Auto Draft') > -1) {
                var year = new Date().getFullYear();
                var postId = <?php echo $post->ID; ?>;
                var reference = 'CRCM-' + year + '-' + String(postId).padStart(4, '0');
                $('#booking_reference').val(reference);
            }
            
            // Initialize
            $('#vehicle_id').trigger('change');
        });
        </script>
        <?php
    }
    
    /**
     * Customer information meta box - CUSTOMER DETAILS
     */
    public function customer_info_meta_box($post) {
        $customer_name = get_post_meta($post->ID, '_crcm_customer_name', true);
        $customer_email = get_post_meta($post->ID, '_crcm_customer_email', true);
        $customer_phone = get_post_meta($post->ID, '_crcm_customer_phone', true);
        $customer_address = get_post_meta($post->ID, '_crcm_customer_address', true);
        $customer_city = get_post_meta($post->ID, '_crcm_customer_city', true);
        $customer_country = get_post_meta($post->ID, '_crcm_customer_country', true);
        $customer_license = get_post_meta($post->ID, '_crcm_customer_license', true);
        $customer_license_expires = get_post_meta($post->ID, '_crcm_customer_license_expires', true);
        $customer_age = get_post_meta($post->ID, '_crcm_customer_age', true);
        $emergency_contact = get_post_meta($post->ID, '_crcm_emergency_contact', true);
        $emergency_phone = get_post_meta($post->ID, '_crcm_emergency_phone', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="customer_name"><?php _e('Full Name', 'custom-rental-manager'); ?> *</label></th>
                <td><input type="text" name="customer_name" id="customer_name" value="<?php echo esc_attr($customer_name); ?>" class="regular-text" required placeholder="First Name Last Name"></td>
            </tr>
            
            <tr>
                <th><label for="customer_email"><?php _e('Email Address', 'custom-rental-manager'); ?> *</label></th>
                <td><input type="email" name="customer_email" id="customer_email" value="<?php echo esc_attr($customer_email); ?>" class="regular-text" required placeholder="customer@example.com"></td>
            </tr>
            
            <tr>
                <th><label for="customer_phone"><?php _e('Phone Number', 'custom-rental-manager'); ?> *</label></th>
                <td><input type="tel" name="customer_phone" id="customer_phone" value="<?php echo esc_attr($customer_phone); ?>" class="regular-text" required placeholder="+39 123 456 7890"></td>
            </tr>
            
            <tr>
                <th><label for="customer_age"><?php _e('Age', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" name="customer_age" id="customer_age" value="<?php echo esc_attr($customer_age); ?>" min="18" max="100" class="regular-text" placeholder="25">
                    <p class="description" id="age-warning"></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="customer_address"><?php _e('Address', 'custom-rental-manager'); ?></label></th>
                <td><input type="text" name="customer_address" id="customer_address" value="<?php echo esc_attr($customer_address); ?>" class="large-text" placeholder="Street, Number"></td>
            </tr>
            
            <tr>
                <th><label for="customer_city"><?php _e('City', 'custom-rental-manager'); ?></label></th>
                <td><input type="text" name="customer_city" id="customer_city" value="<?php echo esc_attr($customer_city); ?>" class="regular-text" placeholder="City Name"></td>
            </tr>
            
            <tr>
                <th><label for="customer_country"><?php _e('Country', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select name="customer_country" id="customer_country" class="regular-text">
                        <option value=""><?php _e('Select Country', 'custom-rental-manager'); ?></option>
                        <option value="IT" <?php selected($customer_country, 'IT'); ?>><?php _e('Italy', 'custom-rental-manager'); ?></option>
                        <option value="FR" <?php selected($customer_country, 'FR'); ?>><?php _e('France', 'custom-rental-manager'); ?></option>
                        <option value="DE" <?php selected($customer_country, 'DE'); ?>><?php _e('Germany', 'custom-rental-manager'); ?></option>
                        <option value="ES" <?php selected($customer_country, 'ES'); ?>><?php _e('Spain', 'custom-rental-manager'); ?></option>
                        <option value="UK" <?php selected($customer_country, 'UK'); ?>><?php _e('United Kingdom', 'custom-rental-manager'); ?></option>
                        <option value="US" <?php selected($customer_country, 'US'); ?>><?php _e('United States', 'custom-rental-manager'); ?></option>
                        <option value="OTHER" <?php selected($customer_country, 'OTHER'); ?>><?php _e('Other', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="customer_license"><?php _e('Driver License Number', 'custom-rental-manager'); ?> *</label></th>
                <td><input type="text" name="customer_license" id="customer_license" value="<?php echo esc_attr($customer_license); ?>" class="regular-text" required placeholder="License number"></td>
            </tr>
            
            <tr>
                <th><label for="customer_license_expires"><?php _e('License Expires', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="date" name="customer_license_expires" id="customer_license_expires" value="<?php echo esc_attr($customer_license_expires); ?>" class="regular-text">
                    <p class="description" id="license-status"></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="emergency_contact"><?php _e('Emergency Contact', 'custom-rental-manager'); ?></label></th>
                <td><input type="text" name="emergency_contact" id="emergency_contact" value="<?php echo esc_attr($emergency_contact); ?>" class="regular-text" placeholder="Contact person name"></td>
            </tr>
            
            <tr>
                <th><label for="emergency_phone"><?php _e('Emergency Phone', 'custom-rental-manager'); ?></label></th>
                <td><input type="tel" name="emergency_phone" id="emergency_phone" value="<?php echo esc_attr($emergency_phone); ?>" class="regular-text" placeholder="+39 123 456 7890"></td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            // Auto-generate booking title from customer name
            $('#customer_name').on('input', function() {
                var customerName = $(this).val();
                var vehicleSelect = $('#vehicle_id option:selected').text();
                
                if (customerName && vehicleSelect && vehicleSelect !== '<?php _e('Select Vehicle', 'custom-rental-manager'); ?>') {
                    var currentTitle = $('#title').val();
                    if (!currentTitle || currentTitle === 'Auto Draft' || currentTitle.indexOf('Booking') === 0) {
                        var title = 'Booking: ' + customerName;
                        $('#title').val(title);
                    }
                }
            });
            
            // Check customer age against vehicle requirements
            $('#customer_age').on('input', function() {
                var age = parseInt($(this).val());
                var warningEl = $('#age-warning');
                
                if (age < 18) {
                    warningEl.html('<strong style="color: red;">⚠️ <?php _e('Customer must be at least 18 years old', 'custom-rental-manager'); ?></strong>');
                } else if (age < 21) {
                    warningEl.html('<strong style="color: orange;">⚠️ <?php _e('Some vehicles may require minimum age 21', 'custom-rental-manager'); ?></strong>');
                } else if (age < 25) {
                    warningEl.html('<strong style="color: orange;">⚠️ <?php _e('Some luxury vehicles may require minimum age 25', 'custom-rental-manager'); ?></strong>');
                } else {
                    warningEl.html('<span style="color: green;">✓ <?php _e('Age requirement met', 'custom-rental-manager'); ?></span>');
                }
                
                // Check against selected vehicle's minimum age
                checkVehicleAgeRequirement();
            });
            
            function checkVehicleAgeRequirement() {
                var vehicleId = $('#vehicle_id').val();
                var customerAge = parseInt($('#customer_age').val());
                
                if (vehicleId && customerAge) {
                    // This would typically make an AJAX call to check vehicle requirements
                    // For now, we'll do a simple check based on vehicle type
                    var vehicleType = $('#vehicle_id option:selected').data('type');
                    var minimumAge = 18;
                    
                    if (vehicleType === 'luxury' || vehicleType === 'sports') {
                        minimumAge = 25;
                    } else if (vehicleType === 'suv' || vehicleType === 'fullsize') {
                        minimumAge = 21;
                    }
                    
                    if (customerAge < minimumAge) {
                        $('#age-warning').html('<strong style="color: red;">⚠️ <?php _e('Selected vehicle requires minimum age', 'custom-rental-manager'); ?> ' + minimumAge + '</strong>');
                        return false;
                    }
                }
                return true;
            }
            
            // Check license expiry
            $('#customer_license_expires').on('change', function() {
                var expiryDate = new Date($(this).val());
                var today = new Date();
                var warningDate = new Date();
                warningDate.setDate(today.getDate() + 30); // 30 days warning
                
                var statusEl = $('#license-status');
                
                if (expiryDate < today) {
                    statusEl.html('<strong style="color: red;">⚠️ <?php _e('License has expired', 'custom-rental-manager'); ?></strong>');
                    $(this).css('border-color', '#d63638');
                } else if (expiryDate < warningDate) {
                    var daysLeft = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
                    statusEl.html('<strong style="color: orange;">⚠️ <?php _e('License expires in', 'custom-rental-manager'); ?> ' + daysLeft + ' <?php _e('days', 'custom-rental-manager'); ?></strong>');
                    $(this).css('border-color', '#dba617');
                } else {
                    statusEl.html('<span style="color: green;">✓ <?php _e('License valid', 'custom-rental-manager'); ?></span>');
                    $(this).css('border-color', '');
                }
            });
            
            // Phone number formatting
            $('#customer_phone, #emergency_phone').on('input', function() {
                var phone = $(this).val().replace(/\D/g, ''); // Remove non-digits
                if (phone.length > 0 && !phone.startsWith('39') && !phone.startsWith('+')) {
                    // Add Italian country code if not international
                    if (phone.length <= 10) {
                        $(this).val('+39 ' + phone);
                    }
                }
            });
            
            // Initialize checks
            $('#customer_age').trigger('input');
            $('#customer_license_expires').trigger('change');
        });
        </script>
        <?php
    }
    
    /**
     * Rental period meta box - DATES & DURATION
     */
    public function rental_period_meta_box($post) {
        $pickup_date = get_post_meta($post->ID, '_crcm_pickup_date', true);
        $pickup_time = get_post_meta($post->ID, '_crcm_pickup_time', true) ?: '09:00';
        $return_date = get_post_meta($post->ID, '_crcm_return_date', true);
        $return_time = get_post_meta($post->ID, '_crcm_return_time', true) ?: '18:00';
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="pickup_date"><?php _e('Pickup Date', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <input type="date" name="pickup_date" id="pickup_date" value="<?php echo esc_attr($pickup_date); ?>" class="regular-text" required min="<?php echo date('Y-m-d'); ?>">
                </td>
            </tr>
            
            <tr>
                <th><label for="pickup_time"><?php _e('Pickup Time', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select name="pickup_time" id="pickup_time" class="regular-text">
                        <option value="08:00" <?php selected($pickup_time, '08:00'); ?>>08:00</option>
                        <option value="08:30" <?php selected($pickup_time, '08:30'); ?>>08:30</option>
                        <option value="09:00" <?php selected($pickup_time, '09:00'); ?>>09:00</option>
                        <option value="09:30" <?php selected($pickup_time, '09:30'); ?>>09:30</option>
                        <option value="10:00" <?php selected($pickup_time, '10:00'); ?>>10:00</option>
                        <option value="10:30" <?php selected($pickup_time, '10:30'); ?>>10:30</option>
                        <option value="11:00" <?php selected($pickup_time, '11:00'); ?>>11:00</option>
                        <option value="11:30" <?php selected($pickup_time, '11:30'); ?>>11:30</option>
                        <option value="12:00" <?php selected($pickup_time, '12:00'); ?>>12:00</option>
                        <option value="12:30" <?php selected($pickup_time, '12:30'); ?>>12:30</option>
                        <option value="13:00" <?php selected($pickup_time, '13:00'); ?>>13:00</option>
                        <option value="13:30" <?php selected($pickup_time, '13:30'); ?>>13:30</option>
                        <option value="14:00" <?php selected($pickup_time, '14:00'); ?>>14:00</option>
                        <option value="14:30" <?php selected($pickup_time, '14:30'); ?>>14:30</option>
                        <option value="15:00" <?php selected($pickup_time, '15:00'); ?>>15:00</option>
                        <option value="15:30" <?php selected($pickup_time, '15:30'); ?>>15:30</option>
                        <option value="16:00" <?php selected($pickup_time, '16:00'); ?>>16:00</option>
                        <option value="16:30" <?php selected($pickup_time, '16:30'); ?>>16:30</option>
                        <option value="17:00" <?php selected($pickup_time, '17:00'); ?>>17:00</option>
                        <option value="17:30" <?php selected($pickup_time, '17:30'); ?>>17:30</option>
                        <option value="18:00" <?php selected($pickup_time, '18:00'); ?>>18:00</option>
                        <option value="18:30" <?php selected($pickup_time, '18:30'); ?>>18:30</option>
                        <option value="19:00" <?php selected($pickup_time, '19:00'); ?>>19:00</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="return_date"><?php _e('Return Date', 'custom-rental-manager'); ?> *</label></th>
                <td>
                    <input type="date" name="return_date" id="return_date" value="<?php echo esc_attr($return_date); ?>" class="regular-text" required min="<?php echo date('Y-m-d'); ?>">
                </td>
            </tr>
            
            <tr>
                <th><label for="return_time"><?php _e('Return Time', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select name="return_time" id="return_time" class="regular-text">
                        <option value="08:00" <?php selected($return_time, '08:00'); ?>>08:00</option>
                        <option value="08:30" <?php selected($return_time, '08:30'); ?>>08:30</option>
                        <option value="09:00" <?php selected($return_time, '09:00'); ?>>09:00</option>
                        <option value="09:30" <?php selected($return_time, '09:30'); ?>>09:30</option>
                        <option value="10:00" <?php selected($return_time, '10:00'); ?>>10:00</option>
                        <option value="10:30" <?php selected($return_time, '10:30'); ?>>10:30</option>
                        <option value="11:00" <?php selected($return_time, '11:00'); ?>>11:00</option>
                        <option value="11:30" <?php selected($return_time, '11:30'); ?>>11:30</option>
                        <option value="12:00" <?php selected($return_time, '12:00'); ?>>12:00</option>
                        <option value="12:30" <?php selected($return_time, '12:30'); ?>>12:30</option>
                        <option value="13:00" <?php selected($return_time, '13:00'); ?>>13:00</option>
                        <option value="13:30" <?php selected($return_time, '13:30'); ?>>13:30</option>
                        <option value="14:00" <?php selected($return_time, '14:00'); ?>>14:00</option>
                        <option value="14:30" <?php selected($return_time, '14:30'); ?>>14:30</option>
                        <option value="15:00" <?php selected($return_time, '15:00'); ?>>15:00</option>
                        <option value="15:30" <?php selected($return_time, '15:30'); ?>>15:30</option>
                        <option value="16:00" <?php selected($return_time, '16:00'); ?>>16:00</option>
                        <option value="16:30" <?php selected($return_time, '16:30'); ?>>16:30</option>
                        <option value="17:00" <?php selected($return_time, '17:00'); ?>>17:00</option>
                        <option value="17:30" <?php selected($return_time, '17:30'); ?>>17:30</option>
                        <option value="18:00" <?php selected($return_time, '18:00'); ?>>18:00</option>
                        <option value="18:30" <?php selected($return_time, '18:30'); ?>>18:30</option>
                        <option value="19:00" <?php selected($return_time, '19:00'); ?>>19:00</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <div class="rental-period-summary">
            <h4><?php _e('Rental Period Summary', 'custom-rental-manager'); ?></h4>
            <div id="period-info">
                <p id="rental-duration"><?php _e('Duration will be calculated automatically', 'custom-rental-manager'); ?></p>
                <div id="availability-check" style="margin-top: 10px;">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            function calculateRentalPeriod() {
                var pickupDate = $('#pickup_date').val();
                var returnDate = $('#return_date').val();
                
                if (pickupDate && returnDate) {
                    var pickup = new Date(pickupDate);
                    var returnD = new Date(returnDate);
                    
                    if (returnD < pickup) {
                        $('#rental-duration').html('<strong style="color: red;">⚠️ <?php _e('Return date must be after pickup date', 'custom-rental-manager'); ?></strong>');
                        return;
                    }
                    
                    var timeDiff = returnD.getTime() - pickup.getTime();
                    var dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    
                    var durationText = '<strong><?php _e('Duration:', 'custom-rental-manager'); ?></strong> ' + dayDiff + ' <?php _e('day(s)', 'custom-rental-manager'); ?>';
                    
                    // Add period type
                    if (dayDiff >= 30) {
                        var months = Math.floor(dayDiff / 30);
                        durationText += ' (' + months + ' <?php _e('month(s)', 'custom-rental-manager'); ?>)';
                        durationText += '<br><span style="color: green;">✓ <?php _e('Monthly rate applicable', 'custom-rental-manager'); ?></span>';
                    } else if (dayDiff >= 7) {
                        var weeks = Math.floor(dayDiff / 7);
                        durationText += ' (' + weeks + ' <?php _e('week(s)', 'custom-rental-manager'); ?>)';
                        durationText += '<br><span style="color: green;">✓ <?php _e('Weekly rate applicable', 'custom-rental-manager'); ?></span>';
                    } else {
                        durationText += '<br><span><?php _e('Daily rate', 'custom-rental-manager'); ?></span>';
                    }
                    
                    $('#rental-duration').html(durationText);
                    
                    // Update pricing
                    updateTotalPrice();
                    
                    // Check availability
                    checkVehicleAvailability();
                }
            }
            
            function checkVehicleAvailability() {
                var vehicleId = $('#vehicle_id').val();
                var pickupDate = $('#pickup_date').val();
                var returnDate = $('#return_date').val();
                
                if (vehicleId && pickupDate && returnDate) {
                    $('#availability-check').html('<p><?php _e('Checking availability...', 'custom-rental-manager'); ?></p>');
                    
                    // AJAX call to check availability
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'crcm_check_availability',
                            vehicle_id: vehicleId,
                            pickup_date: pickupDate,
                            return_date: returnDate,
                            booking_id: <?php echo $post->ID; ?>,
                            nonce: '<?php echo wp_create_nonce('crcm_ajax'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                if (response.data.available) {
                                    $('#availability-check').html('<p style="color: green;">✓ <?php _e('Vehicle is available for selected period', 'custom-rental-manager'); ?></p>');
                                } else {
                                    var conflicts = response.data.conflicts || [];
                                    var conflictHtml = '<p style="color: red;">⚠️ <?php _e('Vehicle has booking conflicts:', 'custom-rental-manager'); ?></p><ul>';
                                    conflicts.forEach(function(conflict) {
                                        conflictHtml += '<li>' + conflict.customer + ' (' + conflict.pickup + ' - ' + conflict.return + ')</li>';
                                    });
                                    conflictHtml += '</ul>';
                                    $('#availability-check').html(conflictHtml);
                                }
                            }
                        },
                        error: function() {
                            $('#availability-check').html('<p style="color: orange;">⚠️ <?php _e('Could not check availability', 'custom-rental-manager'); ?></p>');
                        }
                    });
                }
            }
            
            // Date change handlers
            $('#pickup_date, #return_date').on('change', calculateRentalPeriod);
            
            // Auto-set return date (default 3 days)
            $('#pickup_date').on('change', function() {
                var pickupDate = new Date($(this).val());
                if (!$('#return_date').val() && pickupDate) {
                    pickupDate.setDate(pickupDate.getDate() + 3);
                    $('#return_date').val(pickupDate.toISOString().split('T')[0]);
                }
            });
            
            // Initialize
            calculateRentalPeriod();
        });
        
        // Global function for price updates
        function updateTotalPrice() {
            // This will be implemented in the pricing section
        }
        </script>
        
        <style>
        .rental-period-summary {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        </style>
        <?php
    }
    
    /**
     * Pricing and extras meta box - COMPREHENSIVE PRICING
     */
    public function pricing_extras_meta_box($post) {
        $base_rate = get_post_meta($post->ID, '_crcm_base_rate', true);
        $total_days = get_post_meta($post->ID, '_crcm_total_days', true);
        $selected_extras = get_post_meta($post->ID, '_crcm_selected_extras', true) ?: array();
        $insurance_type = get_post_meta($post->ID, '_crcm_insurance_type', true);
        $additional_driver = get_post_meta($post->ID, '_crcm_additional_driver', true);
        $delivery_fee = get_post_meta($post->ID, '_crcm_delivery_fee', true);
        $discount_amount = get_post_meta($post->ID, '_crcm_discount_amount', true);
        $discount_reason = get_post_meta($post->ID, '_crcm_discount_reason', true);
        $total_amount = get_post_meta($post->ID, '_crcm_total_amount', true);
        
        // Available extras with prices
        $available_extras = array(
            'gps' => array('name' => __('GPS Navigation', 'custom-rental-manager'), 'price' => 5),
            'child_seat' => array('name' => __('Child Seat', 'custom-rental-manager'), 'price' => 8),
            'additional_driver' => array('name' => __('Additional Driver', 'custom-rental-manager'), 'price' => 10),
            'roof_box' => array('name' => __('Roof Box', 'custom-rental-manager'), 'price' => 12),
            'ski_rack' => array('name' => __('Ski Rack', 'custom-rental-manager'), 'price' => 10),
            'bike_rack' => array('name' => __('Bike Rack', 'custom-rental-manager'), 'price' => 10),
            'wifi_hotspot' => array('name' => __('WiFi Hotspot', 'custom-rental-manager'), 'price' => 7),
            'phone_holder' => array('name' => __('Phone Holder', 'custom-rental-manager'), 'price' => 3),
            'usb_charger' => array('name' => __('USB Charger', 'custom-rental-manager'), 'price' => 2),
            'first_aid_kit' => array('name' => __('First Aid Kit', 'custom-rental-manager'), 'price' => 5),
        );
        
        ?>
        <div class="pricing-container">
            <div class="pricing-left">
                <h4><?php _e('Base Pricing', 'custom-rental-manager'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><label for="base_rate"><?php _e('Daily Rate (€)', 'custom-rental-manager'); ?></label></th>
                        <td>
                            <input type="number" name="base_rate" id="base_rate" value="<?php echo esc_attr($base_rate); ?>" step="0.01" min="0" class="regular-text" readonly style="background: #f0f0f0;">
                            <p class="description"><?php _e('Automatically set from selected vehicle', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="total_days"><?php _e('Total Days', 'custom-rental-manager'); ?></label></th>
                        <td>
                            <input type="number" name="total_days" id="total_days" value="<?php echo esc_attr($total_days); ?>" min="1" class="regular-text" readonly style="background: #f0f0f0;">
                            <p class="description"><?php _e('Automatically calculated from dates', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h4><?php _e('Insurance & Protection', 'custom-rental-manager'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><label for="insurance_type"><?php _e('Insurance Type', 'custom-rental-manager'); ?></label></th>
                        <td>
                            <select name="insurance_type" id="insurance_type" class="regular-text">
                                <option value=""><?php _e('No Additional Insurance', 'custom-rental-manager'); ?></option>
                                <option value="basic" <?php selected($insurance_type, 'basic'); ?> data-price="10"><?php _e('Basic Protection (+€10/day)', 'custom-rental-manager'); ?></option>
                                <option value="comprehensive" <?php selected($insurance_type, 'comprehensive'); ?> data-price="20"><?php _e('Comprehensive Protection (+€20/day)', 'custom-rental-manager'); ?></option>
                                <option value="premium" <?php selected($insurance_type, 'premium'); ?> data-price="30"><?php _e('Premium Protection (+€30/day)', 'custom-rental-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h4><?php _e('Additional Services', 'custom-rental-manager'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><label for="delivery_fee"><?php _e('Delivery Fee (€)', 'custom-rental-manager'); ?></label></th>
                        <td>
                            <input type="number" name="delivery_fee" id="delivery_fee" value="<?php echo esc_attr($delivery_fee); ?>" step="0.01" min="0" class="regular-text">
                            <p class="description"><?php _e('One-time delivery/pickup fee', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="discount_amount"><?php _e('Discount (€)', 'custom-rental-manager'); ?></label></th>
                        <td>
                            <input type="number" name="discount_amount" id="discount_amount" value="<?php echo esc_attr($discount_amount); ?>" step="0.01" min="0" class="regular-text">
                            <input type="text" name="discount_reason" id="discount_reason" value="<?php echo esc_attr($discount_reason); ?>" placeholder="<?php _e('Discount reason', 'custom-rental-manager'); ?>" class="regular-text" style="margin-left: 10px;">
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="pricing-right">
                <h4><?php _e('Extras & Add-ons', 'custom-rental-manager'); ?></h4>
                <div class="extras-list">
                    <?php foreach ($available_extras as $key => $extra): ?>
                        <label class="extra-item">
                            <input type="checkbox" name="selected_extras[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $selected_extras)); ?> data-price="<?php echo $extra['price']; ?>">
                            <span class="extra-name"><?php echo $extra['name']; ?></span>
                            <span class="extra-price">+€<?php echo number_format($extra['price'], 0); ?>/day</span>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="pricing-summary">
                    <h4><?php _e('Pricing Breakdown', 'custom-rental-manager'); ?></h4>
                    <table id="pricing-breakdown-table">
                        <tr>
                            <td><?php _e('Base Rate', 'custom-rental-manager'); ?></td>
                            <td id="breakdown-base">€0.00</td>
                        </tr>
                        <tr id="breakdown-insurance-row" style="display: none;">
                            <td><?php _e('Insurance', 'custom-rental-manager'); ?></td>
                            <td id="breakdown-insurance">€0.00</td>
                        </tr>
                        <tr id="breakdown-extras-row" style="display: none;">
                            <td><?php _e('Extras', 'custom-rental-manager'); ?></td>
                            <td id="breakdown-extras">€0.00</td>
                        </tr>
                        <tr id="breakdown-delivery-row" style="display: none;">
                            <td><?php _e('Delivery Fee', 'custom-rental-manager'); ?></td>
                            <td id="breakdown-delivery">€0.00</td>
                        </tr>
                        <tr id="breakdown-discount-row" style="display: none;">
                            <td><?php _e('Discount', 'custom-rental-manager'); ?></td>
                            <td id="breakdown-discount">-€0.00</td>
                        </tr>
                        <tr class="total-row">
                            <td><strong><?php _e('Total Amount', 'custom-rental-manager'); ?></strong></td>
                            <td><strong id="breakdown-total" class="pricing-total">€0.00</strong></td>
                        </tr>
                    </table>
                    
                    <input type="hidden" name="total_amount" id="total_amount" value="<?php echo esc_attr($total_amount); ?>">
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Global function for price calculation
            window.updateTotalPrice = function() {
                var baseRate = parseFloat($('#base_rate').val()) || 0;
                var totalDays = parseInt($('#total_days').val()) || 1;
                var insurancePrice = 0;
                var extrasPrice = 0;
                var deliveryFee = parseFloat($('#delivery_fee').val()) || 0;
                var discountAmount = parseFloat($('#discount_amount').val()) || 0;
                
                // Calculate base cost
                var baseCost = baseRate * totalDays;
                
                // Calculate insurance cost
                var selectedInsurance = $('#insurance_type').find('option:selected');
                if (selectedInsurance.length > 0) {
                    insurancePrice = (parseFloat(selectedInsurance.data('price')) || 0) * totalDays;
                }
                
                // Calculate extras cost
                $('input[name="selected_extras[]"]:checked').each(function() {
                    extrasPrice += (parseFloat($(this).data('price')) || 0) * totalDays;
                });
                
                // Calculate total
                var total = baseCost + insurancePrice + extrasPrice + deliveryFee - discountAmount;
                
                // Update breakdown table
                $('#breakdown-base').text('€' + baseCost.toFixed(2));
                
                if (insurancePrice > 0) {
                    $('#breakdown-insurance').text('€' + insurancePrice.toFixed(2));
                    $('#breakdown-insurance-row').show();
                } else {
                    $('#breakdown-insurance-row').hide();
                }
                
                if (extrasPrice > 0) {
                    $('#breakdown-extras').text('€' + extrasPrice.toFixed(2));
                    $('#breakdown-extras-row').show();
                } else {
                    $('#breakdown-extras-row').hide();
                }
                
                if (deliveryFee > 0) {
                    $('#breakdown-delivery').text('€' + deliveryFee.toFixed(2));
                    $('#breakdown-delivery-row').show();
                } else {
                    $('#breakdown-delivery-row').hide();
                }
                
                if (discountAmount > 0) {
                    $('#breakdown-discount').text('-€' + discountAmount.toFixed(2));
                    $('#breakdown-discount-row').show();
                } else {
                    $('#breakdown-discount-row').hide();
                }
                
                $('#breakdown-total').text('€' + total.toFixed(2));
                $('#total_amount').val(total.toFixed(2));
            };
            
            // Bind change events
            $('#insurance_type, input[name="selected_extras[]"], #delivery_fee, #discount_amount').on('change input', updateTotalPrice);
            
            // Update base rate when vehicle changes
            $('#vehicle_id').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var rate = selectedOption.data('rate') || 0;
                $('#base_rate').val(rate);
                updateTotalPrice();
            });
            
            // Update days when dates change
            function updateRentalDays() {
                var pickupDate = new Date($('#pickup_date').val());
                var returnDate = new Date($('#return_date').val());
                
                if (pickupDate && returnDate && returnDate >= pickupDate) {
                    var timeDiff = returnDate.getTime() - pickupDate.getTime();
                    var dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    $('#total_days').val(dayDiff);
                    updateTotalPrice();
                }
            }
            
            $('#pickup_date, #return_date').on('change', updateRentalDays);
            
            // Auto-set delivery fee based on location
            $('#pickup_location, #return_location').on('change', function() {
                var pickupLoc = $('#pickup_location').val();
                var returnLoc = $('#return_location').val();
                var currentDeliveryFee = parseFloat($('#delivery_fee').val()) || 0;
                
                if (currentDeliveryFee === 0) { // Only auto-set if not manually set
                    var fee = 0;
                    
                    if (pickupLoc === 'home_delivery' || pickupLoc === 'hotel_delivery' || 
                        returnLoc === 'home_pickup' || returnLoc === 'hotel_pickup') {
                        fee = 25; // Delivery/pickup fee
                    } else if (pickupLoc === 'airport' || returnLoc === 'airport') {
                        fee = 15; // Airport fee
                    }
                    
                    if (fee > 0) {
                        $('#delivery_fee').val(fee);
                        updateTotalPrice();
                    }
                }
            });
            
            // Initialize
            updateRentalDays();
            updateTotalPrice();
        });
        </script>
        
        <style>
        .pricing-container {
            display: flex;
            gap: 20px;
        }
        
        .pricing-left {
            flex: 2;
        }
        
        .pricing-right {
            flex: 1;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
        }
        
        .extras-list {
            margin-bottom: 20px;
        }
        
        .extra-item {
            display: block;
            margin: 8px 0;
            padding: 8px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .extra-item:hover {
            background: #f0f0f0;
        }
        
        .extra-item input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .extra-price {
            float: right;
            color: #666;
            font-weight: bold;
        }
        
        .pricing-summary {
            background: #fff;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        #pricing-breakdown-table {
            width: 100%;
            margin-top: 10px;
        }
        
        #pricing-breakdown-table td {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .total-row td {
            border-top: 2px solid #ddd;
            border-bottom: none;
            padding-top: 10px;
            font-size: 1.1em;
        }
        
        .pricing-total {
            color: #0073aa;
            font-size: 1.3em !important;
        }
        </style>
        <?php
    }
    
    /**
     * Booking status meta box - STATUS & WORKFLOW
     */
    public function booking_status_meta_box($post) {
        $booking_status = get_post_meta($post->ID, '_crcm_booking_status', true) ?: 'pending';
        $payment_status = get_post_meta($post->ID, '_crcm_payment_status', true) ?: 'pending';
        $payment_method = get_post_meta($post->ID, '_crcm_payment_method', true);
        $deposit_amount = get_post_meta($post->ID, '_crcm_deposit_amount', true);
        $created_date = get_the_date('Y-m-d H:i:s', $post->ID);
        $last_modified = get_the_modified_date('Y-m-d H:i:s', $post->ID);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="booking_status"><?php _e('Booking Status', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select name="booking_status" id="booking_status" class="regular-text">
                        <option value="pending" <?php selected($booking_status, 'pending'); ?> style="color: orange;"><?php _e('Pending', 'custom-rental-manager'); ?></option>
                        <option value="confirmed" <?php selected($booking_status, 'confirmed'); ?> style="color: blue;"><?php _e('Confirmed', 'custom-rental-manager'); ?></option>
                        <option value="active" <?php selected($booking_status, 'active'); ?> style="color: green;"><?php _e('Active/Ongoing', 'custom-rental-manager'); ?></option>
                        <option value="completed" <?php selected($booking_status, 'completed'); ?> style="color: gray;"><?php _e('Completed', 'custom-rental-manager'); ?></option>
                        <option value="cancelled" <?php selected($booking_status, 'cancelled'); ?> style="color: red;"><?php _e('Cancelled', 'custom-rental-manager'); ?></option>
                    </select>
                    <p class="description" id="status-description"></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="payment_status"><?php _e('Payment Status', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select name="payment_status" id="payment_status" class="regular-text">
                        <option value="pending" <?php selected($payment_status, 'pending'); ?> style="color: orange;"><?php _e('Pending', 'custom-rental-manager'); ?></option>
                        <option value="deposit_paid" <?php selected($payment_status, 'deposit_paid'); ?> style="color: blue;"><?php _e('Deposit Paid', 'custom-rental-manager'); ?></option>
                        <option value="paid" <?php selected($payment_status, 'paid'); ?> style="color: green;"><?php _e('Fully Paid', 'custom-rental-manager'); ?></option>
                        <option value="refunded" <?php selected($payment_status, 'refunded'); ?> style="color: gray;"><?php _e('Refunded', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="payment_method"><?php _e('Payment Method', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select name="payment_method" id="payment_method" class="regular-text">
                        <option value=""><?php _e('Not Selected', 'custom-rental-manager'); ?></option>
                        <option value="cash" <?php selected($payment_method, 'cash'); ?>><?php _e('Cash', 'custom-rental-manager'); ?></option>
                        <option value="credit_card" <?php selected($payment_method, 'credit_card'); ?>><?php _e('Credit Card', 'custom-rental-manager'); ?></option>
                        <option value="debit_card" <?php selected($payment_method, 'debit_card'); ?>><?php _e('Debit Card', 'custom-rental-manager'); ?></option>
                        <option value="bank_transfer" <?php selected($payment_method, 'bank_transfer'); ?>><?php _e('Bank Transfer', 'custom-rental-manager'); ?></option>
                        <option value="paypal" <?php selected($payment_method, 'paypal'); ?>><?php _e('PayPal', 'custom-rental-manager'); ?></option>
                        <option value="other" <?php selected($payment_method, 'other'); ?>><?php _e('Other', 'custom-rental-manager'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="deposit_amount"><?php _e('Deposit Amount (€)', 'custom-rental-manager'); ?></label></th>
                <td>
                    <input type="number" name="deposit_amount" id="deposit_amount" value="<?php echo esc_attr($deposit_amount); ?>" step="0.01" min="0" class="regular-text">
                    <p class="description"><?php _e('Amount paid as deposit', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
        </table>
        
        <div class="booking-timeline">
            <h4><?php _e('Booking Timeline', 'custom-rental-manager'); ?></h4>
            <div class="timeline-info">
                <p><strong><?php _e('Created:', 'custom-rental-manager'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($created_date)); ?></p>
                <?php if ($created_date !== $last_modified): ?>
                    <p><strong><?php _e('Last Modified:', 'custom-rental-manager'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_modified)); ?></p>
                <?php endif; ?>
            </div>
            
            <div id="status-workflow">
                <!-- Populated by JavaScript -->
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Status descriptions
            var statusDescriptions = {
                'pending': '<?php _e('Booking is awaiting confirmation', 'custom-rental-manager'); ?>',
                'confirmed': '<?php _e('Booking is confirmed and ready for pickup', 'custom-rental-manager'); ?>',
                'active': '<?php _e('Customer has picked up the vehicle', 'custom-rental-manager'); ?>',
                'completed': '<?php _e('Vehicle has been returned and booking is complete', 'custom-rental-manager'); ?>',
                'cancelled': '<?php _e('Booking has been cancelled', 'custom-rental-manager'); ?>'
            };
            
            // Update status description
            $('#booking_status').on('change', function() {
                var status = $(this).val();
                $('#status-description').text(statusDescriptions[status] || '');
                
                // Auto-update vehicle status when booking status changes
                updateVehicleStatus(status);
                
                // Auto-suggest payment status
                suggestPaymentStatus(status);
                
                updateStatusWorkflow();
            });
            
            function updateVehicleStatus(bookingStatus) {
                var vehicleId = $('#vehicle_id').val();
                if (!vehicleId) return;
                
                // This would typically make an AJAX call to update vehicle status
                // For now, we'll show a notice
                var vehicleStatus = '';
                switch (bookingStatus) {
                    case 'active':
                        vehicleStatus = 'rented';
                        break;
                    case 'completed':
                    case 'cancelled':
                        vehicleStatus = 'available';
                        break;
                    default:
                        return; // No change needed
                }
                
                if (vehicleStatus) {
                    $('#status-description').append('<br><em><?php _e('Note: Vehicle status will be updated to', 'custom-rental-manager'); ?> "' + vehicleStatus + '"</em>');
                }
            }
            
            function suggestPaymentStatus(bookingStatus) {
                var currentPaymentStatus = $('#payment_status').val();
                
                // Don't override if already set to paid or refunded
                if (currentPaymentStatus === 'paid' || currentPaymentStatus === 'refunded') {
                    return;
                }
                
                switch (bookingStatus) {
                    case 'confirmed':
                        if (currentPaymentStatus === 'pending') {
                            $('#payment_status').val('deposit_paid');
                        }
                        break;
                    case 'active':
                        $('#payment_status').val('paid');
                        break;
                    case 'cancelled':
                        if (currentPaymentStatus === 'deposit_paid') {
                            $('#payment_status').val('refunded');
                        }
                        break;
                }
            }
            
            function updateStatusWorkflow() {
                var currentStatus = $('#booking_status').val();
                var statuses = ['pending', 'confirmed', 'active', 'completed'];
                
                var workflowHtml = '<div class="status-workflow">';
                statuses.forEach(function(status, index) {
                    var isActive = (status === currentStatus);
                    var isPast = (statuses.indexOf(currentStatus) > index);
                    var className = isActive ? 'active' : (isPast ? 'completed' : 'future');
                    
                    workflowHtml += '<div class="workflow-step ' + className + '">';
                    workflowHtml += '<span class="step-number">' + (index + 1) + '</span>';
                    workflowHtml += '<span class="step-label">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
                    workflowHtml += '</div>';
                    
                    if (index < statuses.length - 1) {
                        workflowHtml += '<div class="workflow-arrow ' + (isPast ? 'completed' : 'future') + '">→</div>';
                    }
                });
                workflowHtml += '</div>';
                
                $('#status-workflow').html(workflowHtml);
            }
            
            // Payment status change handler
            $('#payment_status').on('change', function() {
                var paymentStatus = $(this).val();
                var totalAmount = parseFloat($('#total_amount').val()) || 0;
                
                // Auto-suggest deposit amount
                if (paymentStatus === 'deposit_paid' && !$('#deposit_amount').val() && totalAmount > 0) {
                    var suggestedDeposit = Math.min(totalAmount * 0.3, 200); // 30% or €200 max
                    $('#deposit_amount').val(suggestedDeposit.toFixed(2));
                }
            });
            
            // Initialize
            $('#booking_status').trigger('change');
        });
        </script>
        
        <style>
        .booking-timeline {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .timeline-info p {
            margin: 5px 0;
        }
        
        .status-workflow {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 15px;
            padding: 10px;
            background: white;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .workflow-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .workflow-step.completed .step-number {
            background: #4CAF50;
            color: white;
        }
        
        .workflow-step.active .step-number {
            background: #2196F3;
            color: white;
        }
        
        .workflow-step.future .step-number {
            background: #e0e0e0;
            color: #666;
        }
        
        .step-label {
            font-size: 0.9em;
            text-align: center;
        }
        
        .workflow-arrow {
            font-size: 1.2em;
            margin: 0 10px;
        }
        
        .workflow-arrow.completed {
            color: #4CAF50;
        }
        
        .workflow-arrow.future {
            color: #e0e0e0;
        }
        </style>
        <?php
    }
    
    /**
     * Booking notes meta box - NOTES & DOCUMENTS
     */
    public function booking_notes_meta_box($post) {
        $booking_notes = get_post_meta($post->ID, '_crcm_booking_notes', true);
        $internal_notes = get_post_meta($post->ID, '_crcm_internal_notes', true);
        $pickup_notes = get_post_meta($post->ID, '_crcm_pickup_notes', true);
        $return_notes = get_post_meta($post->ID, '_crcm_return_notes', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="booking_notes"><?php _e('Customer Notes', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea name="booking_notes" id="booking_notes" rows="4" cols="50" class="large-text" placeholder="<?php _e('Special requests, requirements, etc.', 'custom-rental-manager'); ?>"><?php echo esc_textarea($booking_notes); ?></textarea>
                    <p class="description"><?php _e('Notes visible to customer', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="internal_notes"><?php _e('Internal Notes', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea name="internal_notes" id="internal_notes" rows="4" cols="50" class="large-text" placeholder="<?php _e('Internal staff notes, not visible to customer', 'custom-rental-manager'); ?>"><?php echo esc_textarea($internal_notes); ?></textarea>
                    <p class="description"><?php _e('Private notes for staff only', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="pickup_notes"><?php _e('Pickup Notes', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea name="pickup_notes" id="pickup_notes" rows="3" cols="50" class="large-text" placeholder="<?php _e('Vehicle condition, fuel level, damage inspection...', 'custom-rental-manager'); ?>"><?php echo esc_textarea($pickup_notes); ?></textarea>
                    <p class="description"><?php _e('Notes from vehicle pickup inspection', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="return_notes"><?php _e('Return Notes', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea name="return_notes" id="return_notes" rows="3" cols="50" class="large-text" placeholder="<?php _e('Return condition, fuel level, damage assessment, cleaning fees...', 'custom-rental-manager'); ?>"><?php echo esc_textarea($return_notes); ?></textarea>
                    <p class="description"><?php _e('Notes from vehicle return inspection', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
        </table>
        
        <div class="booking-documents">
            <h4><?php _e('Documents & Attachments', 'custom-rental-manager'); ?></h4>
            <p><?php _e('Document management will be added in future version.', 'custom-rental-manager'); ?></p>
            <div class="document-checklist">
                <label><input type="checkbox" disabled> <?php _e('Driver License Copy', 'custom-rental-manager'); ?></label>
                <label><input type="checkbox" disabled> <?php _e('Credit Card Authorization', 'custom-rental-manager'); ?></label>
                <label><input type="checkbox" disabled> <?php _e('Rental Agreement', 'custom-rental-manager'); ?></label>
                <label><input type="checkbox" disabled> <?php _e('Vehicle Inspection Report', 'custom-rental-manager'); ?></label>
                <label><input type="checkbox" disabled> <?php _e('Insurance Documentation', 'custom-rental-manager'); ?></label>
            </div>
        </div>
        
        <style>
        .booking-documents {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .document-checklist label {
            display: block;
            margin: 5px 0;
            color: #666;
        }
        
        .document-checklist input[type="checkbox"] {
            margin-right: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Save booking data - COMPLETE & SAFE
     */
    public function save_booking_data($post_id, $post) {
        // Security checks
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (!isset($_POST['crcm_booking_meta_nonce']) || !wp_verify_nonce($_POST['crcm_booking_meta_nonce'], 'crcm_booking_meta')) return;
        
        // PERMISSION CHECK: Only when saving
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Text fields
        $text_fields = array(
            'booking_reference' => '_crcm_booking_reference',
            'pickup_location' => '_crcm_pickup_location',
            'return_location' => '_crcm_return_location',
            'customer_name' => '_crcm_customer_name',
            'customer_email' => '_crcm_customer_email',
            'customer_phone' => '_crcm_customer_phone',
            'customer_address' => '_crcm_customer_address',
            'customer_city' => '_crcm_customer_city',
            'customer_country' => '_crcm_customer_country',
            'customer_license' => '_crcm_customer_license',
            'emergency_contact' => '_crcm_emergency_contact',
            'emergency_phone' => '_crcm_emergency_phone',
            'pickup_time' => '_crcm_pickup_time',
            'return_time' => '_crcm_return_time',
            'insurance_type' => '_crcm_insurance_type',
            'discount_reason' => '_crcm_discount_reason',
            'booking_status' => '_crcm_booking_status',
            'payment_status' => '_crcm_payment_status',
            'payment_method' => '_crcm_payment_method',
            'booking_notes' => '_crcm_booking_notes',
            'internal_notes' => '_crcm_internal_notes',
            'pickup_notes' => '_crcm_pickup_notes',
            'return_notes' => '_crcm_return_notes',
        );
        
        // Numeric fields
        $numeric_fields = array(
            'vehicle_id' => '_crcm_vehicle_id',
            'customer_age' => '_crcm_customer_age',
            'base_rate' => '_crcm_base_rate',
            'total_days' => '_crcm_total_days',
            'delivery_fee' => '_crcm_delivery_fee',
            'discount_amount' => '_crcm_discount_amount',
            'total_amount' => '_crcm_total_amount',
            'deposit_amount' => '_crcm_deposit_amount',
        );
        
        // Date fields
        $date_fields = array(
            'pickup_date' => '_crcm_pickup_date',
            'return_date' => '_crcm_return_date',
            'customer_license_expires' => '_crcm_customer_license_expires',
        );
        
        // Save text fields
        foreach ($text_fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                
                if (in_array($field, array('booking_notes', 'internal_notes', 'pickup_notes', 'return_notes'))) {
                    $value = sanitize_textarea_field($value);
                } else {
                    $value = sanitize_text_field($value);
                    
                    // Special sanitization
                    if ($field === 'customer_email') {
                        $value = sanitize_email($value);
                    } elseif ($field === 'booking_reference') {
                        $value = strtoupper($value);
                    }
                }
                
                update_post_meta($post_id, $meta_key, $value);
            }
        }
        
        // Save numeric fields
        foreach ($numeric_fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                
                if (in_array($field, array('vehicle_id', 'customer_age', 'total_days'))) {
                    $value = intval($value);
                } else {
                    $value = floatval($value);
                }
                
                update_post_meta($post_id, $meta_key, $value);
            }
        }
        
        // Save date fields
        foreach ($date_fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $meta_key, $value);
            }
        }
        
        // Handle extras array
        if (isset($_POST['selected_extras']) && is_array($_POST['selected_extras'])) {
            $extras = array_map('sanitize_text_field', $_POST['selected_extras']);
            update_post_meta($post_id, '_crcm_selected_extras', $extras);
        } else {
            update_post_meta($post_id, '_crcm_selected_extras', array());
        }
        
        // Auto-generate title if empty
        $current_title = get_the_title($post_id);
        if (empty($current_title) || $current_title === 'Auto Draft') {
            $customer_name = get_post_meta($post_id, '_crcm_customer_name', true);
            $booking_reference = get_post_meta($post_id, '_crcm_booking_reference', true);
            
            if ($customer_name) {
                $title = 'Booking: ' . $customer_name;
                if ($booking_reference) {
                    $title .= ' (' . $booking_reference . ')';
                }
            } elseif ($booking_reference) {
                $title = 'Booking: ' . $booking_reference;
            } else {
                $title = 'Booking #' . $post_id;
            }
            
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $title
            ));
        }
        
        // Update vehicle status based on booking status
        $vehicle_id = get_post_meta($post_id, '_crcm_vehicle_id', true);
        $booking_status = get_post_meta($post_id, '_crcm_booking_status', true);
        
        if ($vehicle_id && $booking_status) {
            $vehicle_status = '';
            switch ($booking_status) {
                case 'active':
                    $vehicle_status = 'rented';
                    break;
                case 'completed':
                case 'cancelled':
                    $vehicle_status = 'available';
                    break;
            }
            
            if ($vehicle_status) {
                update_post_meta($vehicle_id, '_crcm_vehicle_status', $vehicle_status);
            }
        }
        
        // Set default statuses if not set
        if (!get_post_meta($post_id, '_crcm_booking_status', true)) {
            update_post_meta($post_id, '_crcm_booking_status', 'pending');
        }
        
        if (!get_post_meta($post_id, '_crcm_payment_status', true)) {
            update_post_meta($post_id, '_crcm_payment_status', 'pending');
        }
    }
    
    /**
     * AJAX: Get vehicle rate
     */
    public function ajax_get_vehicle_rate() {
        check_ajax_referer('crcm_ajax', 'nonce');
        
        $vehicle_id = intval($_POST['vehicle_id']);
        
        if (!$vehicle_id) {
            wp_send_json_error('Invalid vehicle ID');
        }
        
        $daily_rate = get_post_meta($vehicle_id, '_crcm_daily_rate', true);
        $weekly_rate = get_post_meta($vehicle_id, '_crcm_weekly_rate', true);
        $monthly_rate = get_post_meta($vehicle_id, '_crcm_monthly_rate', true);
        
        wp_send_json_success(array(
            'daily_rate' => floatval($daily_rate),
            'weekly_rate' => floatval($weekly_rate),
            'monthly_rate' => floatval($monthly_rate)
        ));
    }
    
    /**
     * AJAX: Calculate total price
     */
    public function ajax_calculate_total() {
        check_ajax_referer('crcm_ajax', 'nonce');
        
        $vehicle_id = intval($_POST['vehicle_id']);
        $pickup_date = sanitize_text_field($_POST['pickup_date']);
        $return_date = sanitize_text_field($_POST['return_date']);
        $extras = isset($_POST['extras']) ? array_map('sanitize_text_field', $_POST['extras']) : array();
        $insurance = sanitize_text_field($_POST['insurance']);
        
        // Calculate days
        $pickup = new DateTime($pickup_date);
        $return = new DateTime($return_date);
        $days = $pickup->diff($return)->days;
        
        if ($days <= 0) {
            wp_send_json_error('Invalid date range');
        }
        
        // Get vehicle rates
        $daily_rate = floatval(get_post_meta($vehicle_id, '_crcm_daily_rate', true));
        $weekly_rate = floatval(get_post_meta($vehicle_id, '_crcm_weekly_rate', true));
        $monthly_rate = floatval(get_post_meta($vehicle_id, '_crcm_monthly_rate', true));
        
        // Calculate base cost (use best rate)
        $base_cost = $daily_rate * $days;
        
        if ($days >= 30 && $monthly_rate > 0) {
            $months = floor($days / 30);
            $remaining_days = $days % 30;
            $base_cost = ($monthly_rate * $months) + ($daily_rate * $remaining_days);
        } elseif ($days >= 7 && $weekly_rate > 0) {
            $weeks = floor($days / 7);
            $remaining_days = $days % 7;
            $base_cost = ($weekly_rate * $weeks) + ($daily_rate * $remaining_days);
        }
        
        // Calculate extras cost
        $extras_cost = 0;
        $available_extras = array(
            'gps' => 5, 'child_seat' => 8, 'additional_driver' => 10,
            'roof_box' => 12, 'ski_rack' => 10, 'bike_rack' => 10,
            'wifi_hotspot' => 7, 'phone_holder' => 3, 'usb_charger' => 2,
            'first_aid_kit' => 5
        );
        
        foreach ($extras as $extra) {
            if (isset($available_extras[$extra])) {
                $extras_cost += $available_extras[$extra] * $days;
            }
        }
        
        // Calculate insurance cost
        $insurance_cost = 0;
        $insurance_rates = array('basic' => 10, 'comprehensive' => 20, 'premium' => 30);
        if (isset($insurance_rates[$insurance])) {
            $insurance_cost = $insurance_rates[$insurance] * $days;
        }
        
        $total = $base_cost + $extras_cost + $insurance_cost;
        
        wp_send_json_success(array(
            'days' => $days,
            'base_cost' => $base_cost,
            'extras_cost' => $extras_cost,
            'insurance_cost' => $insurance_cost,
            'total' => $total
        ));
    }
    
    /**
     * AJAX: Check vehicle availability
     */
    public function ajax_check_availability() {
        check_ajax_referer('crcm_ajax', 'nonce');
        
        $vehicle_id = intval($_POST['vehicle_id']);
        $pickup_date = sanitize_text_field($_POST['pickup_date']);
        $return_date = sanitize_text_field($_POST['return_date']);
        $booking_id = intval($_POST['booking_id']);
        
        $conflicts = $this->check_booking_conflicts($vehicle_id, $pickup_date, $return_date, $booking_id);
        
        wp_send_json_success(array(
            'available' => empty($conflicts),
            'conflicts' => $conflicts
        ));
    }
    
    /**
     * Check booking conflicts for a vehicle
     */
    public function check_booking_conflicts($vehicle_id, $pickup_date, $return_date, $exclude_booking_id = 0) {
        $args = array(
            'post_type' => 'crcm_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_crcm_vehicle_id',
                    'value' => $vehicle_id
                ),
                array(
                    'key' => '_crcm_booking_status',
                    'value' => array('confirmed', 'active'),
                    'compare' => 'IN'
                )
            )
        );
        
        if ($exclude_booking_id > 0) {
            $args['post__not_in'] = array($exclude_booking_id);
        }
        
        $bookings = get_posts($args);
        $conflicts = array();
        
        $new_pickup = new DateTime($pickup_date);
        $new_return = new DateTime($return_date);
        
        foreach ($bookings as $booking) {
            $existing_pickup = new DateTime(get_post_meta($booking->ID, '_crcm_pickup_date', true));
            $existing_return = new DateTime(get_post_meta($booking->ID, '_crcm_return_date', true));
            
            // Check for overlap
            if ($new_pickup < $existing_return && $new_return > $existing_pickup) {
                $conflicts[] = array(
                    'booking_id' => $booking->ID,
                    'customer' => get_post_meta($booking->ID, '_crcm_customer_name', true),
                    'pickup' => $existing_pickup->format('Y-m-d'),
                    'return' => $existing_return->format('Y-m-d')
                );
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Get booking statistics
     */
    public function get_booking_statistics() {
        $bookings = get_posts(array(
            'post_type' => 'crcm_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $stats = array(
            'total' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'active' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'total_revenue' => 0,
            'average_booking' => 0
        );
        
        $total_amount = 0;
        $revenue_count = 0;
        
        foreach ($bookings as $booking) {
            $stats['total']++;
            
            $status = get_post_meta($booking->ID, '_crcm_booking_status', true) ?: 'pending';
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
            
            $amount = floatval(get_post_meta($booking->ID, '_crcm_total_amount', true));
            if ($amount > 0) {
                $total_amount += $amount;
                $revenue_count++;
                
                // Only count revenue from completed bookings
                if ($status === 'completed') {
                    $stats['total_revenue'] += $amount;
                }
            }
        }
        
        if ($revenue_count > 0) {
            $stats['average_booking'] = $total_amount / $revenue_count;
        }
        
        return $stats;
    }
}
