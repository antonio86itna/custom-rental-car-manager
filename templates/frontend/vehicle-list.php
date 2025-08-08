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

// Get vehicle types for filtering
$vehicle_types = crcm_get_vehicle_types();

// Get vehicles with availability check
$args = array(
    'post_type'      => 'crcm_vehicle',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
);

$vehicles = get_posts($args);

if (!empty($atts['type'])) {
    $filter_type = $atts['type'];

    if (is_numeric($filter_type)) {
        foreach ($vehicle_types as $type_obj) {
            if ((int) $type_obj->term_id === (int) $filter_type) {
                $filter_type = $type_obj->slug;
                break;
            }
        }
    }

    $vehicles = array_filter($vehicles, function ($vehicle) use ($filter_type) {
        $vehicle_type = crcm_get_vehicle_type($vehicle->ID);
        return $vehicle_type === $filter_type;
    });

    $vehicles = array_values($vehicles);
}

$currency_symbol = crcm_get_setting('currency_symbol', '€');
$vehicle_manager = crcm()->vehicle_manager;

// Calculate rental days
$rental_days = 1;
if ($pickup_date && $return_date) {
    $pickup = new DateTime($pickup_date);
    $return = new DateTime($return_date);
    $rental_days = max(1, $return->diff($pickup)->days);
}
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
                $daily_rate   = $pricing_data['daily_rate'] ?? 0;
                $vehicle_type_slug = crcm_get_vehicle_type($vehicle->ID);
                $vehicle_type_name = '';
                foreach ($vehicle_types as $type) {
                    if ($type->slug === $vehicle_type_slug) {
                        $vehicle_type_name = $type->name;
                        break;
                    }
                }
                $thumbnail = get_the_post_thumbnail($vehicle->ID, 'medium');
                $features  = get_post_meta($vehicle->ID, '_crcm_vehicle_features', true) ?: array();
                
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
                            
                            <?php if (!empty($vehicle_type_name)): ?>
                                <span class="crcm-vehicle-type"><?php echo esc_html($vehicle_type_name); ?></span>
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

