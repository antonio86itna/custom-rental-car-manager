<?php
/**
 * Frontend Vehicle List Template
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get vehicles
$args = array(
    'post_type' => 'crcm_vehicle',
    'post_status' => 'publish',
    'posts_per_page' => $atts['limit'],
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
?>

<div class="crcm-vehicle-list">
    <?php if (!empty($vehicles)): ?>
        <div class="crcm-vehicles-grid">
            <?php foreach ($vehicles as $vehicle): 
                $vehicle_data = get_post_meta($vehicle->ID, '_crcm_vehicle_data', true);
                $pricing_data = get_post_meta($vehicle->ID, '_crcm_pricing_data', true);
                $daily_rate = $pricing_data['daily_rate'] ?? 0;
                $vehicle_type = wp_get_post_terms($vehicle->ID, 'crcm_vehicle_type');
                $thumbnail = get_the_post_thumbnail($vehicle->ID, 'medium');
            ?>
                <div class="crcm-vehicle-card">
                    <div class="crcm-vehicle-image">
                        <?php if ($thumbnail): ?>
                            <?php echo $thumbnail; ?>
                        <?php else: ?>
                            <div class="crcm-no-image">
                                <span class="dashicons dashicons-car"></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="crcm-vehicle-content">
                        <h3><?php echo esc_html($vehicle->post_title); ?></h3>

                        <?php if (!empty($vehicle_type)): ?>
                            <div class="crcm-vehicle-type">
                                <?php echo esc_html($vehicle_type[0]->name); ?>
                            </div>
                        <?php endif; ?>

                        <div class="crcm-vehicle-specs">
                            <?php if (isset($vehicle_data['seats'])): ?>
                                <span class="crcm-spec">
                                    <i class="dashicons dashicons-admin-users"></i>
                                    <?php echo esc_html($vehicle_data['seats']); ?> <?php _e('seats', 'custom-rental-manager'); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (isset($vehicle_data['transmission'])): ?>
                                <span class="crcm-spec">
                                    <i class="dashicons dashicons-admin-settings"></i>
                                    <?php echo esc_html(ucfirst($vehicle_data['transmission'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="crcm-vehicle-price">
                            <span class="crcm-price">
                                <?php echo crcm_format_price($daily_rate, $currency_symbol); ?>
                                <small>/<?php _e('day', 'custom-rental-manager'); ?></small>
                            </span>
                        </div>

                        <div class="crcm-vehicle-actions">
                            <a href="#" class="crcm-btn crcm-btn-primary" data-vehicle-id="<?php echo esc_attr($vehicle->ID); ?>">
                                <?php _e('Book Now', 'custom-rental-manager'); ?>
                            </a>
                            <a href="<?php echo esc_url(get_permalink($vehicle->ID)); ?>" class="crcm-btn crcm-btn-secondary">
                                <?php _e('View Details', 'custom-rental-manager'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="crcm-no-vehicles">
            <p><?php _e('No vehicles found.', 'custom-rental-manager'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.crcm-vehicles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin: 20px 0;
}

.crcm-vehicle-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.crcm-vehicle-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
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
}

.crcm-no-image {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.crcm-no-image .dashicons {
    font-size: 60px;
    color: white;
}

.crcm-vehicle-content {
    padding: 20px;
}

.crcm-vehicle-content h3 {
    margin: 0 0 10px 0;
    font-size: 20px;
    color: #1a1a1a;
}

.crcm-vehicle-type {
    display: inline-block;
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-bottom: 15px;
}

.crcm-vehicle-specs {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.crcm-spec {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    color: #666;
}

.crcm-spec .dashicons {
    font-size: 16px;
}

.crcm-vehicle-price {
    margin-bottom: 20px;
}

.crcm-price {
    font-size: 24px;
    font-weight: bold;
    color: #2563eb;
}

.crcm-price small {
    font-size: 14px;
    color: #666;
    font-weight: normal;
}

.crcm-vehicle-actions {
    display: flex;
    gap: 10px;
}

.crcm-btn {
    flex: 1;
    padding: 12px 16px;
    border-radius: 6px;
    text-align: center;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}

.crcm-btn-primary {
    background: #2563eb;
    color: white;
}

.crcm-btn-primary:hover {
    background: #1d4ed8;
    color: white;
}

.crcm-btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.crcm-btn-secondary:hover {
    background: #e5e7eb;
    color: #374151;
}

.crcm-no-vehicles {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

@media (max-width: 768px) {
    .crcm-vehicles-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .crcm-vehicle-actions {
        flex-direction: column;
    }
}
</style>
