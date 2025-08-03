<?php
/**
 * Frontend Search Form Template
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$vehicle_types = crcm_get_vehicle_types();
$locations = crcm_get_locations();
?>

<div class="crcm-search-form-wrapper">
    <form class="crcm-search-form" id="crcm-vehicle-search">
        <div class="crcm-search-header">
            <h2><?php _e('Find Your Perfect Rental', 'custom-rental-manager'); ?></h2>
            <p><?php _e('Search available vehicles for your dates', 'custom-rental-manager'); ?></p>
        </div>

        <div class="crcm-search-fields">
            <div class="crcm-field-group">
                <label for="pickup_date"><?php _e('Pickup Date', 'custom-rental-manager'); ?></label>
                <input type="date" id="pickup_date" name="pickup_date" required />
            </div>

            <div class="crcm-field-group">
                <label for="return_date"><?php _e('Return Date', 'custom-rental-manager'); ?></label>
                <input type="date" id="return_date" name="return_date" required />
            </div>

            <div class="crcm-field-group">
                <label for="vehicle_type"><?php _e('Vehicle Type', 'custom-rental-manager'); ?></label>
                <select id="vehicle_type" name="vehicle_type">
                    <option value=""><?php _e('All Types', 'custom-rental-manager'); ?></option>
                    <?php foreach ($vehicle_types as $type): ?>
                        <option value="<?php echo esc_attr($type->term_id); ?>">
                            <?php echo esc_html($type->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="crcm-field-group">
                <label for="pickup_location"><?php _e('Pickup Location', 'custom-rental-manager'); ?></label>
                <select id="pickup_location" name="pickup_location">
                    <option value=""><?php _e('Select Location', 'custom-rental-manager'); ?></option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo esc_attr($location->term_id); ?>">
                            <?php echo esc_html($location->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="crcm-field-group">
                <button type="submit" class="crcm-search-btn">
                    <?php _e('Search Vehicles', 'custom-rental-manager'); ?>
                </button>
            </div>
        </div>

        <?php wp_nonce_field('crcm_nonce', 'crcm_nonce'); ?>
    </form>

    <div id="crcm-search-results" class="crcm-search-results" style="display: none;">
        <h3><?php _e('Available Vehicles', 'custom-rental-manager'); ?></h3>
        <div id="crcm-results-content"></div>
    </div>
</div>

<style>
.crcm-search-form-wrapper {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.crcm-search-form {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.crcm-search-header {
    text-align: center;
    margin-bottom: 30px;
}

.crcm-search-header h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
}

.crcm-search-header p {
    margin: 0;
    opacity: 0.9;
}

.crcm-search-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

.crcm-field-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.crcm-field-group input,
.crcm-field-group select {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    background: rgba(255,255,255,0.9);
}

.crcm-search-btn {
    width: 100%;
    padding: 12px 24px;
    background: #ff6b6b;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
}

.crcm-search-btn:hover {
    background: #ff5252;
}

.crcm-search-results {
    margin-top: 30px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .crcm-search-fields {
        grid-template-columns: 1fr;
    }
}
</style>
