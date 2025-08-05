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
        
        // Admin styles
        add_action('admin_head', array($this, 'admin_booking_styles'));
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
            );
        }
        
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
            </table>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Calculate rental days automatically
            function calculateRentalDays() {
                const pickupDate = new Date($('#pickup_date').val());
                const returnDate = new Date($('#return_date').val());
                
                if (pickupDate && returnDate && returnDate > pickupDate) {
                    const timeDiff = returnDate.getTime() - pickupDate.getTime();
                    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    
                    $('#rental-days-display').text('Giorni di noleggio: ' + daysDiff);
                    $('input[name="booking_data[rental_days]"]').val(daysDiff);
                    
                    // Trigger pricing recalculation
                    $(document).trigger('booking_dates_changed', [daysDiff]);
                    
                    return daysDiff;
                }
                return 1;
            }
            
            // Set minimum return date based on pickup date
            $('#pickup_date').on('change', function() {
                const pickupDate = $(this).val();
                const nextDay = new Date(pickupDate);
                nextDay.setDate(nextDay.getDate() + 1);
                
                $('#return_date').attr('min', nextDay.toISOString().split('T')[0]);
                
                // Auto-adjust return date if it's now invalid
                if ($('#return_date').val() <= pickupDate) {
                    $('#return_date').val(nextDay.toISOString().split('T')[0]);
                }
                
                calculateRentalDays();
            });
            
            $('#return_date').on('change', calculateRentalDays);
            
            // Initial calculation
            calculateRentalDays();
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
        
        <script>
        jQuery(document).ready(function($) {
            let searchTimeout;
            
            $('#customer_search').on('input', function() {
                const query = $(this).val();
                const $results = $('#customer_search_results');
                
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    $results.empty().hide();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    $results.html('<div class="crcm-loading">Ricerca in corso...</div>').show();
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'crcm_search_customers',
                            query: query,
                            nonce: '<?php echo wp_create_nonce('crcm_admin_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success && response.data.length > 0) {
                                let html = '<div class="crcm-customer-results">';
                                response.data.forEach(function(customer) {
                                    html += `
                                        <div class="customer-result" data-customer-id="${customer.ID}">
                                            <div class="customer-info">
                                                <strong>${customer.display_name}</strong>
                                                <span class="customer-email">${customer.user_email}</span>
                                                ${customer.phone ? `<span class="customer-phone">${customer.phone}</span>` : ''}
                                            </div>
                                            <button type="button" class="button button-small select-customer">Seleziona</button>
                                        </div>
                                    `;
                                });
                                html += '</div>';
                                $results.html(html);
                            } else {
                                $results.html('<div class="no-results">Nessun cliente trovato</div>');
                            }
                        },
                        error: function() {
                            $results.html('<div class="error">Errore nella ricerca</div>');
                        }
                    });
                }, 300);
            });
            
            // Select customer
            $(document).on('click', '.select-customer', function() {
                const $result = $(this).closest('.customer-result');
                const customerId = $result.data('customer-id');
                const customerName = $result.find('strong').text();
                const customerEmail = $result.find('.customer-email').text();
                const customerPhone = $result.find('.customer-phone').text();
                
                $('#selected_customer_id').val(customerId);
                $('#customer_search').val('');
                $('#customer_search_results').empty().hide();
                
                let customerCardHtml = `
                    <div class="customer-card">
                        <h4>${customerName}</h4>
                        <p><strong>Email:</strong> ${customerEmail}</p>
                        <p><strong>Ruolo:</strong> Rental Customer</p>
                        ${customerPhone ? `<p><strong>Telefono:</strong> ${customerPhone}</p>` : ''}
                        <button type="button" class="button button-secondary" id="change_customer">
                            Cambia Cliente
                        </button>
                    </div>
                `;
                
                $('#selected_customer_info').html(customerCardHtml).show();
            });
            
            // Change customer
            $(document).on('click', '#change_customer', function() {
                $('#selected_customer_id').val('');
                $('#selected_customer_info').hide();
                $('#customer_search').focus();
            });
            
            // Hide results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.crcm-customer-search-container').length) {
                    $('#customer_search_results').hide();
                }
            });
        });
        </script>
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
        
        <script>
        jQuery(document).ready(function($) {
            $('#vehicle_id').on('change', function() {
                const vehicleId = $(this).val();
                
                if (!vehicleId) {
                    $('#vehicle_details_display, #availability_check').hide();
                    return;
                }
                
                // Show loading state
                $('#vehicle_details_content').html('<div class="crcm-loading">Caricamento dettagli veicolo...</div>');
                $('#availability_status').html('<div class="crcm-loading">Controllo disponibilità...</div>');
                $('#vehicle_details_display, #availability_check').show();
                
                // Load vehicle details
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'crcm_get_vehicle_booking_data',
                        vehicle_id: vehicleId,
                        nonce: '<?php echo wp_create_nonce('crcm_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#vehicle_details_content').html(response.data.details);
                            
                            // Trigger pricing update
                            $(document).trigger('vehicle_selected', [vehicleId, response.data]);
                            
                            // Check availability
                            checkVehicleAvailability(vehicleId);
                        } else {
                            $('#vehicle_details_content').html('<div class="error">Errore nel caricamento dei dettagli</div>');
                        }
                    },
                    error: function() {
                        $('#vehicle_details_content').html('<div class="error">Errore di connessione</div>');
                    }
                });
            });
            
            function checkVehicleAvailability(vehicleId) {
                const pickupDate = $('#pickup_date').val();
                const returnDate = $('#return_date').val();
                
                if (!pickupDate || !returnDate) {
                    $('#availability_status').html('<div class="warning">Seleziona le date per controllare la disponibilità</div>');
                    return;
                }
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'crcm_check_vehicle_availability',
                        vehicle_id: vehicleId,
                        pickup_date: pickupDate,
                        return_date: returnDate,
                        nonce: '<?php echo wp_create_nonce('crcm_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            const available = response.data.available_quantity;
                            const total = response.data.total_quantity;
                            
                            if (available > 0) {
                                $('#availability_status').html(`
                                    <div class="success">
                                        <strong>✅ Disponibile</strong><br>
                                        ${available} unità disponibili su ${total} totali
                                    </div>
                                `);
                            } else {
                                $('#availability_status').html(`
                                    <div class="error">
                                        <strong>❌ Non Disponibile</strong><br>
                                        Nessuna unità disponibile per le date selezionate
                                    </div>
                                `);
                            }
                        } else {
                            $('#availability_status').html('<div class="error">Errore nel controllo disponibilità</div>');
                        }
                    }
                });
            }
            
            // Check availability when dates change
            $(document).on('booking_dates_changed', function() {
                const vehicleId = $('#vehicle_id').val();
                if (vehicleId) {
                    checkVehicleAvailability(vehicleId);
                }
            });
            
            // Initial check if vehicle is already selected
            if ($('#vehicle_id').val()) {
                $('#vehicle_id').trigger('change');
            }
        });
        </script>
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
        
        // Default values
        if (empty($pricing_breakdown)) {
            $pricing_breakdown = array(
                'base_total' => 0,
                'extras_total' => 0,
                'insurance_total' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'final_total' => 0,
                'selected_extras' => array(),
                'selected_insurance' => 'basic',
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
                <table class="crcm-pricing-table">
                    <tbody id="pricing-breakdown-content">
                        <tr class="pricing-row">
                            <td><?php _e('Tariffa base', 'custom-rental-manager'); ?></td>
                            <td class="price-cell">€<span id="base-total">0.00</span></td>
                        </tr>
                        <tr class="pricing-row extras-row" style="display: none;">
                            <td><?php _e('Servizi extra', 'custom-rental-manager'); ?></td>
                            <td class="price-cell">€<span id="extras-total">0.00</span></td>
                        </tr>
                        <tr class="pricing-row insurance-row" style="display: none;">
                            <td><?php _e('Assicurazione premium', 'custom-rental-manager'); ?></td>
                            <td class="price-cell">€<span id="insurance-total">0.00</span></td>
                        </tr>
                        <tr class="pricing-row discount-row" style="display: none;">
                            <td><?php _e('Sconto applicato', 'custom-rental-manager'); ?></td>
                            <td class="price-cell discount">-€<span id="discount-total">0.00</span></td>
                        </tr>
                        <tr class="pricing-row total-row">
                            <td><strong><?php _e('TOTALE', 'custom-rental-manager'); ?></strong></td>
                            <td class="price-cell"><strong>€<span id="final-total">0.00</span></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Hidden fields for storing pricing data -->
            <input type="hidden" name="pricing_breakdown[base_total]" id="base_total_input" value="<?php echo esc_attr($pricing_breakdown['base_total']); ?>" />
            <input type="hidden" name="pricing_breakdown[extras_total]" id="extras_total_input" value="<?php echo esc_attr($pricing_breakdown['extras_total']); ?>" />
            <input type="hidden" name="pricing_breakdown[insurance_total]" id="insurance_total_input" value="<?php echo esc_attr($pricing_breakdown['insurance_total']); ?>" />
            <input type="hidden" name="pricing_breakdown[final_total]" id="final_total_input" value="<?php echo esc_attr($pricing_breakdown['final_total']); ?>" />
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let vehicleData = null;
            let rentalDays = 1;
            
            // Listen for vehicle selection
            $(document).on('vehicle_selected', function(e, vehicleId, data) {
                vehicleData = data;
                loadVehicleExtrasAndInsurance(vehicleId);
                calculatePricing();
            });
            
            // Listen for date changes
            $(document).on('booking_dates_changed', function(e, days) {
                rentalDays = days;
                calculatePricing();
            });
            
            function loadVehicleExtrasAndInsurance(vehicleId) {
                if (!vehicleData || !vehicleData.extras || !vehicleData.insurance) return;
                
                // Load extras
                if (vehicleData.extras.length > 0) {
                    let extrasHtml = '';
                    vehicleData.extras.forEach(function(extra, index) {
                        extrasHtml += `
                            <label class="extra-option">
                                <input type="checkbox" name="pricing_breakdown[selected_extras][]" 
                                       value="${index}" data-name="${extra.name}" data-rate="${extra.daily_rate}">
                                <span class="extra-name">${extra.name}</span>
                                <span class="extra-price">+€${parseFloat(extra.daily_rate).toFixed(2)}/giorno</span>
                            </label>
                        `;
                    });
                    $('#extras-list').html(extrasHtml);
                    $('#extras-selection').show();
                } else {
                    $('#extras-selection').hide();
                }
                
                // Load insurance options
                if (vehicleData.insurance && vehicleData.insurance.premium && vehicleData.insurance.premium.enabled) {
                    let insuranceHtml = `
                        <label class="insurance-option">
                            <input type="radio" name="pricing_breakdown[selected_insurance]" value="basic" checked>
                            <span class="insurance-name">Assicurazione Base</span>
                            <span class="insurance-price">Inclusa</span>
                        </label>
                        <label class="insurance-option">
                            <input type="radio" name="pricing_breakdown[selected_insurance]" value="premium" 
                                   data-rate="${vehicleData.insurance.premium.daily_rate}">
                            <span class="insurance-name">Assicurazione Premium</span>
                            <span class="insurance-price">+€${parseFloat(vehicleData.insurance.premium.daily_rate).toFixed(2)}/giorno</span>
                        </label>
                    `;
                    $('#insurance-options').html(insuranceHtml);
                    $('#insurance-selection').show();
                } else {
                    $('#insurance-selection').hide();
                }
            }
            
            function calculatePricing() {
                if (!vehicleData || !vehicleData.pricing) return;
                
                const baseRate = parseFloat(vehicleData.pricing.daily_rate) || 0;
                const baseTotal = baseRate * rentalDays;
                
                // Calculate extras
                let extrasTotal = 0;
                $('input[name="pricing_breakdown[selected_extras][]"]:checked').each(function() {
                    const rate = parseFloat($(this).data('rate')) || 0;
                    extrasTotal += rate * rentalDays;
                });
                
                // Calculate insurance
                let insuranceTotal = 0;
                const selectedInsurance = $('input[name="pricing_breakdown[selected_insurance]"]:checked');
                if (selectedInsurance.val() === 'premium') {
                    const rate = parseFloat(selectedInsurance.data('rate')) || 0;
                    insuranceTotal = rate * rentalDays;
                }
                
                // Manual discount
                const discount = parseFloat($('#manual_discount').val()) || 0;
                
                // Final total
                const finalTotal = Math.max(0, baseTotal + extrasTotal + insuranceTotal - discount);
                
                // Update display
                $('#base-total').text(baseTotal.toFixed(2));
                $('#extras-total').text(extrasTotal.toFixed(2));
                $('#insurance-total').text(insuranceTotal.toFixed(2));
                $('#discount-total').text(discount.toFixed(2));
                $('#final-total').text(finalTotal.toFixed(2));
                
                // Update hidden inputs
                $('#base_total_input').val(baseTotal);
                $('#extras_total_input').val(extrasTotal);
                $('#insurance_total_input').val(insuranceTotal);
                $('#final_total_input').val(finalTotal);
                
                // Show/hide rows
                $('.extras-row').toggle(extrasTotal > 0);
                $('.insurance-row').toggle(insuranceTotal > 0);
                $('.discount-row').toggle(discount > 0);
            }
            
            // Event handlers for pricing changes
            $(document).on('change', 'input[name="pricing_breakdown[selected_extras][]"]', calculatePricing);
            $(document).on('change', 'input[name="pricing_breakdown[selected_insurance]"]', calculatePricing);
            $('#manual_discount').on('input', calculatePricing);
        });
        </script>
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
        
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        
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
     * AJAX: Check vehicle availability
     */
    public function ajax_check_vehicle_availability() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');
        
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
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
        
        // Save booking data
        if (isset($_POST['booking_data'])) {
            $booking_data = array();
            foreach ($_POST['booking_data'] as $key => $value) {
                $booking_data[$key] = sanitize_text_field($value);
            }
            update_post_meta($post_id, '_crcm_booking_data', $booking_data);
        }
        
        // Save pricing breakdown
        if (isset($_POST['pricing_breakdown'])) {
            $pricing_breakdown = array();
            foreach ($_POST['pricing_breakdown'] as $key => $value) {
                if (is_array($value)) {
                    $pricing_breakdown[$key] = array_map('sanitize_text_field', $value);
                } else {
                    $pricing_breakdown[$key] = sanitize_text_field($value);
                }
            }
            update_post_meta($post_id, '_crcm_pricing_breakdown', $pricing_breakdown);
        }
        
        // Save booking status
        if (isset($_POST['booking_status'])) {
            update_post_meta($post_id, '_crcm_booking_status', sanitize_text_field($_POST['booking_status']));
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
    
    /**
     * Admin styles for booking interface
     */
    public function admin_booking_styles() {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'crcm_booking') {
            ?>
            <style>
            /* BOOKING ADMIN STYLES */
            .post-type-crcm_booking .postbox {
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                margin-bottom: 20px;
            }
            
            .post-type-crcm_booking .postbox-header {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 8px 8px 0 0;
                padding: 12px 16px;
            }
            
            .post-type-crcm_booking .postbox-header h2 {
                font-size: 15px;
                font-weight: 600;
                color: #333;
                margin: 0;
            }
            
            /* Customer Search */
            .crcm-customer-search-container {
                position: relative;
            }
            
            .crcm-search-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-top: none;
                border-radius: 0 0 4px 4px;
                max-height: 300px;
                overflow-y: auto;
                z-index: 1000;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            
            .customer-result {
                padding: 12px;
                border-bottom: 1px solid #f0f0f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .customer-result:hover {
                background: #f8f9fa;
            }
            
            .customer-info {
                flex: 1;
            }
            
            .customer-info strong {
                display: block;
                font-size: 14px;
            }
            
            .customer-email,
            .customer-phone {
                display: block;
                font-size: 12px;
                color: #666;
            }
            
            .customer-card {
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 6px;
                padding: 15px;
                margin-top: 10px;
            }
            
            .customer-card h4 {
                margin: 0 0 10px 0;
                color: #333;
            }
            
            .customer-card p {
                margin: 5px 0;
                font-size: 13px;
            }
            
            /* Vehicle Details */
            .crcm-vehicle-summary {
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 6px;
                padding: 15px;
            }
            
            .vehicle-basic-info h5 {
                margin: 0 0 10px 0;
                font-size: 16px;
                color: #333;
            }
            
            .vehicle-specs {
                margin: 10px 0;
            }
            
            .spec-item {
                display: inline-block;
                background: white;
                padding: 4px 8px;
                margin: 2px 4px 2px 0;
                border-radius: 12px;
                font-size: 12px;
                border: 1px solid #ddd;
            }
            
            .vehicle-pricing {
                margin: 10px 0;
                font-size: 16px;
            }
            
            .vehicle-extras h6,
            .vehicle-insurance h6,
            .vehicle-policies h6 {
                margin: 15px 0 8px 0;
                font-size: 14px;
                color: #333;
            }
            
            .vehicle-extras ul,
            .vehicle-policies ul {
                margin: 0;
                padding-left: 20px;
            }
            
            .vehicle-extras li,
            .vehicle-policies li {
                margin: 5px 0;
                font-size: 13px;
            }
            
            .extra-price,
            .insurance-price {
                color: #666;
                font-size: 12px;
            }
            
            /* Availability Status */
            .crcm-availability-status .success {
                background: #d4edda;
                color: #155724;
                padding: 10px;
                border-radius: 4px;
                border: 1px solid #c3e6cb;
            }
            
            .crcm-availability-status .error {
                background: #f8d7da;
                color: #721c24;
                padding: 10px;
                border-radius: 4px;
                border: 1px solid #f5c6cb;
            }
            
            .crcm-availability-status .warning {
                background: #fff3cd;
                color: #856404;
                padding: 10px;
                border-radius: 4px;
                border: 1px solid #ffeaa7;
            }
            
            /* Pricing */
            .crcm-pricing-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
            }
            
            .crcm-pricing-table td {
                padding: 8px 12px;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .crcm-pricing-table .price-cell {
                text-align: right;
                font-weight: 600;
            }
            
            .crcm-pricing-table .total-row td {
                border-top: 2px solid #333;
                font-size: 16px;
                padding-top: 12px;
            }
            
            .crcm-pricing-table .discount {
                color: #e74c3c;
            }
            
            /* Extra Services */
            .extra-option,
            .insurance-option {
                display: block;
                padding: 10px;
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin: 5px 0;
                cursor: pointer;
            }
            
            .extra-option:hover,
            .insurance-option:hover {
                background: #e9ecef;
            }
            
            .extra-option input,
            .insurance-option input {
                margin-right: 8px;
            }
            
            .extra-name,
            .insurance-name {
                font-weight: 500;
            }
            
            .extra-price,
            .insurance-price {
                float: right;
                color: #666;
                font-size: 13px;
            }
            
            /* Status Badges */
            .crcm-status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }
            
            .crcm-status-badge.pending {
                background: #fff3cd;
                color: #856404;
            }
            
            .crcm-status-badge.confirmed {
                background: #cce5ff;
                color: #004085;
            }
            
            .crcm-status-badge.active {
                background: #d4edda;
                color: #155724;
            }
            
            .crcm-status-badge.completed {
                background: #e2e3e5;
                color: #383d41;
            }
            
            .crcm-status-badge.cancelled {
                background: #f8d7da;
                color: #721c24;
            }
            
            /* Role Badges */
            .crcm-role-badge {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 10px;
                font-size: 11px;
                font-weight: 600;
            }
            
            .crcm-role-badge.customer {
                background: #d4edda;
                color: #155724;
            }
            
            .crcm-role-badge.manager {
                background: #cce5ff;
                color: #004085;
            }
            
            .crcm-role-badge.admin {
                background: #fff3cd;
                color: #856404;
            }
            
            /* Loading States */
            .crcm-loading {
                text-align: center;
                padding: 20px;
                color: #666;
                font-style: italic;
            }
            
            /* Messages */
            .no-results,
            .error {
                padding: 10px;
                text-align: center;
                color: #666;
                font-style: italic;
            }
            
            .error {
                color: #721c24;
                background: #f8d7da;
                border-radius: 4px;
            }
            
            /* Section Headers */
            .crcm-section-header {
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            
            .crcm-section-header h4 {
                margin: 0 0 5px 0;
                font-size: 16px;
                color: #333;
            }
            
            .crcm-section-header .description {
                margin: 0;
                font-size: 13px;
                color: #666;
            }
            
            /* Form Elements */
            .post-type-crcm_booking .form-table th {
                width: 200px;
                font-weight: 600;
            }
            
            .post-type-crcm_booking .form-table input,
            .post-type-crcm_booking .form-table select,
            .post-type-crcm_booking .form-table textarea {
                border-radius: 4px;
                border: 1px solid #ddd;
            }
            
            .post-type-crcm_booking .form-table input:focus,
            .post-type-crcm_booking .form-table select:focus,
            .post-type-crcm_booking .form-table textarea:focus {
                border-color: #007cba;
                box-shadow: 0 0 0 1px #007cba;
            }
            </style>
            <?php
        }
    }
}

