<?php
/**
 * Booking Manager Class - COMPLETE OPTIMIZATION & ADVANCED PRICING
 * 
 * Enhanced with dynamic insurance integration, custom pricing rates,
 * late return calculations, automatic title generation, and complete
 * synchronization with vehicle data.
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
        add_action('init', array($this, 'ensure_user_roles'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_booking_meta'));
        
        // AJAX handlers for dynamic booking creation
        add_action('wp_ajax_crcm_get_vehicle_booking_data', array($this, 'ajax_get_vehicle_booking_data'));
        add_action('wp_ajax_crcm_calculate_booking_total', array($this, 'ajax_calculate_booking_total'));
        add_action('wp_ajax_crcm_check_vehicle_availability', array($this, 'ajax_check_vehicle_availability'));
        add_action('wp_ajax_crcm_search_customers', array($this, 'ajax_search_customers'));
        
        // ENHANCED: Advanced pricing calculation AJAX
        add_action('wp_ajax_crcm_calculate_advanced_pricing', array($this, 'ajax_calculate_advanced_pricing'));
        
        // User management
        add_action('user_register', array($this, 'assign_default_customer_role'));
        add_filter('manage_users_columns', array($this, 'add_user_role_column'));
        add_action('manage_users_custom_column', array($this, 'show_user_role_column'), 10, 3);
        
        // Booking columns
        add_filter('manage_crcm_booking_posts_columns', array($this, 'booking_columns'));
        add_action('manage_crcm_booking_posts_custom_column', array($this, 'booking_column_content'), 10, 2);
        
        // ENHANCED: Auto-generate booking title
        add_action('wp_insert_post', array($this, 'auto_generate_booking_title'), 10, 2);
        
        // Admin styles
        add_action('admin_head', array($this, 'admin_booking_styles'));
    }
    
    /**
     * Ensure custom user roles exist and are properly configured
     */
    public function ensure_user_roles() {
        // Remove roles first to ensure clean setup
        remove_role('crcm_customer');
        remove_role('crcm_manager');
        
        // Create Customer role with specific capabilities
        add_role('crcm_customer', __('Rental Customer', 'custom-rental-manager'), array(
            'read' => true,
            'crcm_view_own_bookings' => true,
            'crcm_edit_own_profile' => true,
            'crcm_cancel_bookings' => true,
        ));
        
        // Create Manager role with comprehensive capabilities
        add_role('crcm_manager', __('Rental Manager', 'custom-rental-manager'), array(
            'read' => true,
            'edit_posts' => true,
            'edit_others_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'delete_others_posts' => true,
            'manage_categories' => true,
            'upload_files' => true,
            
            // Vehicle management
            'crcm_manage_vehicles' => true,
            'crcm_edit_vehicles' => true,
            'crcm_delete_vehicles' => true,
            'crcm_publish_vehicles' => true,
            
            // Booking management
            'crcm_manage_bookings' => true,
            'crcm_edit_bookings' => true,
            'crcm_delete_bookings' => true,
            'crcm_publish_bookings' => true,
            'crcm_view_all_bookings' => true,
            
            // Customer management
            'crcm_manage_customers' => true,
            'crcm_view_customer_data' => true,
            'crcm_edit_customer_profiles' => true,
            
            // Reports and analytics
            'crcm_view_reports' => true,
            'crcm_export_data' => true,
        ));
        
        // Add capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $capabilities = array(
                'crcm_manage_vehicles', 'crcm_edit_vehicles', 'crcm_delete_vehicles', 'crcm_publish_vehicles',
                'crcm_manage_bookings', 'crcm_edit_bookings', 'crcm_delete_bookings', 'crcm_publish_bookings',
                'crcm_view_all_bookings', 'crcm_manage_customers', 'crcm_view_customer_data',
                'crcm_edit_customer_profiles', 'crcm_view_reports', 'crcm_export_data'
            );
            
            foreach ($capabilities as $cap) {
                $admin->add_cap($cap);
            }
        }
        
        // Ensure roles are properly registered
        wp_roles()->reinit();
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
        
        // ENHANCED: Advanced pricing with all components
        add_meta_box(
            'crcm_booking_pricing',
            '💰 ' . __('Advanced Pricing & Services', 'custom-rental-manager'),
            array($this, 'advanced_pricing_meta_box'),
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
        
        <div class="crcm-booking-details">
            
            <div class="crcm-section-header">
                <h4><?php _e('Dettagli Prenotazione', 'custom-rental-manager'); ?></h4>
                <p class="description"><?php _e('Configura date, orari e luoghi per la prenotazione', 'custom-rental-manager'); ?></p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="pickup_date"><?php _e('Data Ritiro', 'custom-rental-manager'); ?> *</label></th>
                    <td><input type="date" id="pickup_date" name="booking_data[pickup_date]" value="<?php echo esc_attr($booking_data['pickup_date']); ?>" class="crcm-datepicker" required /></td>
                </tr>
                <tr>
                    <th><label for="return_date"><?php _e('Data Riconsegna', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <input type="date" id="return_date" name="booking_data[return_date]" value="<?php echo esc_attr($booking_data['return_date']); ?>" class="crcm-datepicker" required />
                        <p class="description rental-days-display"><?php printf(__('Giorni di noleggio: %d', 'custom-rental-manager'), $booking_data['rental_days'] ?? 1); ?></p>
                        <input type="hidden" id="rental_days" name="booking_data[rental_days]" value="<?php echo esc_attr($booking_data['rental_days'] ?? 1); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label for="pickup_time"><?php _e('Orario Ritiro', 'custom-rental-manager'); ?></label></th>
                    <td>
                        <select id="pickup_time" name="booking_data[pickup_time]">
                            <?php for ($h = 8; $h <= 20; $h++): ?>
                                <?php for ($m = 0; $m < 60; $m += 30): ?>
                                    <?php $time = sprintf('%02d:%02d', $h, $m); ?>
                                    <option value="<?php echo $time; ?>" <?php selected($booking_data['pickup_time'], $time); ?>><?php echo $time; ?></option>
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
                                    <option value="<?php echo $time; ?>" <?php selected($booking_data['return_time'], $time); ?>><?php echo $time; ?></option>
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
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($booking_data['pickup_location'], $key); ?>>
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
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($booking_data['return_location'], $key); ?>>
                                    <?php echo esc_html($location['name']); ?> - <?php echo esc_html($location['address']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
        </div>
        
        <style>
        .crcm-section-header { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
        .crcm-section-header h4 { margin: 0; font-size: 16px; }
        .rental-days-display { font-weight: bold; color: #0073aa; }
        </style>
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
        
        <div class="crcm-customer-selection">
            
            <div class="crcm-section-header">
                <h4><?php _e('Selezione Cliente', 'custom-rental-manager'); ?></h4>
                <p class="description"><?php _e('Cerca e seleziona il cliente per questa prenotazione', 'custom-rental-manager'); ?></p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="customer_search"><?php _e('Cerca Cliente', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <input type="text" id="customer_search" placeholder="Digita nome, email o telefono..." class="regular-text" />
                        <input type="hidden" id="customer_id" name="booking_data[customer_id]" value="<?php echo esc_attr($selected_customer_id); ?>" />
                        
                        <div class="customer-search-results"></div>
                        
                        <div class="selected-customer-info" <?php echo $selected_customer ? '' : 'style="display:none;"'; ?>>
                            <?php if ($selected_customer): ?>
                                <div class="selected-customer">
                                    <div class="customer-name"><strong><?php echo esc_html($selected_customer->display_name); ?></strong></div>
                                    <div class="customer-email"><strong>Email:</strong> <?php echo esc_html($selected_customer->user_email); ?></div>
                                    <div class="customer-role"><strong>Ruolo:</strong> <?php echo esc_html(ucfirst(reset($selected_customer->roles))); ?></div>
                                    <?php $phone = get_user_meta($selected_customer->ID, 'phone', true);
                                    if ($phone): ?>
                                        <div class="customer-phone"><strong>Telefono:</strong> <?php echo esc_html($phone); ?></div>
                                    <?php endif; ?>
                                    <button type="button" class="button-link remove-customer">❌ Rimuovi selezione</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <p class="description"><?php _e('Solo utenti con ruolo "Rental Customer" possono essere selezionati', 'custom-rental-manager'); ?></p>
                        <button type="button" class="button create-customer-btn"><?php _e('Crea nuovo cliente →', 'custom-rental-manager'); ?></button>
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
        
        <div class="crcm-vehicle-selection">
            
            <div class="crcm-section-header">
                <h4><?php _e('Selezione Veicolo', 'custom-rental-manager'); ?></h4>
                <p class="description"><?php _e('Seleziona il veicolo per questa prenotazione. I dati verranno caricati dinamicamente.', 'custom-rental-manager'); ?></p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th><label for="vehicle_id"><?php _e('Veicolo', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <select id="vehicle_id" name="booking_data[vehicle_id]" required>
                            <option value=""><?php _e('Seleziona un veicolo...', 'custom-rental-manager'); ?></option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <?php
                                $vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
                                $pricing_data = get_post_meta($vehicle->ID, '_crcm_pricing_data', true);
                                $vehicle_type = $vehicle_data['vehicle_type'] ?? 'auto';
                                $daily_rate = $pricing_data['daily_rate'] ?? 0;
                                ?>
                                <option value="<?php echo esc_attr($vehicle->ID); ?>" <?php selected($selected_vehicle_id, $vehicle->ID); ?>>
                                    <?php echo esc_html($vehicle->post_title); ?>
                                    (<?php echo ucfirst($vehicle_type); ?> - €<?php echo number_format($daily_rate, 2); ?>/giorno)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <div class="vehicle-details-section">
                
                <div class="crcm-section-header">
                    <h4><?php _e('Dettagli Veicolo Selezionato', 'custom-rental-manager'); ?></h4>
                </div>
                
                <div class="vehicle-details-container">
                    <?php if ($selected_vehicle_id): ?>
                        <?php $this->render_vehicle_details($selected_vehicle_id); ?>
                    <?php else: ?>
                        <p class="description">Seleziona un veicolo per visualizzare i dettagli</p>
                    <?php endif; ?>
                </div>
                
            </div>
            
            <div class="availability-section">
                
                <div class="crcm-section-header">
                    <h4><?php _e('Controllo Disponibilità', 'custom-rental-manager'); ?></h4>
                </div>
                
                <div class="availability-status">
                    <p class="description">Seleziona un veicolo e le date per controllare la disponibilità</p>
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
        
        <div class="vehicle-info-card">
            
            <div class="vehicle-header">
                <h5><?php echo esc_html($vehicle->post_title); ?></h5>
            </div>
            
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
            
            <?php if (!empty($extras_data)): ?>
                <div class="vehicle-extras">
                    <h6><?php _e('Servizi Extra Disponibili', 'custom-rental-manager'); ?></h6>
                    <?php foreach ($extras_data as $extra): ?>
                        <div class="extra-item">• <?php echo esc_html($extra['name']); ?> 
                            <span class="extra-price">+€<?php echo number_format($extra['daily_rate'], 2); ?>/giorno</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($insurance_data) && !empty($insurance_data['premium']['enabled'])): ?>
                <div class="vehicle-insurance">
                    <h6><?php _e('Assicurazione Premium Disponibile', 'custom-rental-manager'); ?></h6>
                    <div class="insurance-info">
                        Franchigia €<?php echo number_format($insurance_data['premium']['deductible'], 0); ?> 
                        <span class="insurance-price">+€<?php echo number_format($insurance_data['premium']['daily_rate'], 2); ?>/giorno</span>
                    </div>
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
                        <?php if (!empty($misc_data['late_return_rule'])): ?>
                            <li>⏰ Giorno extra dopo le <?php echo $misc_data['late_return_time'] ?? '10:00'; ?></li>
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
     * ENHANCED: Advanced pricing meta box with complete integration
     */
    public function advanced_pricing_meta_box($post) {
        $booking_data = get_post_meta($post->ID, '_crcm_booking_data', true);
        $pricing_breakdown = get_post_meta($post->ID, '_crcm_pricing_breakdown', true);
        
        // Default values
        if (empty($pricing_breakdown)) {
            $pricing_breakdown = array(
                'base_total' => 0,
                'custom_rates_total' => 0,
                'extras_total' => 0,
                'insurance_total' => 0,
                'late_return_penalty' => 0,
                'tax_total' => 0,
                'discount_total' => 0,
                'final_total' => 0,
                'selected_extras' => array(),
                'selected_insurance' => 'basic',
            );
        }
        ?>
        
        <div class="crcm-advanced-pricing">
            
            <div class="crcm-section-header">
                <h4><?php _e('Prezzi e Servizi Avanzati', 'custom-rental-manager'); ?></h4>
                <p class="description"><?php _e('Configurazione completa con tariffe personalizzate, servizi extra, assicurazioni e penali', 'custom-rental-manager'); ?></p>
            </div>
            
            <!-- Servizi Extra -->
            <div class="pricing-section extras-section">
                <h5><?php _e('Servizi Extra', 'custom-rental-manager'); ?></h5>
                <div class="extras-container">
                    <p class="description">Seleziona un veicolo per visualizzare i servizi extra disponibili</p>
                </div>
            </div>
            
            <!-- Opzioni Assicurative -->
            <div class="pricing-section insurance-section">
                <h5><?php _e('Opzioni Assicurative', 'custom-rental-manager'); ?></h5>
                <div class="insurance-container">
                    <p class="description">Seleziona un veicolo per visualizzare le opzioni assicurative</p>
                </div>
            </div>
            
            <!-- Sconto Manuale -->
            <div class="pricing-section discount-section">
                <h5><?php _e('Sconto Manuale', 'custom-rental-manager'); ?></h5>
                <table class="form-table">
                    <tr>
                        <th><label for="manual_discount"><?php _e('Sconto (€)', 'custom-rental-manager'); ?></label></th>
                        <td>
                            <input type="number" id="manual_discount" name="pricing_breakdown[discount_total]" value="<?php echo esc_attr($pricing_breakdown['discount_total']); ?>" min="0" step="0.01" />
                            <p class="description"><?php _e('Sconto fisso in euro da applicare al totale', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="discount_reason"><?php _e('Motivo Sconto', 'custom-rental-manager'); ?></label></th>
                        <td>
                            <input type="text" id="discount_reason" name="pricing_breakdown[discount_reason]" value="<?php echo esc_attr($pricing_breakdown['discount_reason'] ?? ''); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Riepilogo Prezzi Avanzato -->
            <div class="pricing-section summary-section">
                <h5><?php _e('Riepilogo Prezzi Dettagliato', 'custom-rental-manager'); ?></h5>
                <table class="pricing-summary-table">
                    <tr class="base-rate-row">
                        <td><?php _e('Tariffa base', 'custom-rental-manager'); ?></td>
                        <td class="base-total">€0.00</td>
                    </tr>
                    <tr class="custom-rates-row">
                        <td><?php _e('Tariffe personalizzate', 'custom-rental-manager'); ?></td>
                        <td class="custom-rates-total">€0.00</td>
                    </tr>
                    <tr class="extras-row">
                        <td><?php _e('Servizi extra', 'custom-rental-manager'); ?></td>
                        <td class="extras-total">€0.00</td>
                    </tr>
                    <tr class="insurance-row">
                        <td><?php _e('Assicurazione premium', 'custom-rental-manager'); ?></td>
                        <td class="insurance-total">€0.00</td>
                    </tr>
                    <tr class="late-return-row">
                        <td><?php _e('Penale riconsegna tardiva', 'custom-rental-manager'); ?></td>
                        <td class="late-return-penalty">€0.00</td>
                    </tr>
                    <tr class="discount-row">
                        <td><?php _e('Sconto applicato', 'custom-rental-manager'); ?></td>
                        <td class="discount-total">-€0.00</td>
                    </tr>
                    <tr class="final-total-row">
                        <td><strong><?php _e('TOTALE', 'custom-rental-manager'); ?></strong></td>
                        <td><strong class="final-total">€0.00</strong></td>
                    </tr>
                </table>
                
                <!-- Hidden fields for data storage -->
                <input type="hidden" id="base_total" name="pricing_breakdown[base_total]" value="<?php echo esc_attr($pricing_breakdown['base_total']); ?>" />
                <input type="hidden" id="custom_rates_total" name="pricing_breakdown[custom_rates_total]" value="<?php echo esc_attr($pricing_breakdown['custom_rates_total']); ?>" />
                <input type="hidden" id="extras_total" name="pricing_breakdown[extras_total]" value="<?php echo esc_attr($pricing_breakdown['extras_total']); ?>" />
                <input type="hidden" id="insurance_total" name="pricing_breakdown[insurance_total]" value="<?php echo esc_attr($pricing_breakdown['insurance_total']); ?>" />
                <input type="hidden" id="late_return_penalty" name="pricing_breakdown[late_return_penalty]" value="<?php echo esc_attr($pricing_breakdown['late_return_penalty']); ?>" />
                <input type="hidden" id="final_total" name="pricing_breakdown[final_total]" value="<?php echo esc_attr($pricing_breakdown['final_total']); ?>" />
                
                <!-- Detailed calculation log for transparency -->
                <div class="calculation-log">
                    <h6><?php _e('Log Calcoli Dettagliato', 'custom-rental-manager'); ?></h6>
                    <div class="log-content">
                        <p class="description">I calcoli dettagliati appariranno qui quando selezioni un veicolo e imposti le date</p>
                    </div>
                </div>
            </div>
            
        </div>
        
        <style>
        .crcm-advanced-pricing .pricing-section { 
            margin-bottom: 25px; 
            padding: 15px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            background: #f9f9f9; 
        }
        .pricing-summary-table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .pricing-summary-table td { 
            padding: 8px 12px; 
            border-bottom: 1px solid #ddd; 
        }
        .final-total-row { 
            background-color: #0073aa; 
            color: white; 
            font-weight: bold; 
        }
        .calculation-log { 
            margin-top: 15px; 
            padding: 10px; 
            background: #fff; 
            border: 1px solid #ccc; 
            border-radius: 3px; 
        }
        .log-content { 
            font-family: monospace; 
            font-size: 12px; 
            max-height: 200px; 
            overflow-y: auto; 
        }
        </style>
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
        
        <table class="form-table">
            <tr>
                <th><label for="booking_status"><?php _e('Stato', 'custom-rental-manager'); ?></label></th>
                <td>
                    <select id="booking_status" name="booking_status">
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($booking_status, $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        
        <div class="status-info">
            <p><strong>In Attesa:</strong> Prenotazione creata</p>
            <p><strong>Confermata:</strong> Pagamento ricevuto</p>
            <p><strong>In Corso:</strong> Veicolo ritirato</p>
            <p><strong>Completata:</strong> Veicolo riconsegnato</p>
            <p><strong>Cancellata:</strong> Prenotazione annullata</p>
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
        
        <table class="form-table">
            <tr>
                <th><label for="booking_notes"><?php _e('Note Cliente', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea id="booking_notes" name="booking_notes" rows="4" class="large-text"><?php echo esc_textarea($notes); ?></textarea>
                    <p class="description"><?php _e('Note visibili al cliente', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="internal_notes"><?php _e('Note Interne', 'custom-rental-manager'); ?></label></th>
                <td>
                    <textarea id="internal_notes" name="internal_notes" rows="4" class="large-text"><?php echo esc_textarea($internal_notes); ?></textarea>
                    <p class="description"><?php _e('Note riservate allo staff', 'custom-rental-manager'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * ENHANCED: Auto-generate booking title with unique code
     */
    public function auto_generate_booking_title($post_id, $post) {
        // Only for new booking posts
        if ($post->post_type !== 'crcm_booking' || $post->post_status === 'auto-draft') {
            return;
        }
        
        // Skip if title is already set and not auto-generated
        if (!empty($post->post_title) && strpos($post->post_title, 'Prenotazione - ') !== 0) {
            return;
        }
        
        // Generate unique booking code
        $booking_code = $this->generate_booking_code();
        $new_title = 'Prenotazione - ' . $booking_code;
        
        // Update post title
        wp_update_post(array(
            'ID' => $post_id,
            'post_title' => $new_title
        ));
        
        // Store booking code in meta
        update_post_meta($post_id, '_crcm_booking_code', $booking_code);
    }
    
    /**
     * Generate unique booking code
     */
    private function generate_booking_code() {
        // Use existing function if available
        if (function_exists('crcm_get_next_booking_number')) {
            return crcm_get_next_booking_number();
        }
        
        // Fallback generation
        $prefix = 'CBR'; // Costabilerent
        $year = date('y');
        $month = date('m');
        $day = date('d');
        $sequence = wp_cache_get('crcm_booking_sequence', 'crcm');
        
        if (!$sequence) {
            global $wpdb;
            $last_booking = $wpdb->get_var(
                "SELECT meta_value FROM {$wpdb->postmeta} 
                 WHERE meta_key = '_crcm_booking_code' 
                 AND meta_value LIKE '{$prefix}{$year}{$month}%' 
                 ORDER BY meta_value DESC LIMIT 1"
            );
            
            if ($last_booking) {
                $sequence = intval(substr($last_booking, -3)) + 1;
            } else {
                $sequence = 1;
            }
        }
        
        wp_cache_set('crcm_booking_sequence', $sequence + 1, 'crcm', 3600);
        
        return $prefix . $year . $month . $day . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * ENHANCED: Advanced pricing calculation with custom rates and late return
     */
    public function ajax_calculate_advanced_pricing() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');
        
        $vehicle_id = intval($_POST['vehicle_id']);
        $pickup_date = sanitize_text_field($_POST['pickup_date']);
        $return_date = sanitize_text_field($_POST['return_date']);
        $pickup_time = sanitize_text_field($_POST['pickup_time']);
        $return_time = sanitize_text_field($_POST['return_time']);
        $selected_extras = isset($_POST['selected_extras']) ? array_map('intval', $_POST['selected_extras']) : array();
        $selected_insurance = sanitize_text_field($_POST['selected_insurance']);
        $manual_discount = floatval($_POST['manual_discount'] ?? 0);
        
        if (!$vehicle_id || !$pickup_date || !$return_date) {
            wp_send_json_error('Missing required parameters');
        }
        
        try {
            $calculation = $this->calculate_complete_pricing(
                $vehicle_id, 
                $pickup_date, 
                $return_date, 
                $pickup_time, 
                $return_time,
                $selected_extras,
                $selected_insurance,
                $manual_discount
            );
            
            wp_send_json_success($calculation);
            
        } catch (Exception $e) {
            wp_send_json_error('Calculation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate complete pricing with all components
     */
    private function calculate_complete_pricing($vehicle_id, $pickup_date, $return_date, $pickup_time, $return_time, $selected_extras = array(), $selected_insurance = 'basic', $manual_discount = 0) {
        $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
        $pricing_data = get_post_meta($vehicle_id, '_crcm_pricing_data', true);
        $extras_data = get_post_meta($vehicle_id, '_crcm_extras_data', true);
        $insurance_data = get_post_meta($vehicle_id, '_crcm_insurance_data', true);
        $misc_data = get_post_meta($vehicle_id, '_crcm_misc_data', true);
        
        $pickup = new DateTime($pickup_date . ' ' . $pickup_time);
        $return = new DateTime($return_date . ' ' . $return_time);
        $interval = $pickup->diff($return);
        $base_days = max(1, $interval->days);
        
        $calculation_log = array();
        $calculation_log[] = "=== CALCOLO PRICING AVANZATO ===";
        $calculation_log[] = "Veicolo ID: {$vehicle_id}";
        $calculation_log[] = "Periodo: {$pickup_date} {$pickup_time} → {$return_date} {$return_time}";
        $calculation_log[] = "Giorni base: {$base_days}";
        
        // 1. BASE RATE CALCULATION
        $base_rate = floatval($pricing_data['daily_rate'] ?? 0);
        $base_total = $base_rate * $base_days;
        $calculation_log[] = "Tariffa base: €{$base_rate}/giorno × {$base_days} = €{$base_total}";
        
        // 2. CUSTOM RATES CALCULATION
        $custom_rates_total = 0;
        if (!empty($pricing_data['custom_rates'])) {
            $calculation_log[] = "--- TARIFFE PERSONALIZZATE ---";
            
            foreach ($pricing_data['custom_rates'] as $rate) {
                if (empty($rate['extra_rate']) || $rate['extra_rate'] <= 0) continue;
                
                $applies = false;
                $affected_days = 0;
                
                switch ($rate['type']) {
                    case 'date_range':
                        if (!empty($rate['start_date']) && !empty($rate['end_date'])) {
                            $rate_start = new DateTime($rate['start_date']);
                            $rate_end = new DateTime($rate['end_date']);
                            
                            // Calculate overlap days
                            $overlap_start = max($pickup, $rate_start);
                            $overlap_end = min($return, $rate_end);
                            
                            if ($overlap_start < $overlap_end) {
                                $affected_days = $overlap_start->diff($overlap_end)->days;
                                $applies = true;
                            }
                        }
                        break;
                        
                    case 'weekends':
                        $current_date = clone $pickup;
                        while ($current_date < $return) {
                            $day_of_week = $current_date->format('N');
                            if ($day_of_week >= 6) { // Saturday (6) or Sunday (7)
                                $affected_days++;
                            }
                            $current_date->add(new DateInterval('P1D'));
                        }
                        $applies = $affected_days > 0;
                        break;
                }
                
                if ($applies && $affected_days > 0) {
                    $rate_total = $rate['extra_rate'] * $affected_days;
                    $custom_rates_total += $rate_total;
                    $calculation_log[] = "Tariffa '{$rate['name']}': €{$rate['extra_rate']} × {$affected_days} giorni = €{$rate_total}";
                }
            }
            
            $calculation_log[] = "Totale tariffe personalizzate: €{$custom_rates_total}";
        }
        
        // 3. LATE RETURN PENALTY
        $late_return_penalty = 0;
        if (!empty($misc_data['late_return_rule'])) {
            $late_return_time = $misc_data['late_return_time'] ?? '10:00';
            $return_time_obj = DateTime::createFromFormat('H:i', $return_time);
            $limit_time_obj = DateTime::createFromFormat('H:i', $late_return_time);
            
            if ($return_time_obj > $limit_time_obj) {
                $late_return_penalty = $base_rate; // One extra day
                $calculation_log[] = "--- PENALE RICONSEGNA TARDIVA ---";
                $calculation_log[] = "Riconsegna alle {$return_time} (limite: {$late_return_time})";
                $calculation_log[] = "Penale giorno extra: €{$late_return_penalty}";
            }
        }
        
        // 4. EXTRAS CALCULATION
        $extras_total = 0;
        if (!empty($selected_extras) && !empty($extras_data)) {
            $calculation_log[] = "--- SERVIZI EXTRA ---";
            
            foreach ($selected_extras as $extra_index) {
                if (isset($extras_data[$extra_index])) {
                    $extra = $extras_data[$extra_index];
                    $extra_total = $extra['daily_rate'] * $base_days;
                    $extras_total += $extra_total;
                    $calculation_log[] = "Extra '{$extra['name']}': €{$extra['daily_rate']} × {$base_days} = €{$extra_total}";
                }
            }
            
            $calculation_log[] = "Totale servizi extra: €{$extras_total}";
        }
        
        // 5. INSURANCE CALCULATION
        $insurance_total = 0;
        if ($selected_insurance === 'premium' && !empty($insurance_data['premium']['enabled'])) {
            $insurance_rate = floatval($insurance_data['premium']['daily_rate']);
            $insurance_total = $insurance_rate * $base_days;
            
            $calculation_log[] = "--- ASSICURAZIONE PREMIUM ---";
            $calculation_log[] = "Tariffa premium: €{$insurance_rate} × {$base_days} = €{$insurance_total}";
        }
        
        // 6. FINAL CALCULATION
        $subtotal = $base_total + $custom_rates_total + $extras_total + $insurance_total + $late_return_penalty;
        $final_total = max(0, $subtotal - $manual_discount);
        
        $calculation_log[] = "--- TOTALE FINALE ---";
        $calculation_log[] = "Subtotale: €{$subtotal}";
        if ($manual_discount > 0) {
            $calculation_log[] = "Sconto: -€{$manual_discount}";
        }
        $calculation_log[] = "TOTALE: €{$final_total}";
        
        return array(
            'base_total' => $base_total,
            'custom_rates_total' => $custom_rates_total,
            'extras_total' => $extras_total,
            'insurance_total' => $insurance_total,
            'late_return_penalty' => $late_return_penalty,
            'discount_total' => $manual_discount,
            'final_total' => $final_total,
            'rental_days' => $base_days,
            'calculation_log' => $calculation_log,
            'breakdown' => array(
                'base_rate' => $base_rate,
                'affected_days' => $base_days,
                'has_custom_rates' => $custom_rates_total > 0,
                'has_late_penalty' => $late_return_penalty > 0,
                'selected_extras_count' => count($selected_extras),
                'insurance_type' => $selected_insurance
            )
        );
    }
    
    /**
     * AJAX: Search customers with role filter
     */
    public function ajax_search_customers() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        
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
     * AJAX: Get vehicle booking data with complete integration
     */
    public function ajax_get_vehicle_booking_data() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');
        
        $vehicle_id = intval($_POST['vehicle_id']);
        
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
        
        $vehicle_id = intval($_POST['vehicle_id']);
        $pickup_date = sanitize_text_field($_POST['pickup_date']);
        $return_date = sanitize_text_field($_POST['return_date']);
        
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
     * Save booking meta data with enhanced validation
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
        
        // Generate and save booking number if not exists
        $booking_code = get_post_meta($post_id, '_crcm_booking_code', true);
        if (empty($booking_code)) {
            $booking_code = $this->generate_booking_code();
            update_post_meta($post_id, '_crcm_booking_code', $booking_code);
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
                return '🙋‍♂️ Customer';
            } elseif (in_array('crcm_manager', $roles)) {
                return '👨‍💼 Manager';
            } elseif (in_array('administrator', $roles)) {
                return '👑 Admin';
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
        $new_columns['crcm_booking_code'] = __('Booking Code', 'custom-rental-manager');
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
        $booking_code = get_post_meta($post_id, '_crcm_booking_code', true);
        
        switch ($column) {
            case 'crcm_booking_code':
                if ($booking_code) {
                    echo '<strong>' . esc_html($booking_code) . '</strong>';
                } else {
                    echo '<em>Non generato</em>';
                }
                break;
                
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
                } else {
                    echo '<em>Da calcolare</em>';
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
                
                echo '<span class="crcm-status-badge status-' . esc_attr($status) . '">' . esc_html($label) . '</span>';
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
            .crcm-booking-details { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .crcm-section-header { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #0073aa; }
            .crcm-section-header h4 { margin: 0; color: #0073aa; font-size: 16px; }
            .vehicle-info-card { background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-top: 15px; }
            .vehicle-specs span { display: inline-block; margin-right: 15px; padding: 3px 8px; background: #e1f5fe; border-radius: 12px; font-size: 12px; }
            .customer-search-results { background: #fff; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; margin-top: 5px; }
            .customer-result-item { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; }
            .customer-result-item:hover { background: #f0f0f0; }
            .selected-customer { background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 3px; margin-top: 10px; }
            .pricing-section { margin-bottom: 20px; }
            .pricing-summary-table { width: 100%; }
            .pricing-summary-table td { padding: 8px 0; border-bottom: 1px solid #ddd; }
            .final-total-row td { font-weight: bold; font-size: 18px; color: #0073aa; }
            .crcm-status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
            .status-pending { background: #fff3cd; color: #856404; }
            .status-confirmed { background: #d4edda; color: #155724; }
            .status-active { background: #d1ecf1; color: #0c5460; }
            .status-completed { background: #e2e3e5; color: #383d41; }
            .status-cancelled { background: #f8d7da; color: #721c24; }
            .calculation-log { font-family: monospace; font-size: 12px; background: #f8f9fa; padding: 10px; border-left: 4px solid #0073aa; }
            </style>
            <?php
        }
    }
}

// Initialize booking manager
new CRCM_Booking_Manager();
