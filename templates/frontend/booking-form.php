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
$vehicle_id = isset($_GET['vehicle']) ? intval($_GET['vehicle']) : ($atts['vehicle_id'] ?? '');
$pickup_date = isset($_GET['pickup_date']) ? sanitize_text_field($_GET['pickup_date']) : '';
$return_date = isset($_GET['return_date']) ? sanitize_text_field($_GET['return_date']) : '';
$pickup_time = isset($_GET['pickup_time']) ? sanitize_text_field($_GET['pickup_time']) : '09:00';
$return_time = isset($_GET['return_time']) ? sanitize_text_field($_GET['return_time']) : '18:00';

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

<style>
.crcm-booking-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.crcm-booking-header {
    margin-bottom: 30px;
}

.crcm-breadcrumb {
    margin-bottom: 15px;
}

.crcm-back-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.crcm-back-link:hover {
    color: #764ba2;
    text-decoration: none;
}

.crcm-booking-header h1 {
    font-size: 28px;
    color: #2c3e50;
    margin: 0;
}

.crcm-booking-wrapper {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    align-items: start;
}

.crcm-progress-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    position: relative;
}

.crcm-progress-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e1e8ed;
    z-index: 1;
}

.crcm-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    background: white;
    z-index: 2;
    position: relative;
}

.crcm-step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e1e8ed;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.crcm-step.active .crcm-step-number {
    background: #667eea;
    color: white;
}

.crcm-step.completed .crcm-step-number {
    background: #27ae60;
    color: white;
}

.crcm-step-label {
    font-size: 12px;
    font-weight: 600;
    color: #7f8c8d;
    max-width: 80px;
}

.crcm-step.active .crcm-step-label {
    color: #667eea;
}

.crcm-wizard-form {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
}

.crcm-form-step {
    display: none;
}

.crcm-form-step.active {
    display: block;
}

.crcm-step-content h2 {
    color: #2c3e50;
    margin: 0 0 10px 0;
    font-size: 24px;
}

.crcm-step-description {
    color: #7f8c8d;
    margin: 0 0 30px 0;
    font-size: 16px;
}

.crcm-field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.crcm-field-group {
    display: flex;
    flex-direction: column;
}

.crcm-field-group label {
    font-weight: 600;
    color: #34495e;
    margin-bottom: 8px;
    font-size: 14px;
}

.crcm-field-group input,
.crcm-field-group select,
.crcm-field-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: #fafbfc;
    box-sizing: border-box;
}

.crcm-field-group input:focus,
.crcm-field-group select:focus,
.crcm-field-group textarea:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.crcm-field-note {
    font-size: 12px;
    color: #7f8c8d;
    margin-top: 5px;
}

.crcm-pickup-details,
.crcm-extras-section {
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
}

.crcm-pickup-details h3,
.crcm-extras-section h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 18px;
}

.crcm-checkbox-group {
    display: flex;
    align-items: center;
    margin: 15px 0;
}

.crcm-checkbox-group input[type="checkbox"] {
    display: none;
}

.crcm-checkbox-group label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: 500;
    color: #34495e;
    margin: 0;
}

.crcm-checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #e1e8ed;
    border-radius: 4px;
    margin-right: 12px;
    position: relative;
    transition: all 0.3s ease;
    background: white;
    flex-shrink: 0;
}

.crcm-checkbox-group input[type="checkbox"]:checked + label .crcm-checkbox-custom {
    background: #667eea;
    border-color: #667eea;
}

.crcm-checkbox-group input[type="checkbox"]:checked + label .crcm-checkbox-custom::after {
    content: '✓';
    position: absolute;
    color: white;
    font-size: 12px;
    font-weight: bold;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.crcm-delivery-address {
    margin-top: 15px;
}

.crcm-extras-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
}

.crcm-extra-item {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.crcm-extra-item:hover {
    border-color: #667eea;
}

.crcm-extra-content input[type="checkbox"] {
    display: none;
}

.crcm-extra-label {
    display: flex;
    align-items: center;
    padding: 15px;
    cursor: pointer;
    margin: 0;
    transition: all 0.3s ease;
}

.crcm-extra-content input[type="checkbox"]:checked + .crcm-extra-label {
    background: #f0f4ff;
}

.crcm-extra-icon {
    font-size: 24px;
    margin-right: 15px;
    flex-shrink: 0;
}

.crcm-extra-info {
    flex: 1;
}

.crcm-extra-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.crcm-extra-price {
    color: #27ae60;
    font-weight: 600;
    font-size: 14px;
}

.crcm-extra-checkbox {
    width: 20px;
    height: 20px;
    border: 2px solid #e1e8ed;
    border-radius: 4px;
    position: relative;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.crcm-extra-content input[type="checkbox"]:checked + .crcm-extra-label .crcm-extra-checkbox {
    background: #667eea;
    border-color: #667eea;
}

.crcm-extra-content input[type="checkbox"]:checked + .crcm-extra-label .crcm-extra-checkbox::after {
    content: '✓';
    position: absolute;
    color: white;
    font-size: 12px;
    font-weight: bold;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.crcm-payment-options {
    margin: 30px 0;
}

.crcm-payment-choice {
    margin-bottom: 15px;
}

.crcm-payment-choice input[type="radio"] {
    display: none;
}

.crcm-payment-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border: 2px solid #e1e8ed;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0;
}

.crcm-payment-choice input[type="radio"]:checked + .crcm-payment-label {
    border-color: #667eea;
    background: #f0f4ff;
}

.crcm-payment-title {
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
}

.crcm-payment-desc {
    color: #7f8c8d;
    font-size: 14px;
}

.crcm-payment-amount {
    font-size: 24px;
    font-weight: 700;
    color: #27ae60;
}

.crcm-step-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #e1e8ed;
}

.crcm-prev-btn,
.crcm-next-btn,
.crcm-pay-btn {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.crcm-prev-btn {
    background: #ecf0f1;
    color: #34495e;
}

.crcm-prev-btn:hover {
    background: #bdc3c7;
}

.crcm-next-btn,
.crcm-pay-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.crcm-next-btn:hover,
.crcm-pay-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.crcm-pay-btn {
    background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
    font-size: 16px;
    padding: 15px 40px;
}

.crcm-pay-btn:hover {
    box-shadow: 0 8px 25px rgba(39, 174, 96, 0.4);
}

/* Sidebar Styles */
.crcm-booking-sidebar {
    position: sticky;
    top: 20px;
}

.crcm-summary-card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
}

.crcm-summary-card h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 18px;
    border-bottom: 1px solid #e1e8ed;
    padding-bottom: 10px;
}

.crcm-summary-vehicle {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e1e8ed;
}

.crcm-vehicle-image {
    width: 80px;
    height: 60px;
    flex-shrink: 0;
}

.crcm-vehicle-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.crcm-no-image {
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 24px;
}

.crcm-vehicle-info h4 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 16px;
}

.crcm-vehicle-specs {
    display: flex;
    gap: 10px;
    font-size: 12px;
    color: #7f8c8d;
}

.crcm-summary-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e1e8ed;
}

.crcm-summary-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.crcm-summary-section h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    font-size: 16px;
}

.crcm-date-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.crcm-pricing-breakdown {
    font-size: 14px;
}

.crcm-price-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 5px 0;
}

.crcm-price-item.crcm-total {
    border-top: 1px solid #e1e8ed;
    margin-top: 10px;
    padding-top: 15px;
    font-size: 16px;
}

.crcm-contact-info {
    font-size: 14px;
}

.crcm-contact-info p {
    margin: 0 0 5px 0;
}

.crcm-contact-info a {
    color: #667eea;
    text-decoration: none;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .crcm-booking-wrapper {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .crcm-booking-sidebar {
        order: -1;
        position: static;
    }
}

@media (max-width: 768px) {
    .crcm-booking-container {
        padding: 15px;
    }
    
    .crcm-progress-steps {
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .crcm-step-number {
        width: 35px;
        height: 35px;
    }
    
    .crcm-wizard-form {
        padding: 20px;
    }
    
    .crcm-field-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .crcm-extras-grid {
        grid-template-columns: 1fr;
    }
    
    .crcm-step-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .crcm-prev-btn,
    .crcm-next-btn,
    .crcm-pay-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentStep = 1;
    const totalSteps = 4;
    const dailyRate = <?php echo json_encode($daily_rate); ?>;
    const rentalDays = <?php echo json_encode($rental_days); ?>;
    const currencySymbol = <?php echo json_encode($currency_symbol); ?>;
    
    // Initialize
    showStep(1);
    
    // Step navigation
    $('.crcm-next-btn').on('click', function() {
        const nextStep = parseInt($(this).data('next'));
        if (validateStep(currentStep)) {
            showStep(nextStep);
        }
    });
    
    $('.crcm-prev-btn').on('click', function() {
        const prevStep = parseInt($(this).data('prev'));
        showStep(prevStep);
    });
    
    // Show specific step
    function showStep(step) {
        // Hide all steps
        $('.crcm-form-step').removeClass('active');
        $('.crcm-step').removeClass('active completed');
        
        // Show current step
        $('#step-' + step).addClass('active');
        $('.crcm-step[data-step="' + step + '"]').addClass('active');
        
        // Mark completed steps
        for (let i = 1; i < step; i++) {
            $('.crcm-step[data-step="' + i + '"]').addClass('completed');
        }
        
        currentStep = step;
        
        // Update summary if on step 3 or 4
        if (step >= 3) {
            updateSummary();
        }
    }
    
    // Validate current step
    function validateStep(step) {
        let isValid = true;
        const $currentStep = $('#step-' + step);
        
        // Clear previous errors
        $('.crcm-field-error').remove();
        
        // Check required fields
        $currentStep.find('[required]').each(function() {
            if (!$(this).val().trim()) {
                showFieldError($(this), 'Campo obbligatorio');
                isValid = false;
            }
        });
        
        // Email validation
        if (step === 2) {
            const email = $('#email').val();
            if (email && !isValidEmail(email)) {
                showFieldError($('#email'), 'Email non valida');
                isValid = false;
            }
        }
        
        // Terms acceptance
        if (step === 3) {
            if (!$('#accept_terms').is(':checked')) {
                alert('Devi accettare i termini e condizioni per procedere.');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    // Home delivery toggle
    $('#home_delivery').on('change', function() {
        if ($(this).is(':checked')) {
            $('.crcm-delivery-address').slideDown();
        } else {
            $('.crcm-delivery-address').slideUp();
        }
    });
    
    // Auto-sync return location
    $('#pickup_location').on('change', function() {
        const selectedValue = $(this).val();
        if (selectedValue && !$('#return_location').val()) {
            $('#return_location').val(selectedValue);
        }
    });
    
    // Extras calculation
    $('input[name="extras[]"]').on('change', function() {
        updatePricing();
    });
    
    // Payment type change
    $('input[name="payment_type"]').on('change', function() {
        updateFinalAmount();
    });
    
    // Update pricing
    function updatePricing() {
        let total = dailyRate * rentalDays;
        let extrasTotal = 0;
        
        // Clear extras pricing
        $('#extras-pricing').empty();
        
        // Calculate extras
        $('input[name="extras[]"]:checked').each(function() {
            const extraPrice = parseFloat($(this).data('price'));
            const extraName = $(this).closest('.crcm-extra-item').find('.crcm-extra-name').text();
            const extraTotal = extraPrice * rentalDays;
            
            extrasTotal += extraTotal;
            
            // Add to sidebar
            $('#extras-pricing').append(
                '<div class="crcm-price-item">' +
                '<span>' + extraName + ' (' + rentalDays + ' giorni)</span>' +
                '<span>' + formatPrice(extraTotal) + '</span>' +
                '</div>'
            );
        });
        
        total += extrasTotal;
        
        // Update totals
        $('#sidebar-total').text(formatPrice(total));
        $('#full-amount').text(formatPrice(total));
        $('#deposit-amount').text(formatPrice(total * 0.3));
        
        updateFinalAmount();
    }
    
    // Update final amount based on payment type
    function updateFinalAmount() {
        const total = parseFloat($('#sidebar-total').text().replace(/[^0-9.-]+/g, ''));
        const paymentType = $('input[name="payment_type"]:checked').val();
        
        let finalAmount = total;
        if (paymentType === 'deposit') {
            finalAmount = total * 0.3;
        }
        
        $('#final-total').text(formatPrice(finalAmount));
    }
    
    // Update summary
    function updateSummary() {
        // This would be populated with booking details
        // Implementation depends on specific requirements
    }
    
    // Format price
    function formatPrice(amount) {
        return currencySymbol + amount.toFixed(2);
    }
    
    // Form submission
    $('#crcm-booking-form').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateStep(currentStep)) {
            return;
        }
        
        // Show loading
        $('#stripe-pay-btn').prop('disabled', true).html('💳 Elaborazione...');
        
        // Here you would integrate with Stripe
        // For now, just simulate success
        setTimeout(function() {
            alert('Prenotazione completata con successo!');
            // Redirect to confirmation page or customer dashboard
        }, 2000);
    });
    
    // Utility functions
    function showFieldError($field, message) {
        const $error = $('<div class="crcm-field-error" style="color: #e74c3c; font-size: 12px; margin-top: 5px;">' + message + '</div>');
        $field.closest('.crcm-field-group').append($error);
        $field.focus();
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Initialize pricing
    updatePricing();
});
</script>
