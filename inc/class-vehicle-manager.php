<?php
/**
 * Vehicle Manager Class - OPTIMIZED & CLEANED
 * 
 * Handles all vehicle-related operations with custom user roles,
 * dynamic vehicle types, and advanced availability management.
 * Removed taxonomies, editor, gallery and fixed all issues.
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
     * Vehicle types configuration
     */
    private $vehicle_types = array(
        'auto' => array(
            'name' => 'Auto',
            'slug' => 'auto',
            'fields' => array('seats', 'luggage', 'transmission', 'fuel_type', 'quantity'),
            'features' => array(
                'air_conditioning' => 'Air Conditioning',
                'gps_navigation' => 'GPS Navigation', 
                'bluetooth' => 'Bluetooth',
                'usb_charging' => 'USB Charging',
                'storage_box' => 'Storage Box',
                'phone_holder' => 'Phone Holder',
                'gps_security' => 'GPS Security System',
                'abs_brakes' => 'ABS Brakes',
                'led_lights' => 'LED Lights'
            )
        ),
        'scooter' => array(
            'name' => 'Scooter',
            'slug' => 'scooter',
            'fields' => array('fuel_type', 'engine_size', 'quantity'),
            'features' => array(
                'helmet_included' => '2 Helmet Included',
                'top_case' => 'Top Case'
            )
        )
    );
    
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
        add_action('init', array($this, 'create_user_roles'));
        add_action('init', array($this, 'remove_vehicle_supports'), 20);
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_vehicle_meta'));
        add_action('wp_ajax_crcm_search_vehicles', array($this, 'ajax_search_vehicles'));
        add_action('wp_ajax_nopriv_crcm_search_vehicles', array($this, 'ajax_search_vehicles'));
        add_filter('manage_crcm_vehicle_posts_columns', array($this, 'vehicle_columns'));
        add_action('manage_crcm_vehicle_posts_custom_column', array($this, 'vehicle_column_content'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_crcm_get_vehicle_fields', array($this, 'ajax_get_vehicle_fields'));
    }
    
    /**
     * Create custom user roles
     */
    public function create_user_roles() {
        // Customer role for rental clients
        if (!get_role('crcm_customer')) {
            add_role('crcm_customer', __('Rental Customer', 'custom-rental-manager'), array(
                'read' => true,
                'crcm_view_bookings' => true,
                'crcm_manage_profile' => true,
            ));
        }
        
        // Manager role for rental operations
        if (!get_role('crcm_manager')) {
            add_role('crcm_manager', __('Rental Manager', 'custom-rental-manager'), array(
                'read' => true,
                'edit_posts' => true,
                'edit_others_posts' => true,
                'publish_posts' => true,
                'manage_categories' => true,
                'crcm_manage_vehicles' => true,
                'crcm_manage_bookings' => true,
                'crcm_manage_customers' => true,
                'crcm_view_reports' => true,
            ));
        }
        
        // Add capabilities to administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('crcm_manage_vehicles');
            $admin->add_cap('crcm_manage_bookings');
            $admin->add_cap('crcm_manage_customers');
            $admin->add_cap('crcm_view_reports');
        }
    }
    
    /**
     * Remove editor and other supports from vehicle post type
     */
    public function remove_vehicle_supports() {
        remove_post_type_support('crcm_vehicle', 'editor');
        remove_post_type_support('crcm_vehicle', 'excerpt');
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
            __('Tariffe', 'custom-rental-manager'),
            array($this, 'pricing_meta_box'),
            'crcm_vehicle',
            'normal',
            'high'
        );
        
        add_meta_box(
            'crcm_vehicle_features',
            __('Features & Specifications', 'custom-rental-manager'),
            array($this, 'features_meta_box'),
            'crcm_vehicle',
            'normal',
            'default'
        );
        
        add_meta_box(
            'crcm_vehicle_availability',
            __('Disponibilità', 'custom-rental-manager'),
            array($this, 'availability_meta_box'),
            'crcm_vehicle',
            'normal',
            'default'
        );
        
        add_meta_box(
            'crcm_vehicle_extras',
            __('Servizi Extra', 'custom-rental-manager'),
            array($this, 'extras_meta_box'),
            'crcm_vehicle',
            'side',
            'default'
        );
        
        add_meta_box(
            'crcm_vehicle_insurance',
            __('Assicurazioni', 'custom-rental-manager'),
            array($this, 'insurance_meta_box'),
            'crcm_vehicle',
            'side',
            'default'
        );
        
        add_meta_box(
            'crcm_vehicle_misc',
            __('Varie', 'custom-rental-manager'),
            array($this, 'misc_meta_box'),
            'crcm_vehicle',
            'side',
            'default'
        );
    }
    
    /**
     * Vehicle details meta box with dynamic fields
     */
    public function vehicle_details_meta_box($post) {
        wp_nonce_field('crcm_vehicle_meta_nonce', 'crcm_vehicle_meta_nonce_field');
        
        $vehicle_data = get_post_meta($post->ID, '_crcm_vehicle_data', true);
        $selected_type = isset($vehicle_data['vehicle_type']) ? $vehicle_data['vehicle_type'] : 'auto';
        
        // Default values
        if (empty($vehicle_data)) {
            $vehicle_data = array(
                'vehicle_type' => 'auto',
                'seats' => 2,
                'luggage' => 1,
                'transmission' => 'manual',
                'fuel_type' => 'gasoline',
                'engine_size' => '50cc',
                'quantity' => 1,
            );
        }
        ?>
        
        <div class="crcm-vehicle-details-tabs">
            <!-- Vehicle Type Selection -->
            <table class="form-table crcm-main-table">
                <tr>
                    <th><label for="vehicle_type"><?php _e('Tipo di Veicolo', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <select id="vehicle_type" name="vehicle_data[vehicle_type]" class="crcm-vehicle-type-selector" required>
                            <?php foreach ($this->vehicle_types as $type_key => $type_data): ?>
                                <option value="<?php echo esc_attr($type_key); ?>" <?php selected($selected_type, $type_key); ?>>
                                    <?php echo esc_html($type_data['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <!-- Dynamic Fields Container -->
            <div id="crcm-dynamic-fields">
                <?php $this->render_vehicle_fields($selected_type, $vehicle_data); ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#vehicle_type').on('change', function() {
                const vehicleType = $(this).val();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'crcm_get_vehicle_fields',
                        vehicle_type: vehicleType,
                        nonce: '<?php echo wp_create_nonce('crcm_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#crcm-dynamic-fields').html(response.data);
                            // Trigger features update
                            $('#crcm-dynamic-fields').trigger('vehicle_type_changed', [vehicleType]);
                        }
                    }
                });
            });
        });
        </script>
        
        <style>
        .crcm-main-table {
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            padding-bottom: 20px;
        }
        
        .crcm-vehicle-type-selector {
            width: 200px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        #crcm-dynamic-fields .form-table {
            margin-top: 0;
        }
        </style>
        <?php
    }
    
    /**
     * Render vehicle fields based on type
     */
    private function render_vehicle_fields($vehicle_type, $vehicle_data) {
        $type_config = $this->vehicle_types[$vehicle_type];
        ?>
        
        <table class="form-table">
            <?php if (in_array('seats', $type_config['fields'])): ?>
                <tr class="crcm-field-auto">
                    <th><label for="seats"><?php _e('Numero Posti', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <select id="seats" name="vehicle_data[seats]" required>
                            <option value="2" <?php selected(isset($vehicle_data['seats']) ? $vehicle_data['seats'] : '', '2'); ?>>2</option>
                            <option value="4" <?php selected(isset($vehicle_data['seats']) ? $vehicle_data['seats'] : '', '4'); ?>>4</option>
                            <option value="5" <?php selected(isset($vehicle_data['seats']) ? $vehicle_data['seats'] : '', '5'); ?>>5</option>
                            <option value="7" <?php selected(isset($vehicle_data['seats']) ? $vehicle_data['seats'] : '', '7'); ?>>7</option>
                            <option value="8" <?php selected(isset($vehicle_data['seats']) ? $vehicle_data['seats'] : '', '8'); ?>>8</option>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>
            
            <?php if (in_array('luggage', $type_config['fields'])): ?>
                <tr class="crcm-field-auto">
                    <th><label for="luggage"><?php _e('Numero Bagagli', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <select id="luggage" name="vehicle_data[luggage]" required>
                            <option value="1" <?php selected(isset($vehicle_data['luggage']) ? $vehicle_data['luggage'] : '', '1'); ?>>1</option>
                            <option value="2" <?php selected(isset($vehicle_data['luggage']) ? $vehicle_data['luggage'] : '', '2'); ?>>2</option>
                            <option value="3" <?php selected(isset($vehicle_data['luggage']) ? $vehicle_data['luggage'] : '', '3'); ?>>3</option>
                            <option value="4" <?php selected(isset($vehicle_data['luggage']) ? $vehicle_data['luggage'] : '', '4'); ?>>4</option>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>
            
            <?php if (in_array('transmission', $type_config['fields'])): ?>
                <tr class="crcm-field-auto">
                    <th><label for="transmission"><?php _e('Cambio', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <select id="transmission" name="vehicle_data[transmission]" required>
                            <option value="manual" <?php selected(isset($vehicle_data['transmission']) ? $vehicle_data['transmission'] : '', 'manual'); ?>>
                                <?php _e('Manuale', 'custom-rental-manager'); ?>
                            </option>
                            <option value="automatic" <?php selected(isset($vehicle_data['transmission']) ? $vehicle_data['transmission'] : '', 'automatic'); ?>>
                                <?php _e('Automatico', 'custom-rental-manager'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>
            
            <?php if (in_array('fuel_type', $type_config['fields'])): ?>
                <tr>
                    <th><label for="fuel_type"><?php _e('Carburante', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <select id="fuel_type" name="vehicle_data[fuel_type]" required>
                            <?php if ($vehicle_type === 'auto'): ?>
                                <option value="gasoline" <?php selected(isset($vehicle_data['fuel_type']) ? $vehicle_data['fuel_type'] : '', 'gasoline'); ?>>
                                    <?php _e('Benzina', 'custom-rental-manager'); ?>
                                </option>
                                <option value="diesel" <?php selected(isset($vehicle_data['fuel_type']) ? $vehicle_data['fuel_type'] : '', 'diesel'); ?>>
                                    <?php _e('Diesel', 'custom-rental-manager'); ?>
                                </option>
                                <option value="electric" <?php selected(isset($vehicle_data['fuel_type']) ? $vehicle_data['fuel_type'] : '', 'electric'); ?>>
                                    <?php _e('Elettrico', 'custom-rental-manager'); ?>
                                </option>
                                <option value="hybrid" <?php selected(isset($vehicle_data['fuel_type']) ? $vehicle_data['fuel_type'] : '', 'hybrid'); ?>>
                                    <?php _e('Ibrido', 'custom-rental-manager'); ?>
                                </option>
                            <?php else: ?>
                                <option value="gasoline" <?php selected(isset($vehicle_data['fuel_type']) ? $vehicle_data['fuel_type'] : '', 'gasoline'); ?>>
                                    <?php _e('Benzina', 'custom-rental-manager'); ?>
                                </option>
                                <option value="electric" <?php selected(isset($vehicle_data['fuel_type']) ? $vehicle_data['fuel_type'] : '', 'electric'); ?>>
                                    <?php _e('Elettrico', 'custom-rental-manager'); ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>
            
            <?php if (in_array('engine_size', $type_config['fields'])): ?>
                <tr class="crcm-field-scooter">
                    <th><label for="engine_size"><?php _e('Cilindrata', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <select id="engine_size" name="vehicle_data[engine_size]" required>
                            <option value="50cc" <?php selected(isset($vehicle_data['engine_size']) ? $vehicle_data['engine_size'] : '', '50cc'); ?>>50cc</option>
                            <option value="125cc" <?php selected(isset($vehicle_data['engine_size']) ? $vehicle_data['engine_size'] : '', '125cc'); ?>>125cc</option>
                            <option value="150cc" <?php selected(isset($vehicle_data['engine_size']) ? $vehicle_data['engine_size'] : '', '150cc'); ?>>150cc</option>
                            <option value="200cc" <?php selected(isset($vehicle_data['engine_size']) ? $vehicle_data['engine_size'] : '', '200cc'); ?>>200cc</option>
                            <option value="300cc" <?php selected(isset($vehicle_data['engine_size']) ? $vehicle_data['engine_size'] : '', '300cc'); ?>>300cc</option>
                            <option value="400cc" <?php selected(isset($vehicle_data['engine_size']) ? $vehicle_data['engine_size'] : '', '400cc'); ?>>400cc</option>
                            <option value="500cc" <?php selected(isset($vehicle_data['engine_size']) ? $vehicle_data['engine_size'] : '', '500cc'); ?>>500cc</option>
                            <option value="750cc" <?php selected(isset($vehicle_data['engine_size']) ? $vehicle_data['engine_size'] : '', '750cc'); ?>>750cc</option>
                            <option value="1000cc" <?php selected(isset($vehicle_data['engine_size']) ? $vehicle_data['engine_size'] : '', '1000cc'); ?>>1000cc</option>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>
            
            <?php if (in_array('quantity', $type_config['fields'])): ?>
                <tr>
                    <th><label for="quantity"><?php _e('Quantità Disponibile', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <input type="number" id="quantity" name="vehicle_data[quantity]" 
                               value="<?php echo esc_attr(isset($vehicle_data['quantity']) ? $vehicle_data['quantity'] : '1'); ?>" 
                               min="1" max="50" required />
                        <p class="description"><?php _e('Numero totale di unità disponibili per questo veicolo', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        <?php
    }
    
    /**
     * Enhanced pricing meta box with custom rates
     */
    public function pricing_meta_box($post) {
        $pricing_data = get_post_meta($post->ID, '_crcm_pricing_data', true);
        
        // Default values
        if (empty($pricing_data)) {
            $pricing_data = array(
                'daily_rate' => 0,
                'custom_rates' => array(),
            );
        }
        ?>
        
        <div class="crcm-pricing-container">
            <!-- Base Rate -->
            <table class="form-table">
                <tr>
                    <th><label for="daily_rate"><?php _e('Prezzo Base Giornaliero (€)', 'custom-rental-manager'); ?> *</label></th>
                    <td>
                        <input type="number" id="daily_rate" name="pricing_data[daily_rate]" 
                               value="<?php echo esc_attr($pricing_data['daily_rate']); ?>" 
                               step="0.01" min="0" required />
                        <p class="description"><?php _e('Tariffa base applicata quando non ci sono tariffe personalizzate', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
            </table>
            
            <!-- Custom Rates -->
            <div class="crcm-custom-rates">
                <h3><?php _e('Tariffe Personalizzate', 'custom-rental-manager'); ?></h3>
                <p class="description"><?php _e('Aggiungi tariffe extra per periodi specifici. Le tariffe si sommano al prezzo base.', 'custom-rental-manager'); ?></p>
                
                <div id="custom-rates-container">
                    <?php
                    if (!empty($pricing_data['custom_rates'])) {
                        foreach ($pricing_data['custom_rates'] as $index => $rate) {
                            $this->render_custom_rate_row($index, $rate);
                        }
                    }
                    ?>
                </div>
                
                <button type="button" id="add-custom-rate" class="button button-secondary">
                    <?php _e('Aggiungi Tariffa Personalizzata', 'custom-rental-manager'); ?>
                </button>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let rateIndex = <?php echo !empty($pricing_data['custom_rates']) ? count($pricing_data['custom_rates']) : 0; ?>;
            
            $('#add-custom-rate').on('click', function() {
                const rateHtml = `
                    <div class="crcm-custom-rate-row" data-index="${rateIndex}">
                        <table class="form-table">
                            <tr>
                                <td style="width: 200px;">
                                    <label><?php _e('Nome Tariffa', 'custom-rental-manager'); ?></label>
                                    <input type="text" name="pricing_data[custom_rates][${rateIndex}][name]" placeholder="Es: Agosto 2025" />
                                </td>
                                <td style="width: 120px;">
                                    <label><?php _e('Tipo', 'custom-rental-manager'); ?></label>
                                    <select name="pricing_data[custom_rates][${rateIndex}][type]" class="rate-type-selector">
                                        <option value="date_range"><?php _e('Periodo', 'custom-rental-manager'); ?></option>
                                        <option value="weekends"><?php _e('Fine Settimana', 'custom-rental-manager'); ?></option>
                                        <option value="specific_days"><?php _e('Giorni Specifici', 'custom-rental-manager'); ?></option>
                                    </select>
                                </td>
                                <td class="date-fields" style="width: 200px;">
                                    <label><?php _e('Data Inizio', 'custom-rental-manager'); ?></label>
                                    <input type="date" name="pricing_data[custom_rates][${rateIndex}][start_date]" />
                                </td>
                                <td class="date-fields" style="width: 200px;">
                                    <label><?php _e('Data Fine', 'custom-rental-manager'); ?></label>
                                    <input type="date" name="pricing_data[custom_rates][${rateIndex}][end_date]" />
                                </td>
                                <td style="width: 120px;">
                                    <label><?php _e('Extra (€)', 'custom-rental-manager'); ?></label>
                                    <input type="number" name="pricing_data[custom_rates][${rateIndex}][extra_rate]" step="0.01" min="0" />
                                </td>
                                <td style="width: 50px;">
                                    <label>&nbsp;</label>
                                    <button type="button" class="button button-link-delete remove-rate"><?php _e('Rimuovi', 'custom-rental-manager'); ?></button>
                                </td>
                            </tr>
                        </table>
                    </div>
                `;
                
                $('#custom-rates-container').append(rateHtml);
                rateIndex++;
            });
            
            $(document).on('click', '.remove-rate', function() {
                $(this).closest('.crcm-custom-rate-row').remove();
            });
            
            $(document).on('change', '.rate-type-selector', function() {
                const $row = $(this).closest('.crcm-custom-rate-row');
                const type = $(this).val();
                
                if (type === 'weekends') {
                    $row.find('.date-fields').hide();
                } else {
                    $row.find('.date-fields').show();
                }
            });
        });
        </script>
        
        <style>
        .crcm-custom-rates {
            margin-top: 20px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .crcm-custom-rate-row {
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .crcm-custom-rate-row table {
            margin: 0;
        }
        
        .crcm-custom-rate-row td {
            padding: 5px;
            vertical-align: top;
        }
        
        .crcm-custom-rate-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .crcm-custom-rate-row input,
        .crcm-custom-rate-row select {
            width: 100%;
        }
        </style>
        <?php
    }
    
    /**
     * Render custom rate row
     */
    private function render_custom_rate_row($index, $rate) {
        ?>
        <div class="crcm-custom-rate-row" data-index="<?php echo $index; ?>">
            <table class="form-table">
                <tr>
                    <td style="width: 200px;">
                        <label><?php _e('Nome Tariffa', 'custom-rental-manager'); ?></label>
                        <input type="text" name="pricing_data[custom_rates][<?php echo $index; ?>][name]" 
                               value="<?php echo esc_attr($rate['name'] ?? ''); ?>" placeholder="Es: Agosto 2025" />
                    </td>
                    <td style="width: 120px;">
                        <label><?php _e('Tipo', 'custom-rental-manager'); ?></label>
                        <select name="pricing_data[custom_rates][<?php echo $index; ?>][type]" class="rate-type-selector">
                            <option value="date_range" <?php selected($rate['type'] ?? '', 'date_range'); ?>><?php _e('Periodo', 'custom-rental-manager'); ?></option>
                            <option value="weekends" <?php selected($rate['type'] ?? '', 'weekends'); ?>><?php _e('Fine Settimana', 'custom-rental-manager'); ?></option>
                            <option value="specific_days" <?php selected($rate['type'] ?? '', 'specific_days'); ?>><?php _e('Giorni Specifici', 'custom-rental-manager'); ?></option>
                        </select>
                    </td>
                    <td class="date-fields" style="width: 200px; <?php echo ($rate['type'] ?? '') === 'weekends' ? 'display: none;' : ''; ?>">
                        <label><?php _e('Data Inizio', 'custom-rental-manager'); ?></label>
                        <input type="date" name="pricing_data[custom_rates][<?php echo $index; ?>][start_date]" 
                               value="<?php echo esc_attr($rate['start_date'] ?? ''); ?>" />
                    </td>
                    <td class="date-fields" style="width: 200px; <?php echo ($rate['type'] ?? '') === 'weekends' ? 'display: none;' : ''; ?>">
                        <label><?php _e('Data Fine', 'custom-rental-manager'); ?></label>
                        <input type="date" name="pricing_data[custom_rates][<?php echo $index; ?>][end_date]" 
                               value="<?php echo esc_attr($rate['end_date'] ?? ''); ?>" />
                    </td>
                    <td style="width: 120px;">
                        <label><?php _e('Extra (€)', 'custom-rental-manager'); ?></label>
                        <input type="number" name="pricing_data[custom_rates][<?php echo $index; ?>][extra_rate]" 
                               value="<?php echo esc_attr($rate['extra_rate'] ?? ''); ?>" step="0.01" min="0" />
                    </td>
                    <td style="width: 50px;">
                        <label>&nbsp;</label>
                        <button type="button" class="button button-link-delete remove-rate"><?php _e('Rimuovi', 'custom-rental-manager'); ?></button>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Dynamic features meta box - FIXED: Now properly detects vehicle type
     */
    public function features_meta_box($post) {
        $features = get_post_meta($post->ID, '_crcm_vehicle_features', true);
        $vehicle_data = get_post_meta($post->ID, '_crcm_vehicle_data', true);
        $vehicle_type = isset($vehicle_data['vehicle_type']) ? $vehicle_data['vehicle_type'] : 'auto';
        
        if (!is_array($features)) {
            $features = array();
        }
        
        $available_features = $this->vehicle_types[$vehicle_type]['features'];
        ?>
        
        <div class="crcm-features-container" id="crcm-features-container" data-vehicle-type="<?php echo esc_attr($vehicle_type); ?>">
            <div class="crcm-features-grid">
                <?php foreach ($available_features as $key => $label): ?>
                    <div class="crcm-feature-item">
                        <label>
                            <input type="checkbox" name="vehicle_features[]" value="<?php echo esc_attr($key); ?>" 
                                   <?php checked(in_array($key, $features)); ?> />
                            <span class="crcm-feature-label"><?php echo esc_html($label); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Listen for vehicle type changes from the dynamic fields section
            $(document).on('vehicle_type_changed', '#crcm-dynamic-fields', function(e, vehicleType) {
                updateFeatures(vehicleType);
            });
            
            function updateFeatures(vehicleType) {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'crcm_get_vehicle_features',
                        vehicle_type: vehicleType,
                        nonce: '<?php echo wp_create_nonce('crcm_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#crcm-features-container .crcm-features-grid').html(response.data);
                            $('#crcm-features-container').attr('data-vehicle-type', vehicleType);
                        }
                    }
                });
            }
        });
        </script>
        
        <style>
        .crcm-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .crcm-feature-item {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .crcm-feature-item label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .crcm-feature-item input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .crcm-feature-label {
            font-weight: 500;
        }
        </style>
        <?php
    }
    
    /**
     * Advanced availability meta box - FIXED: JavaScript now works properly
     */
    public function availability_meta_box($post) {
        $availability_data = get_post_meta($post->ID, '_crcm_availability_data', true);
        $vehicle_data = get_post_meta($post->ID, '_crcm_vehicle_data', true);
        $max_quantity = isset($vehicle_data['quantity']) ? intval($vehicle_data['quantity']) : 1;
        
        if (empty($availability_data)) {
            $availability_data = array();
        }
        ?>
        
        <div class="crcm-availability-container">
            <p class="description">
                <?php printf(__('Gestisci la disponibilità del veicolo. Quantità massima: %d unità', 'custom-rental-manager'), $max_quantity); ?>
            </p>
            
            <div id="availability-rules-container">
                <?php
                if (!empty($availability_data)) {
                    foreach ($availability_data as $index => $rule) {
                        $this->render_availability_rule($index, $rule, $max_quantity);
                    }
                }
                ?>
            </div>
            
            <button type="button" id="add-availability-rule" class="button button-secondary">
                <?php _e('Aggiungi Regola Disponibilità', 'custom-rental-manager'); ?>
            </button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let ruleIndex = <?php echo !empty($availability_data) ? count($availability_data) : 0; ?>;
            const maxQuantity = <?php echo $max_quantity; ?>;
            
            // FIXED: Properly bind click event
            $('#add-availability-rule').off('click').on('click', function(e) {
                e.preventDefault();
                
                let quantityOptions = '';
                for (let i = 1; i <= maxQuantity; i++) {
                    quantityOptions += `<option value="${i}">${i}</option>`;
                }
                quantityOptions += `<option value="all"><?php _e('Tutte', 'custom-rental-manager'); ?></option>`;
                
                const ruleHtml = `
                    <div class="crcm-availability-rule" data-index="${ruleIndex}">
                        <table class="form-table">
                            <tr>
                                <td style="width: 200px;">
                                    <label><?php _e('Nome Regola', 'custom-rental-manager'); ?></label>
                                    <input type="text" name="availability_data[${ruleIndex}][name]" placeholder="Es: Manutenzione" />
                                </td>
                                <td style="width: 150px;">
                                    <label><?php _e('Data Inizio', 'custom-rental-manager'); ?></label>
                                    <input type="date" name="availability_data[${ruleIndex}][start_date]" required />
                                </td>
                                <td style="width: 150px;">
                                    <label><?php _e('Data Fine', 'custom-rental-manager'); ?></label>
                                    <input type="date" name="availability_data[${ruleIndex}][end_date]" required />
                                </td>
                                <td style="width: 150px;">
                                    <label><?php _e('Quantità da Rimuovere', 'custom-rental-manager'); ?></label>
                                    <select name="availability_data[${ruleIndex}][quantity_to_remove]">
                                        ${quantityOptions}
                                    </select>
                                </td>
                                <td style="width: 50px;">
                                    <label>&nbsp;</label>
                                    <button type="button" class="button button-link-delete remove-rule"><?php _e('Rimuovi', 'custom-rental-manager'); ?></button>
                                </td>
                            </tr>
                        </table>
                    </div>
                `;
                
                $('#availability-rules-container').append(ruleHtml);
                ruleIndex++;
            });
            
            // FIXED: Use event delegation for dynamically added elements
            $(document).on('click', '.remove-rule', function(e) {
                e.preventDefault();
                $(this).closest('.crcm-availability-rule').remove();
            });
        });
        </script>
        
        <style>
        .crcm-availability-container {
            padding: 10px 0;
        }
        
        .crcm-availability-rule {
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .crcm-availability-rule table {
            margin: 0;
        }
        
        .crcm-availability-rule td {
            padding: 5px;
            vertical-align: top;
        }
        
        .crcm-availability-rule label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .crcm-availability-rule input,
        .crcm-availability-rule select {
            width: 100%;
        }
        
        #add-availability-rule {
            margin-top: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Render availability rule
     */
    private function render_availability_rule($index, $rule, $max_quantity) {
        ?>
        <div class="crcm-availability-rule" data-index="<?php echo $index; ?>">
            <table class="form-table">
                <tr>
                    <td style="width: 200px;">
                        <label><?php _e('Nome Regola', 'custom-rental-manager'); ?></label>
                        <input type="text" name="availability_data[<?php echo $index; ?>][name]" 
                               value="<?php echo esc_attr($rule['name'] ?? ''); ?>" placeholder="Es: Manutenzione" />
                    </td>
                    <td style="width: 150px;">
                        <label><?php _e('Data Inizio', 'custom-rental-manager'); ?></label>
                        <input type="date" name="availability_data[<?php echo $index; ?>][start_date]" 
                               value="<?php echo esc_attr($rule['start_date'] ?? ''); ?>" required />
                    </td>
                    <td style="width: 150px;">
                        <label><?php _e('Data Fine', 'custom-rental-manager'); ?></label>
                        <input type="date" name="availability_data[<?php echo $index; ?>][end_date]" 
                               value="<?php echo esc_attr($rule['end_date'] ?? ''); ?>" required />
                    </td>
                    <td style="width: 150px;">
                        <label><?php _e('Quantità da Rimuovere', 'custom-rental-manager'); ?></label>
                        <select name="availability_data[<?php echo $index; ?>][quantity_to_remove]">
                            <?php for ($i = 1; $i <= $max_quantity; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($rule['quantity_to_remove'] ?? '', $i); ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                            <option value="all" <?php selected($rule['quantity_to_remove'] ?? '', 'all'); ?>>
                                <?php _e('Tutte', 'custom-rental-manager'); ?>
                            </option>
                        </select>
                    </td>
                    <td style="width: 50px;">
                        <label>&nbsp;</label>
                        <button type="button" class="button button-link-delete remove-rule"><?php _e('Rimuovi', 'custom-rental-manager'); ?></button>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Empty meta boxes for future implementation
     */
    public function extras_meta_box($post) {
        echo '<p>' . __('Servizi extra verranno implementati qui.', 'custom-rental-manager') . '</p>';
    }
    
    public function insurance_meta_box($post) {
        echo '<p>' . __('Opzioni assicurative verranno implementate qui.', 'custom-rental-manager') . '</p>';
    }
    
    public function misc_meta_box($post) {
        echo '<p>' . __('Altre opzioni verranno implementate qui.', 'custom-rental-manager') . '</p>';
    }
    
    /**
     * AJAX get vehicle fields
     */
    public function ajax_get_vehicle_fields() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');
        
        $vehicle_type = sanitize_text_field($_POST['vehicle_type']);
        $vehicle_data = array(); // Default empty data
        
        ob_start();
        $this->render_vehicle_fields($vehicle_type, $vehicle_data);
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }
    
    /**
     * AJAX get vehicle features based on type
     */
    public function ajax_get_vehicle_features() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');
        
        $vehicle_type = sanitize_text_field($_POST['vehicle_type']);
        $available_features = $this->vehicle_types[$vehicle_type]['features'];
        
        ob_start();
        foreach ($available_features as $key => $label):
        ?>
            <div class="crcm-feature-item">
                <label>
                    <input type="checkbox" name="vehicle_features[]" value="<?php echo esc_attr($key); ?>" />
                    <span class="crcm-feature-label"><?php echo esc_html($label); ?></span>
                </label>
            </div>
        <?php
        endforeach;
        $html = ob_get_clean();
        
        wp_send_json_success($html);
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
            $pricing_data['daily_rate'] = floatval($_POST['pricing_data']['daily_rate']);
            
            // Save custom rates
            if (isset($_POST['pricing_data']['custom_rates'])) {
                $custom_rates = array();
                foreach ($_POST['pricing_data']['custom_rates'] as $rate) {
                    if (!empty($rate['name']) && !empty($rate['extra_rate'])) {
                        $custom_rates[] = array(
                            'name' => sanitize_text_field($rate['name']),
                            'type' => sanitize_text_field($rate['type']),
                            'start_date' => sanitize_text_field($rate['start_date'] ?? ''),
                            'end_date' => sanitize_text_field($rate['end_date'] ?? ''),
                            'extra_rate' => floatval($rate['extra_rate']),
                        );
                    }
                }
                $pricing_data['custom_rates'] = $custom_rates;
            }
            
            update_post_meta($post_id, '_crcm_pricing_data', $pricing_data);
        }
        
        // Save availability data
        if (isset($_POST['availability_data'])) {
            $availability_data = array();
            foreach ($_POST['availability_data'] as $rule) {
                if (!empty($rule['name']) && !empty($rule['start_date']) && !empty($rule['end_date'])) {
                    $availability_data[] = array(
                        'name' => sanitize_text_field($rule['name']),
                        'start_date' => sanitize_text_field($rule['start_date']),
                        'end_date' => sanitize_text_field($rule['end_date']),
                        'quantity_to_remove' => sanitize_text_field($rule['quantity_to_remove']),
                    );
                }
            }
            update_post_meta($post_id, '_crcm_availability_data', $availability_data);
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
     * Enhanced availability check with custom rules
     */
    public function check_availability($vehicle_id, $pickup_date, $return_date) {
        $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
        $availability_data = get_post_meta($vehicle_id, '_crcm_availability_data', true);
        
        $total_quantity = isset($vehicle_data['quantity']) ? intval($vehicle_data['quantity']) : 0;
        
        if ($total_quantity <= 0) {
            return 0;
        }
        
        // Apply availability rules
        $blocked_quantity = 0;
        if (!empty($availability_data)) {
            foreach ($availability_data as $rule) {
                if ($this->dates_overlap($pickup_date, $return_date, $rule['start_date'], $rule['end_date'])) {
                    if ($rule['quantity_to_remove'] === 'all') {
                        return 0; // Completely unavailable
                    } else {
                        $blocked_quantity += intval($rule['quantity_to_remove']);
                    }
                }
            }
        }
        
        // Count existing bookings
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
            
            if ($this->dates_overlap($pickup_date, $return_date, $booking_data['pickup_date'], $booking_data['return_date'])) {
                $booked_quantity++;
            }
        }
        
        return max(0, $total_quantity - $blocked_quantity - $booked_quantity);
    }
    
    /**
     * Check if two date ranges overlap
     */
    private function dates_overlap($start1, $end1, $start2, $end2) {
        return $start1 < $end2 && $end1 > $start2;
    }
    
    /**
     * Get vehicle type from post
     */
    public function get_vehicle_type($vehicle_id) {
        $vehicle_data = get_post_meta($vehicle_id, '_crcm_vehicle_data', true);
        return isset($vehicle_data['vehicle_type']) ? $vehicle_data['vehicle_type'] : 'auto';
    }
    
    /**
     * Get locations array
     */
    public function get_locations() {
        return $this->locations;
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
        $new_columns['crcm_specs'] = __('Specifications', 'custom-rental-manager');
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
                    echo '<div class="dashicons dashicons-car" style="font-size: 40px; color: #ccc;"></div>';
                }
                break;
                
            case 'crcm_type':
                $type = isset($vehicle_data['vehicle_type']) ? $vehicle_data['vehicle_type'] : 'auto';
                $type_name = $this->vehicle_types[$type]['name'];
                echo '<span class="crcm-type-badge crcm-type-' . esc_attr($type) . '">' . esc_html($type_name) . '</span>';
                break;
                
            case 'crcm_specs':
                if ($vehicle_data) {
                    $specs = array();
                    
                    if (isset($vehicle_data['seats'])) {
                        $specs[] = $vehicle_data['seats'] . ' posti';
                    }
                    
                    if (isset($vehicle_data['engine_size'])) {
                        $specs[] = $vehicle_data['engine_size'];
                    }
                    
                    if (isset($vehicle_data['transmission'])) {
                        $specs[] = ucfirst($vehicle_data['transmission']);
                    }
                    
                    if (isset($vehicle_data['fuel_type'])) {
                        $specs[] = ucfirst($vehicle_data['fuel_type']);
                    }
                    
                    echo implode('<br>', array_map('esc_html', $specs));
                }
                break;
                
            case 'crcm_daily_rate':
                if ($pricing_data && isset($pricing_data['daily_rate'])) {
                    echo '€' . number_format($pricing_data['daily_rate'], 2);
                }
                break;
                
            case 'crcm_availability':
                if ($vehicle_data && isset($vehicle_data['quantity'])) {
                    $available = intval($vehicle_data['quantity']);
                    $color = $available > 0 ? 'green' : 'red';
                    echo '<span style="color: ' . $color . '; font-weight: bold;">' . $available . '</span>';
                }
                break;
        }
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
    
    /**
     * Search available vehicles
     */
    public function search_available_vehicles($pickup_date, $return_date, $vehicle_type = '') {
        $args = array(
            'post_type' => 'crcm_vehicle',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );
        
        $vehicles = get_posts($args);
        $available_vehicles = array();
        
        foreach ($vehicles as $vehicle) {
            $vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
            
            // Filter by type if specified
            if (!empty($vehicle_type) && isset($vehicle_data['vehicle_type']) && $vehicle_data['vehicle_type'] !== $vehicle_type) {
                continue;
            }
            
            $available_quantity = $this->check_availability($vehicle->ID, $pickup_date, $return_date);
            $pricing_data = get_post_meta($vehicle->ID, '_crcm_pricing_data', true);
            
            $available_vehicles[] = array(
                'id' => $vehicle->ID,
                'title' => $vehicle->post_title,
                'permalink' => get_permalink($vehicle->ID),
                'thumbnail' => get_the_post_thumbnail_url($vehicle->ID, 'medium'),
                'daily_rate' => $pricing_data['daily_rate'] ?? 0,
                'vehicle_type' => $vehicle_data['vehicle_type'] ?? 'auto',
                'specs' => $vehicle_data,
                'available_quantity' => $available_quantity,
                'is_available' => $available_quantity > 0,
            );
        }
        
        return $available_vehicles;
    }
}

// Add CSS for admin styling
add_action('admin_head', function() {
    ?>
    <style>
    .crcm-type-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        color: white;
    }
    
    .crcm-type-auto {
        background: #667eea;
    }
    
    .crcm-type-scooter {
        background: #764ba2;
    }
    </style>
    <?php
});

// Add AJAX handler for features
add_action('wp_ajax_crcm_get_vehicle_features', array(crcm()->vehicle_manager, 'ajax_get_vehicle_features'));
