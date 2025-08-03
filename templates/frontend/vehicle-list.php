<?php
/**
 * Frontend Vehicle List Template - ENHANCED
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get search parameters
$pickup_date = isset($_GET['pickup_date']) ? sanitize_text_field($_GET['pickup_date']) : '';
$return_date = isset($_GET['return_date']) ? sanitize_text_field($_GET['return_date']) : '';
$pickup_time = isset($_GET['pickup_time']) ? sanitize_text_field($_GET['pickup_time']) : '09:00';
$return_time = isset($_GET['return_time']) ? sanitize_text_field($_GET['return_time']) : '18:00';

// Get vehicles with availability check
$args = array(
    'post_type' => 'crcm_vehicle',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
);

if (!empty($atts['type'])) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'crcm_vehicle_type',
            'field' => 'term_id',
            'terms' => $atts['type'],
        ),
    );
}

$vehicles = get_posts($args);
$currency_symbol = crcm_get_setting('currency_symbol', '€');
$vehicle_manager = crcm()->vehicle_manager;

// Calculate rental days
$rental_days = 1;
if ($pickup_date && $return_date) {
    $pickup = new DateTime($pickup_date);
    $return = new DateTime($return_date);
    $rental_days = max(1, $return->diff($pickup)->days);
}

// Get vehicle types for filtering
$vehicle_types = crcm_get_vehicle_types();
?>

<div class="crcm-vehicle-list-container">
    <!-- Search Summary -->
    <?php if ($pickup_date && $return_date): ?>
    <div class="crcm-search-summary">
        <div class="crcm-search-info">
            <h2><?php _e('Veicoli disponibili', 'custom-rental-manager'); ?></h2>
            <p>
                <?php printf(
                    __('Dal %s al %s (%d giorni)', 'custom-rental-manager'),
                    date_i18n('d/m/Y', strtotime($pickup_date)),
                    date_i18n('d/m/Y', strtotime($return_date)),
                    $rental_days
                ); ?>
            </p>
        </div>
        <div class="crcm-search-actions">
            <a href="javascript:history.back()" class="crcm-back-btn">
                <span>←</span> <?php _e('Cambia veicolo o date', 'custom-rental-manager'); ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="crcm-filters-container">
        <div class="crcm-filters-wrapper">
            <div class="crcm-filter-group">
                <label for="vehicle-type-filter"><?php _e('Tipo di veicolo:', 'custom-rental-manager'); ?></label>
                <select id="vehicle-type-filter" class="crcm-filter-select">
                    <option value=""><?php _e('Tutti i tipi', 'custom-rental-manager'); ?></option>
                    <?php foreach ($vehicle_types as $type): ?>
                        <option value="<?php echo esc_attr($type->slug); ?>">
                            <?php echo esc_html($type->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="crcm-filter-group">
                <label for="price-sort-filter"><?php _e('Ordina per prezzo:', 'custom-rental-manager'); ?></label>
                <select id="price-sort-filter" class="crcm-filter-select">
                    <option value=""><?php _e('Prezzo standard', 'custom-rental-manager'); ?></option>
                    <option value="price-asc"><?php _e('Prezzo crescente', 'custom-rental-manager'); ?></option>
                    <option value="price-desc"><?php _e('Prezzo decrescente', 'custom-rental-manager'); ?></option>
                </select>
            </div>
            
            <div class="crcm-filter-group">
                <span class="crcm-results-count">
                    <?php printf(__('Trovati %d veicoli', 'custom-rental-manager'), count($vehicles)); ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Vehicle Grid -->
    <div class="crcm-vehicles-grid" id="vehicles-grid">
        <?php if (!empty($vehicles)): ?>
            <?php foreach ($vehicles as $vehicle): 
                $vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
                $pricing_data = get_post_meta($vehicle->ID, '_crcm_pricing_data', true);
                $daily_rate = $pricing_data['daily_rate'] ?? 0;
                $vehicle_type = wp_get_post_terms($vehicle->ID, 'crcm_vehicle_type');
                $thumbnail = get_the_post_thumbnail($vehicle->ID, 'medium');
                $features = get_post_meta($vehicle->ID, '_crcm_vehicle_features', true) ?: array();
                
                // Check availability for selected dates
                $available_quantity = 0;
                $is_available = true;
                
                if ($pickup_date && $return_date && $vehicle_manager) {
                    $available_quantity = $vehicle_manager->check_availability($vehicle->ID, $pickup_date, $return_date);
                    $is_available = $available_quantity > 0;
                }
                
                // Calculate total price
                $total_price = $daily_rate * $rental_days;
                
                // Apply discounts for longer rentals
                if ($rental_days >= 30 && isset($pricing_data['monthly_discount'])) {
                    $discount = $pricing_data['monthly_discount'] / 100;
                    $total_price = $total_price * (1 - $discount);
                } elseif ($rental_days >= 7 && isset($pricing_data['weekly_discount'])) {
                    $discount = $pricing_data['weekly_discount'] / 100;
                    $total_price = $total_price * (1 - $discount);
                }
                
                $vehicle_type_slug = !empty($vehicle_type) ? $vehicle_type[0]->slug : '';
            ?>
                <div class="crcm-vehicle-card <?php echo $is_available ? 'available' : 'unavailable'; ?>" 
                     data-vehicle-type="<?php echo esc_attr($vehicle_type_slug); ?>"
                     data-daily-rate="<?php echo esc_attr($daily_rate); ?>"
                     data-vehicle-id="<?php echo esc_attr($vehicle->ID); ?>">
                     
                    <!-- Vehicle Image -->
                    <div class="crcm-vehicle-image">
                        <?php if ($thumbnail): ?>
                            <?php echo $thumbnail; ?>
                        <?php else: ?>
                            <div class="crcm-no-image">
                                <span class="crcm-vehicle-icon">🚗</span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Availability Badge -->
                        <?php if ($pickup_date && $return_date): ?>
                            <div class="crcm-availability-badge <?php echo $is_available ? 'available' : 'unavailable'; ?>">
                                <?php if ($is_available): ?>
                                    <span class="crcm-available-count"><?php echo $available_quantity; ?> <?php _e('disponibili', 'custom-rental-manager'); ?></span>
                                    <span class="crcm-available-icon">✓</span>
                                <?php else: ?>
                                    <span class="crcm-unavailable-text"><?php _e('Esaurito', 'custom-rental-manager'); ?></span>
                                    <span class="crcm-unavailable-icon">✗</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Vehicle Info -->
                    <div class="crcm-vehicle-info">
                        <div class="crcm-vehicle-header">
                            <h3 class="crcm-vehicle-title"><?php echo esc_html($vehicle->post_title); ?></h3>
                            
                            <?php if (!empty($vehicle_type)): ?>
                                <span class="crcm-vehicle-type"><?php echo esc_html($vehicle_type[0]->name); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Vehicle Specs -->
                        <div class="crcm-vehicle-specs">
                            <?php if (isset($vehicle_data['seats'])): ?>
                                <div class="crcm-spec-item">
                                    <span class="crcm-spec-icon">👥</span>
                                    <span class="crcm-spec-text"><?php echo esc_html($vehicle_data['seats']); ?> <?php _e('posti', 'custom-rental-manager'); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($vehicle_data['transmission'])): ?>
                                <div class="crcm-spec-item">
                                    <span class="crcm-spec-icon">⚙️</span>
                                    <span class="crcm-spec-text"><?php echo esc_html(ucfirst($vehicle_data['transmission'])); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($vehicle_data['fuel_type'])): ?>
                                <div class="crcm-spec-item">
                                    <span class="crcm-spec-icon">⛽</span>
                                    <span class="crcm-spec-text"><?php echo esc_html(ucfirst($vehicle_data['fuel_type'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Features -->
                        <?php if (!empty($features)): ?>
                            <div class="crcm-vehicle-features">
                                <?php 
                                $feature_icons = array(
                                    'air_conditioning' => '❄️',
                                    'gps' => '🗺️',
                                    'bluetooth' => '📱',
                                    'helmet_included' => '🪖',
                                    'storage_box' => '📦',
                                );
                                $shown_features = array_slice($features, 0, 3);
                                ?>
                                <?php foreach ($shown_features as $feature): ?>
                                    <span class="crcm-feature-tag">
                                        <?php echo isset($feature_icons[$feature]) ? $feature_icons[$feature] : '✓'; ?>
                                        <?php echo esc_html(str_replace('_', ' ', $feature)); ?>
                                    </span>
                                <?php endforeach; ?>
                                
                                <?php if (count($features) > 3): ?>
                                    <span class="crcm-feature-more">+<?php echo count($features) - 3; ?> <?php _e('altri', 'custom-rental-manager'); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Pricing -->
                        <div class="crcm-vehicle-pricing">
                            <div class="crcm-price-section">
                                <div class="crcm-daily-price">
                                    <span class="crcm-price-amount"><?php echo crcm_format_price($daily_rate, $currency_symbol); ?></span>
                                    <span class="crcm-price-period">/ <?php _e('giorno', 'custom-rental-manager'); ?></span>
                                </div>
                                
                                <?php if ($pickup_date && $return_date): ?>
                                    <div class="crcm-total-price">
                                        <span class="crcm-total-label"><?php _e('Totale:', 'custom-rental-manager'); ?></span>
                                        <span class="crcm-total-amount"><?php echo crcm_format_price($total_price, $currency_symbol); ?></span>
                                        <span class="crcm-total-period">(<?php echo $rental_days; ?> <?php _e('giorni', 'custom-rental-manager'); ?>)</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="crcm-vehicle-actions">
                            <?php if ($is_available): ?>
                                <button type="button" 
                                        class="crcm-book-btn crcm-book-now" 
                                        data-vehicle-id="<?php echo esc_attr($vehicle->ID); ?>"
                                        data-pickup-date="<?php echo esc_attr($pickup_date); ?>"
                                        data-return-date="<?php echo esc_attr($return_date); ?>"
                                        data-pickup-time="<?php echo esc_attr($pickup_time); ?>"
                                        data-return-time="<?php echo esc_attr($return_time); ?>">
                                    <span class="crcm-btn-icon">🚗</span>
                                    <?php _e('Prenota Ora', 'custom-rental-manager'); ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="crcm-book-btn unavailable" disabled>
                                    <span class="crcm-btn-icon">❌</span>
                                    <?php _e('Non Disponibile', 'custom-rental-manager'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <button type="button" class="crcm-details-btn" data-vehicle-id="<?php echo esc_attr($vehicle->ID); ?>">
                                <span class="crcm-btn-icon">👁️</span>
                                <?php _e('Dettagli', 'custom-rental-manager'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="crcm-no-vehicles">
                <div class="crcm-no-vehicles-icon">🚗</div>
                <h3><?php _e('Nessun veicolo trovato', 'custom-rental-manager'); ?></h3>
                <p><?php _e('Non ci sono veicoli disponibili per le date selezionate.', 'custom-rental-manager'); ?></p>
                <a href="javascript:history.back()" class="crcm-back-btn">
                    <?php _e('Modifica ricerca', 'custom-rental-manager'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.crcm-vehicle-list-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.crcm-search-summary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.crcm-search-info h2 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: 700;
}

.crcm-search-info p {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.crcm-back-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.crcm-back-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    text-decoration: none;
}

.crcm-filters-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.crcm-filters-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    align-items: end;
}

.crcm-filter-group label {
    display: block;
    font-weight: 600;
    color: #34495e;
    margin-bottom: 8px;
    font-size: 14px;
}

.crcm-filter-select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    font-size: 15px;
    background: #fafbfc;
    transition: all 0.3s ease;
}

.crcm-filter-select:focus {
    outline: none;
    border-color: #667eea;
    background: white;
}

.crcm-results-count {
    color: #7f8c8d;
    font-weight: 600;
    text-align: right;
}

.crcm-vehicles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.crcm-vehicle-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.crcm-vehicle-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
}

.crcm-vehicle-card.unavailable {
    opacity: 0.7;
}

.crcm-vehicle-card.unavailable:hover {
    transform: none;
}

.crcm-vehicle-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.crcm-vehicle-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.crcm-vehicle-card:hover .crcm-vehicle-image img {
    transform: scale(1.05);
}

.crcm-no-image {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.crcm-vehicle-icon {
    font-size: 48px;
    opacity: 0.6;
}

.crcm-availability-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.crcm-availability-badge.available {
    background: #27ae60;
    color: white;
}

.crcm-availability-badge.unavailable {
    background: #e74c3c;
    color: white;
}

.crcm-vehicle-info {
    padding: 20px;
}

.crcm-vehicle-header {
    margin-bottom: 15px;
}

.crcm-vehicle-title {
    font-size: 20px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 5px 0;
    line-height: 1.3;
}

.crcm-vehicle-type {
    display: inline-block;
    background: #667eea;
    color: white;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.crcm-vehicle-specs {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.crcm-spec-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    color: #7f8c8d;
}

.crcm-spec-icon {
    font-size: 16px;
}

.crcm-vehicle-features {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.crcm-feature-tag {
    background: #ecf0f1;
    color: #34495e;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 3px;
}

.crcm-feature-more {
    background: #3498db;
    color: white;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
}

.crcm-vehicle-pricing {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.crcm-daily-price {
    display: flex;
    align-items: baseline;
    gap: 5px;
    margin-bottom: 8px;
}

.crcm-price-amount {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
}

.crcm-price-period {
    font-size: 14px;
    color: #7f8c8d;
}

.crcm-total-price {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.crcm-total-label {
    color: #7f8c8d;
}

.crcm-total-amount {
    font-weight: 700;
    color: #27ae60;
}

.crcm-total-period {
    color: #7f8c8d;
    font-size: 12px;
}

.crcm-vehicle-actions {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 10px;
}

.crcm-book-btn {
    background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
}

.crcm-book-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
}

.crcm-book-btn.unavailable {
    background: #95a5a6;
    cursor: not-allowed;
}

.crcm-book-btn.unavailable:hover {
    transform: none;
    box-shadow: none;
}

.crcm-details-btn {
    background: #ecf0f1;
    color: #34495e;
    border: none;
    padding: 12px 15px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    font-size: 14px;
}

.crcm-details-btn:hover {
    background: #bdc3c7;
}

.crcm-no-vehicles {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
}

.crcm-no-vehicles-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.6;
}

.crcm-no-vehicles h3 {
    font-size: 24px;
    color: #2c3e50;
    margin: 0 0 10px 0;
}

.crcm-no-vehicles p {
    color: #7f8c8d;
    margin: 0 0 30px 0;
    font-size: 16px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .crcm-vehicle-list-container {
        padding: 15px;
    }
    
    .crcm-search-summary {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .crcm-filters-wrapper {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .crcm-results-count {
        text-align: left;
    }
    
    .crcm-vehicles-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .crcm-vehicle-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Vehicle type filter
    $('#vehicle-type-filter').on('change', function() {
        const selectedType = $(this).val();
        const $cards = $('.crcm-vehicle-card');
        
        if (selectedType === '') {
            $cards.show();
        } else {
            $cards.each(function() {
                const cardType = $(this).data('vehicle-type');
                if (cardType === selectedType) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
        
        updateResultsCount();
    });
    
    // Price sorting
    $('#price-sort-filter').on('change', function() {
        const sortType = $(this).val();
        const $grid = $('#vehicles-grid');
        const $cards = $('.crcm-vehicle-card').toArray();
        
        if (sortType === 'price-asc' || sortType === 'price-desc') {
            $cards.sort(function(a, b) {
                const priceA = parseFloat($(a).data('daily-rate'));
                const priceB = parseFloat($(b).data('daily-rate'));
                
                if (sortType === 'price-asc') {
                    return priceA - priceB;
                } else {
                    return priceB - priceA;
                }
            });
            
            $grid.empty().append($cards);
        }
    });
    
    // Book now button click
    $(document).on('click', '.crcm-book-now', function() {
        const vehicleId = $(this).data('vehicle-id');
        const pickupDate = $(this).data('pickup-date');
        const returnDate = $(this).data('return-date');
        const pickupTime = $(this).data('pickup-time');
        const returnTime = $(this).data('return-time');
        
        // Build booking URL with parameters
        const bookingUrl = new URL(window.location.origin + '/booking-form/');
        bookingUrl.searchParams.set('vehicle', vehicleId);
        bookingUrl.searchParams.set('pickup_date', pickupDate);
        bookingUrl.searchParams.set('return_date', returnDate);
        bookingUrl.searchParams.set('pickup_time', pickupTime);
        bookingUrl.searchParams.set('return_time', returnTime);
        
        window.location.href = bookingUrl.toString();
    });
    
    // Update results count
    function updateResultsCount() {
        const visibleCards = $('.crcm-vehicle-card:visible').length;
        $('.crcm-results-count').text('Trovati ' + visibleCards + ' veicoli');
    }
});
</script>
