<?php
/**
 * Frontend Booking Form Template - WIZARD STYLE
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get parameters from URL
$vehicle_id  = ! empty( sanitize_text_field( $_GET['vehicle'] ?? '' ) )
    ? intval( sanitize_text_field( $_GET['vehicle'] ) )
    : ( $atts['vehicle_id'] ?? '' );
$pickup_date = ! empty( sanitize_text_field( $_GET['pickup_date'] ?? '' ) )
    ? sanitize_text_field( $_GET['pickup_date'] )
    : '';
$return_date = ! empty( sanitize_text_field( $_GET['return_date'] ?? '' ) )
    ? sanitize_text_field( $_GET['return_date'] )
    : '';
$pickup_time = ! empty( sanitize_text_field( $_GET['pickup_time'] ?? '' ) )
    ? sanitize_text_field( $_GET['pickup_time'] )
    : '09:00';
$return_time = ! empty( sanitize_text_field( $_GET['return_time'] ?? '' ) )
    ? sanitize_text_field( $_GET['return_time'] )
    : '18:00';

$vehicle = null;
if ($vehicle_id) {
    $vehicle = get_post($vehicle_id);
}

if (!$vehicle) {
    echo '<div class="crcm-error">' . __('Veicolo non trovato.', 'custom-rental-manager') . '</div>';
    return;
}

$vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
$pricing_data = get_post_meta($vehicle->ID, '_crcm_pricing_data', true);
$daily_rate = $pricing_data['daily_rate'] ?? 0;
$locations = crcm_get_locations();
$currency_symbol = crcm_get_setting('currency_symbol', '€');

// Calculate rental days
$rental_days = 1;
if ($pickup_date && $return_date) {
    $pickup = new DateTime($pickup_date);
    $return = new DateTime($return_date);
    $rental_days = max(1, $return->diff($pickup)->days);
}

// Available extras
$extras = array(
    'child_seat' => array('name' => 'Seggiolino per bambini', 'price' => 5, 'icon' => '👶'),
    'gps' => array('name' => 'GPS Navigatore', 'price' => 7, 'icon' => '🗺️'),
    'helmet' => array('name' => 'Casco extra', 'price' => 3, 'icon' => '🪖'),
    'phone_holder' => array('name' => 'Supporto telefono', 'price' => 2, 'icon' => '📱'),
    'storage_box' => array('name' => 'Bauletto aggiuntivo', 'price' => 8, 'icon' => '📦'),
);

// Calculate base price
$base_total = $daily_rate * $rental_days;
?>

<div class="crcm-booking-container">
    <!-- Booking Header -->
    <div class="crcm-booking-header">
        <div class="crcm-breadcrumb">
            <a href="javascript:history.back()" class="crcm-back-link">← <?php _e('Cambia veicolo o date', 'custom-rental-manager'); ?></a>
        </div>
        <h1><?php _e('Prenota il tuo veicolo', 'custom-rental-manager'); ?></h1>
    </div>
    
    <div class="crcm-booking-wrapper">
        <!-- Main Booking Form -->
        <div class="crcm-booking-main">
            <!-- Progress Steps -->
            <div class="crcm-progress-steps">
                <div class="crcm-step active" data-step="1">
                    <div class="crcm-step-number">1</div>
                    <div class="crcm-step-label"><?php _e('Servizi Aggiuntivi', 'custom-rental-manager'); ?></div>
                </div>
                <div class="crcm-step" data-step="2">
                    <div class="crcm-step-number">2</div>
                    <div class="crcm-step-label"><?php _e('Dati Personali', 'custom-rental-manager'); ?></div>
                </div>
                <div class="crcm-step" data-step="3">
                    <div class="crcm-step-number">3</div>
                    <div class="crcm-step-label"><?php _e('Riepilogo', 'custom-rental-manager'); ?></div>
                </div>
                <div class="crcm-step" data-step="4">
                    <div class="crcm-step-number">4</div>
                    <div class="crcm-step-label"><?php _e('Pagamento', 'custom-rental-manager'); ?></div>
                </div>
            </div>
            
            <!-- Booking Form -->
            <form id="crcm-booking-form" class="crcm-wizard-form">
                <input type="hidden" name="vehicle_id" value="<?php echo esc_attr($vehicle_id); ?>" />
                <input type="hidden" name="pickup_date" value="<?php echo esc_attr($pickup_date); ?>" />
                <input type="hidden" name="return_date" value="<?php echo esc_attr($return_date); ?>" />
                <input type="hidden" name="pickup_time" value="<?php echo esc_attr($pickup_time); ?>" />
                <input type="hidden" name="return_time" value="<?php echo esc_attr($return_time); ?>" />
                
                <!-- Step 1: Extras -->
                <div class="crcm-form-step active" id="step-1">
                    <div class="crcm-step-content">
                        <h2><?php _e('Step 1: Servizi Aggiuntivi', 'custom-rental-manager'); ?></h2>
                        <p class="crcm-step-description"><?php _e('Personalizza il tuo noleggio con i nostri servizi extra.', 'custom-rental-manager'); ?></p>
                        
                        <!-- Pickup Details -->
                        <div class="crcm-pickup-details">
                            <h3><?php _e('Dettagli del ritiro', 'custom-rental-manager'); ?></h3>
                            
                            <div class="crcm-field-row">
                                <div class="crcm-field-group">
                                    <label for="pickup_location"><?php _e('Sede di ritiro', 'custom-rental-manager'); ?></label>
                                    <select id="pickup_location" name="pickup_location" required>
                                        <option value=""><?php _e('Seleziona sede', 'custom-rental-manager'); ?></option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?php echo $location->term_id; ?>">
                                                <?php echo esc_html($location->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="crcm-field-group">
                                    <label for="return_location"><?php _e('Sede di riconsegna', 'custom-rental-manager'); ?></label>
                                    <select id="return_location" name="return_location">
                                        <option value=""><?php _e('Stessa sede di ritiro', 'custom-rental-manager'); ?></option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?php echo $location->term_id; ?>">
                                                <?php echo esc_html($location->name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="crcm-checkbox-group">
                                <input type="checkbox" id="home_delivery" name="home_delivery" value="1" />
                                <label for="home_delivery">
                                    <span class="crcm-checkbox-custom"></span>
                                    <?php _e('Richiedi consegna a domicilio (gratuita)', 'custom-rental-manager'); ?>
                                </label>
                            </div>
                            
                            <div class="crcm-delivery-address" style="display: none;">
                                <div class="crcm-field-group">
                                    <label for="delivery_address"><?php _e('Indirizzo di consegna', 'custom-rental-manager'); ?></label>
                                    <textarea id="delivery_address" name="delivery_address" rows="3" 
                                              placeholder="<?php _e('Inserisci l\'indirizzo completo per la consegna', 'custom-rental-manager'); ?>"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Extras Selection -->
                        <div class="crcm-extras-section">
                            <h3><?php _e('Servizi extra opzionali', 'custom-rental-manager'); ?></h3>
                            <div class="crcm-extras-grid">
                                <?php foreach ($extras as $key => $extra): ?>
                                    <div class="crcm-extra-item">
                                        <div class="crcm-extra-content">
                                            <input type="checkbox" 
                                                   id="extra_<?php echo $key; ?>" 
                                                   name="extras[]" 
                                                   value="<?php echo $key; ?>"
                                                   data-price="<?php echo $extra['price']; ?>" />
                                            <label for="extra_<?php echo $key; ?>" class="crcm-extra-label">
                                                <div class="crcm-extra-icon"><?php echo $extra['icon']; ?></div>
                                                <div class="crcm-extra-info">
                                                    <div class="crcm-extra-name"><?php echo esc_html($extra['name']); ?></div>
                                                    <div class="crcm-extra-price">+<?php echo crcm_format_price($extra['price'], $currency_symbol); ?>/<?php _e('giorno', 'custom-rental-manager'); ?></div>
                                                </div>
                                                <div class="crcm-extra-checkbox"></div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Special Requests -->
                        <div class="crcm-field-group">
                            <label for="special_requests"><?php _e('Richieste speciali (opzionale)', 'custom-rental-manager'); ?></label>
                            <textarea id="special_requests" name="special_requests" rows="3" 
                                      placeholder="<?php _e('Eventuali richieste particolari...', 'custom-rental-manager'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="crcm-step-actions">
                        <button type="button" class="crcm-next-btn" data-next="2">
                            <?php _e('Continua', 'custom-rental-manager'); ?> →
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Customer Information -->
                <div class="crcm-form-step" id="step-2">
                    <div class="crcm-step-content">
                        <h2><?php _e('Step 2: Dati Personali', 'custom-rental-manager'); ?></h2>
                        <p class="crcm-step-description"><?php _e('Inserisci i tuoi dati per completare la prenotazione.', 'custom-rental-manager'); ?></p>
                        
                        <div class="crcm-field-row">
                            <div class="crcm-field-group">
                                <label for="first_name"><?php _e('Nome', 'custom-rental-manager'); ?> *</label>
                                <input type="text" id="first_name" name="first_name" required />
                            </div>
                            
                            <div class="crcm-field-group">
                                <label for="last_name"><?php _e('Cognome', 'custom-rental-manager'); ?> *</label>
                                <input type="text" id="last_name" name="last_name" required />
                            </div>
                        </div>
                        
                        <div class="crcm-field-row">
                            <div class="crcm-field-group">
                                <label for="email"><?php _e('Email', 'custom-rental-manager'); ?> *</label>
                                <input type="email" id="email" name="email" required />
                                <small class="crcm-field-note"><?php _e('Riceverai la conferma di prenotazione via email', 'custom-rental-manager'); ?></small>
                            </div>
                            
                            <div class="crcm-field-group">
                                <label for="phone"><?php _e('Telefono', 'custom-rental-manager'); ?> *</label>
                                <input type="tel" id="phone" name="phone" required />
                            </div>
                        </div>
                        
                        <div class="crcm-field-row">
                            <div class="crcm-field-group">
                                <label for="date_of_birth"><?php _e('Data di nascita', 'custom-rental-manager'); ?> *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" required />
                            </div>
                            
                            <div class="crcm-field-group">
                                <label for="license_number"><?php _e('Numero patente', 'custom-rental-manager'); ?> *</label>
                                <input type="text" id="license_number" name="license_number" required />
                            </div>
                        </div>
                        
                        <div class="crcm-field-group">
                            <label for="address"><?php _e('Indirizzo completo', 'custom-rental-manager'); ?></label>
                            <textarea id="address" name="address" rows="2" 
                                      placeholder="<?php _e('Via, Città, CAP, Provincia', 'custom-rental-manager'); ?>"></textarea>
                        </div>
                        
                        <div class="crcm-field-row">
                            <div class="crcm-field-group">
                                <label for="emergency_contact"><?php _e('Contatto di emergenza', 'custom-rental-manager'); ?></label>
                                <input type="text" id="emergency_contact" name="emergency_contact" 
                                       placeholder="<?php _e('Nome e cognome', 'custom-rental-manager'); ?>" />
                            </div>
                            
                            <div class="crcm-field-group">
                                <label for="emergency_phone"><?php _e('Telefono di emergenza', 'custom-rental-manager'); ?></label>
                                <input type="tel" id="emergency_phone" name="emergency_phone" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="crcm-step-actions">
                        <button type="button" class="crcm-prev-btn" data-prev="1">
                            ← <?php _e('Indietro', 'custom-rental-manager'); ?>
                        </button>
                        <button type="button" class="crcm-next-btn" data-next="3">
                            <?php _e('Continua', 'custom-rental-manager'); ?> →
                        </button>
                    </div>
                </div>
                
                <!-- Step 3: Summary -->
                <div class="crcm-form-step" id="step-3">
                    <div class="crcm-step-content">
                        <h2><?php _e('Step 3: Riepilogo e Pagamento', 'custom-rental-manager'); ?></h2>
                        <p class="crcm-step-description"><?php _e('Verifica i dettagli della tua prenotazione e scegli la modalità di pagamento.', 'custom-rental-manager'); ?></p>
                        
                        <!-- Booking Summary -->
                        <div class="crcm-booking-summary">
                            <h3><?php _e('Riepilogo prenotazione', 'custom-rental-manager'); ?></h3>
                            <div id="summary-content">
                                <!-- Summary will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <!-- Payment Options -->
                        <div class="crcm-payment-options">
                            <h3><?php _e('Modalità di pagamento', 'custom-rental-manager'); ?></h3>
                            
                            <div class="crcm-payment-choice">
                                <input type="radio" id="pay_full" name="payment_type" value="full" checked />
                                <label for="pay_full" class="crcm-payment-label">
                                    <div class="crcm-payment-info">
                                        <div class="crcm-payment-title"><?php _e('Pagamento completo', 'custom-rental-manager'); ?></div>
                                        <div class="crcm-payment-desc"><?php _e('Paga subito l\'intero importo', 'custom-rental-manager'); ?></div>
                                    </div>
                                    <div class="crcm-payment-amount" id="full-amount">
                                        <?php echo crcm_format_price($base_total, $currency_symbol); ?>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="crcm-payment-choice">
                                <input type="radio" id="pay_deposit" name="payment_type" value="deposit" />
                                <label for="pay_deposit" class="crcm-payment-label">
                                    <div class="crcm-payment-info">
                                        <div class="crcm-payment-title"><?php _e('Solo deposito (30%)', 'custom-rental-manager'); ?></div>
                                        <div class="crcm-payment-desc"><?php _e('Paga ora il deposito, il resto al ritiro', 'custom-rental-manager'); ?></div>
                                    </div>
                                    <div class="crcm-payment-amount" id="deposit-amount">
                                        <?php echo crcm_format_price($base_total * 0.3, $currency_symbol); ?>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="crcm-terms-section">
                            <div class="crcm-checkbox-group">
                                <input type="checkbox" id="accept_terms" name="accept_terms" value="1" required />
                                <label for="accept_terms">
                                    <span class="crcm-checkbox-custom"></span>
                                    <?php _e('Accetto i ', 'custom-rental-manager'); ?>
                                    <a href="#" target="_blank"><?php _e('Termini e Condizioni', 'custom-rental-manager'); ?></a>
                                    <?php _e(' e la ', 'custom-rental-manager'); ?>
                                    <a href="#" target="_blank"><?php _e('Privacy Policy', 'custom-rental-manager'); ?></a>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="crcm-step-actions">
                        <button type="button" class="crcm-prev-btn" data-prev="2">
                            ← <?php _e('Indietro', 'custom-rental-manager'); ?>
                        </button>
                        <button type="button" class="crcm-next-btn" data-next="4">
                            <?php _e('Procedi al pagamento', 'custom-rental-manager'); ?> →
                        </button>
                    </div>
                </div>
                
                <!-- Step 4: Payment -->
                <div class="crcm-form-step" id="step-4">
                    <div class="crcm-step-content">
                        <h2><?php _e('Step 4: Conferma e Pagamento', 'custom-rental-manager'); ?></h2>
                        <p class="crcm-step-description"><?php _e('Ultimo step! Conferma la prenotazione e effettua il pagamento sicuro.', 'custom-rental-manager'); ?></p>
                        
                        <div class="crcm-final-summary">
                            <h3><?php _e('Importo da pagare', 'custom-rental-manager'); ?></h3>
                            <div class="crcm-total-amount" id="final-total">
                                <?php echo crcm_format_price($base_total, $currency_symbol); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="crcm-step-actions">
                        <button type="button" class="crcm-prev-btn" data-prev="3">
                            ← <?php _e('Indietro', 'custom-rental-manager'); ?>
                        </button>
                        <button type="submit" class="crcm-pay-btn" id="stripe-pay-btn">
                            <span class="crcm-btn-icon">💳</span>
                            <?php _e('Paga con Stripe', 'custom-rental-manager'); ?>
                        </button>
                    </div>
                </div>
                
                <?php wp_nonce_field('crcm_nonce', 'crcm_nonce'); ?>
            </form>
        </div>
        
        <!-- Booking Summary Sidebar -->
        <div class="crcm-booking-sidebar">
            <div class="crcm-summary-card">
                <h3><?php _e('Riepilogo Prenotazione', 'custom-rental-manager'); ?></h3>
                
                <!-- Vehicle Info -->
                <div class="crcm-summary-vehicle">
                    <div class="crcm-vehicle-image">
                        <?php if (has_post_thumbnail($vehicle->ID)): ?>
                            <?php echo get_the_post_thumbnail($vehicle->ID, 'thumbnail'); ?>
                        <?php else: ?>
                            <div class="crcm-no-image">🚗</div>
                        <?php endif; ?>
                    </div>
                    <div class="crcm-vehicle-info">
                        <h4><?php echo esc_html($vehicle->post_title); ?></h4>
                        <div class="crcm-vehicle-specs">
                            <?php if (isset($vehicle_data['seats'])): ?>
                                <span>👥 <?php echo $vehicle_data['seats']; ?> posti</span>
                            <?php endif; ?>
                            <?php if (isset($vehicle_data['transmission'])): ?>
                                <span>⚙️ <?php echo ucfirst($vehicle_data['transmission']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Rental Details -->
                <div class="crcm-summary-section">
                    <h4><?php _e('Dettagli noleggio', 'custom-rental-manager'); ?></h4>
                    <div class="crcm-rental-dates">
                        <div class="crcm-date-item">
                            <strong><?php _e('Ritiro:', 'custom-rental-manager'); ?></strong>
                            <span id="pickup-summary">
                                <?php echo $pickup_date ? date_i18n('d/m/Y', strtotime($pickup_date)) : '-'; ?>
                                <?php echo $pickup_time ? ' alle ' . $pickup_time : ''; ?>
                            </span>
                        </div>
                        <div class="crcm-date-item">
                            <strong><?php _e('Riconsegna:', 'custom-rental-manager'); ?></strong>
                            <span id="return-summary">
                                <?php echo $return_date ? date_i18n('d/m/Y', strtotime($return_date)) : '-'; ?>
                                <?php echo $return_time ? ' alle ' . $return_time : ''; ?>
                            </span>
                        </div>
                        <div class="crcm-date-item">
                            <strong><?php _e('Durata:', 'custom-rental-manager'); ?></strong>
                            <span id="duration-summary"><?php echo $rental_days; ?> <?php _e('giorni', 'custom-rental-manager'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing Breakdown -->
                <div class="crcm-summary-section">
                    <h4><?php _e('Costi', 'custom-rental-manager'); ?></h4>
                    <div class="crcm-pricing-breakdown">
                        <div class="crcm-price-item">
                            <span><?php _e('Noleggio base', 'custom-rental-manager'); ?> (<?php echo $rental_days; ?> <?php _e('giorni', 'custom-rental-manager'); ?>)</span>
                            <span id="base-price"><?php echo crcm_format_price($base_total, $currency_symbol); ?></span>
                        </div>
                        
                        <div id="extras-pricing" class="crcm-extras-pricing">
                            <!-- Extras will be added here by JavaScript -->
                        </div>
                        
                        <div class="crcm-price-item crcm-total">
                            <strong>
                                <span><?php _e('Totale', 'custom-rental-manager'); ?></span>
                                <span id="sidebar-total"><?php echo crcm_format_price($base_total, $currency_symbol); ?></span>
                            </strong>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Info -->
                <div class="crcm-summary-section">
                    <h4><?php _e('Hai bisogno di aiuto?', 'custom-rental-manager'); ?></h4>
                    <div class="crcm-contact-info">
                        <p>📞 <a href="tel:+39081234567">+39 081 234 567</a></p>
                        <p>✉️ <a href="mailto:info@ischiarent.it">info@ischiarent.it</a></p>
                        <p><small><?php _e('Il nostro team è disponibile 9:00-19:00', 'custom-rental-manager'); ?></small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

