<?php
/**
 * Frontend Booking Form Template
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$vehicle_id = $atts['vehicle_id'];
$vehicle = null;

if ($vehicle_id) {
    $vehicle = get_post($vehicle_id);
}

$locations = crcm_get_locations();
?>

<div class="crcm-booking-form-wrapper">
    <?php if ($vehicle): ?>
        <div class="crcm-vehicle-info">
            <h3><?php echo esc_html($vehicle->post_title); ?></h3>
            <?php if (has_post_thumbnail($vehicle->ID)): ?>
                <?php echo get_the_post_thumbnail($vehicle->ID, 'medium'); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <form class="crcm-booking-form" id="crcm-booking-form">
        <h2><?php _e('Book Your Rental', 'custom-rental-manager'); ?></h2>

        <!-- Rental Details -->
        <div class="crcm-form-section">
            <h3><?php _e('Rental Details', 'custom-rental-manager'); ?></h3>

            <div class="crcm-form-row">
                <div class="crcm-form-group">
                    <label for="pickup_date"><?php _e('Pickup Date', 'custom-rental-manager'); ?> *</label>
                    <input type="date" id="pickup_date" name="pickup_date" required />
                </div>
                <div class="crcm-form-group">
                    <label for="return_date"><?php _e('Return Date', 'custom-rental-manager'); ?> *</label>
                    <input type="date" id="return_date" name="return_date" required />
                </div>
            </div>

            <div class="crcm-form-row">
                <div class="crcm-form-group">
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
                <div class="crcm-form-group">
                    <label>
                        <input type="checkbox" id="home_delivery" name="home_delivery" value="1" />
                        <?php _e('Home Delivery Service', 'custom-rental-manager'); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="crcm-form-section">
            <h3><?php _e('Customer Information', 'custom-rental-manager'); ?></h3>

            <div class="crcm-form-row">
                <div class="crcm-form-group">
                    <label for="first_name"><?php _e('First Name', 'custom-rental-manager'); ?> *</label>
                    <input type="text" id="first_name" name="customer_data[first_name]" required />
                </div>
                <div class="crcm-form-group">
                    <label for="last_name"><?php _e('Last Name', 'custom-rental-manager'); ?> *</label>
                    <input type="text" id="last_name" name="customer_data[last_name]" required />
                </div>
            </div>

            <div class="crcm-form-row">
                <div class="crcm-form-group">
                    <label for="email"><?php _e('Email', 'custom-rental-manager'); ?> *</label>
                    <input type="email" id="email" name="customer_data[email]" required />
                </div>
                <div class="crcm-form-group">
                    <label for="phone"><?php _e('Phone', 'custom-rental-manager'); ?> *</label>
                    <input type="tel" id="phone" name="customer_data[phone]" required />
                </div>
            </div>

            <div class="crcm-form-group">
                <label for="license_number"><?php _e('License Number', 'custom-rental-manager'); ?> *</label>
                <input type="text" id="license_number" name="customer_data[license_number]" required />
            </div>
        </div>

        <!-- Extras -->
        <div class="crcm-form-section">
            <h3><?php _e('Extras', 'custom-rental-manager'); ?></h3>

            <div class="crcm-extras">
                <label>
                    <input type="checkbox" name="extras[]" value="helmet" />
                    <?php _e('Helmet (+€5/day)', 'custom-rental-manager'); ?>
                </label>
                <label>
                    <input type="checkbox" name="extras[]" value="child_seat" />
                    <?php _e('Child Seat (+€10/day)', 'custom-rental-manager'); ?>
                </label>
                <label>
                    <input type="checkbox" name="extras[]" value="gps" />
                    <?php _e('GPS Navigation (+€8/day)', 'custom-rental-manager'); ?>
                </label>
            </div>
        </div>

        <div class="crcm-form-actions">
            <button type="submit" class="crcm-submit-btn">
                <?php _e('Submit Booking Request', 'custom-rental-manager'); ?>
            </button>
        </div>

        <?php if ($vehicle_id): ?>
            <input type="hidden" name="vehicle_id" value="<?php echo esc_attr($vehicle_id); ?>" />
        <?php endif; ?>

        <?php wp_nonce_field('crcm_nonce', 'crcm_nonce'); ?>
    </form>
</div>

<style>
.crcm-booking-form-wrapper {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.crcm-booking-form {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.crcm-form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.crcm-form-section:last-child {
    border-bottom: none;
}

.crcm-form-section h3 {
    margin-bottom: 20px;
    color: #1a1a1a;
}

.crcm-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.crcm-form-group {
    margin-bottom: 15px;
}

.crcm-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.crcm-form-group input,
.crcm-form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
}

.crcm-extras {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.crcm-extras label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.crcm-submit-btn {
    width: 100%;
    padding: 15px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
}

.crcm-submit-btn:hover {
    background: #1d4ed8;
}

@media (max-width: 768px) {
    .crcm-form-row {
        grid-template-columns: 1fr;
    }
}
</style>
