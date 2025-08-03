<?php
/**
 * Admin Dashboard Template
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard statistics
$stats = crcm_get_dashboard_stats();
$upcoming_bookings = crcm_get_upcoming_bookings(5);
$currency_symbol = crcm_get_setting('currency_symbol', '€');
?>

<div class="wrap crcm-dashboard">
    <h1><?php _e('Rental Manager Dashboard', 'custom-rental-manager'); ?></h1>

    <!-- Statistics Cards -->
    <div class="crcm-stats-grid">
        <div class="crcm-stat-card">
            <div class="crcm-stat-icon">
                <span class="dashicons dashicons-car"></span>
            </div>
            <div class="crcm-stat-content">
                <h3><?php echo number_format($stats['total_vehicles']); ?></h3>
                <p><?php _e('Total Vehicles', 'custom-rental-manager'); ?></p>
            </div>
        </div>

        <div class="crcm-stat-card">
            <div class="crcm-stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="crcm-stat-content">
                <h3><?php echo number_format($stats['total_bookings']); ?></h3>
                <p><?php _e('Total Bookings', 'custom-rental-manager'); ?></p>
            </div>
        </div>

        <div class="crcm-stat-card">
            <div class="crcm-stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="crcm-stat-content">
                <h3><?php echo crcm_format_price($stats['monthly_revenue'], $currency_symbol); ?></h3>
                <p><?php _e('This Month Revenue', 'custom-rental-manager'); ?></p>
            </div>
        </div>

        <div class="crcm-stat-card">
            <div class="crcm-stat-icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="crcm-stat-content">
                <h3><?php echo number_format($stats['active_bookings']); ?></h3>
                <p><?php _e('Active Rentals', 'custom-rental-manager'); ?></p>
            </div>
        </div>
    </div>

    <!-- Today's Activity -->
    <div class="crcm-dashboard-section">
        <h2><?php _e('Today's Activity', 'custom-rental-manager'); ?></h2>
        <div class="crcm-today-activity">
            <div class="crcm-activity-card">
                <h3><?php echo number_format($stats['todays_pickups']); ?></h3>
                <p><?php _e('Pickups Today', 'custom-rental-manager'); ?></p>
                <span class="dashicons dashicons-arrow-up-alt"></span>
            </div>
            <div class="crcm-activity-card">
                <h3><?php echo number_format($stats['todays_returns']); ?></h3>
                <p><?php _e('Returns Today', 'custom-rental-manager'); ?></p>
                <span class="dashicons dashicons-arrow-down-alt"></span>
            </div>
        </div>
    </div>

    <!-- Booking Status Overview -->
    <div class="crcm-dashboard-section">
        <h2><?php _e('Booking Status Overview', 'custom-rental-manager'); ?></h2>
        <div class="crcm-status-overview">
            <div class="crcm-status-item">
                <span class="crcm-status-count"><?php echo number_format($stats['pending_bookings']); ?></span>
                <span class="crcm-status-label"><?php _e('Pending', 'custom-rental-manager'); ?></span>
            </div>
            <div class="crcm-status-item">
                <span class="crcm-status-count"><?php echo number_format($stats['confirmed_bookings']); ?></span>
                <span class="crcm-status-label"><?php _e('Confirmed', 'custom-rental-manager'); ?></span>
            </div>
            <div class="crcm-status-item">
                <span class="crcm-status-count"><?php echo number_format($stats['active_bookings']); ?></span>
                <span class="crcm-status-label"><?php _e('Active', 'custom-rental-manager'); ?></span>
            </div>
            <div class="crcm-status-item">
                <span class="crcm-status-count"><?php echo number_format($stats['completed_bookings']); ?></span>
                <span class="crcm-status-label"><?php _e('Completed', 'custom-rental-manager'); ?></span>
            </div>
        </div>
    </div>

    <!-- Upcoming Bookings -->
    <div class="crcm-dashboard-section">
        <h2><?php _e('Upcoming Bookings', 'custom-rental-manager'); ?></h2>
        <?php if (!empty($upcoming_bookings)): ?>
            <div class="crcm-upcoming-bookings">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Booking', 'custom-rental-manager'); ?></th>
                            <th><?php _e('Customer', 'custom-rental-manager'); ?></th>
                            <th><?php _e('Vehicle', 'custom-rental-manager'); ?></th>
                            <th><?php _e('Date', 'custom-rental-manager'); ?></th>
                            <th><?php _e('Status', 'custom-rental-manager'); ?></th>
                            <th><?php _e('Actions', 'custom-rental-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_bookings as $booking): 
                            $booking_data = maybe_unserialize($booking['booking_data']);
                            $booking_number = get_post_meta($booking['ID'], '_crcm_booking_number', true);
                            $customer_data = get_post_meta($booking['ID'], '_crcm_customer_data', true);
                            $vehicle = get_post($booking_data['vehicle_id']);
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($booking_number); ?></strong>
                                </td>
                                <td>
                                    <?php if ($customer_data): ?>
                                        <?php echo esc_html($customer_data['first_name'] . ' ' . $customer_data['last_name']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($vehicle): ?>
                                        <?php echo esc_html($vehicle->post_title); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($booking_data['pickup_date'])): ?>
                                        <?php echo esc_html(crcm_format_date($booking_data['pickup_date'])); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo crcm_get_status_badge($booking['status']); ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($booking['ID'])); ?>" class="button button-small">
                                        <?php _e('Edit', 'custom-rental-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p><?php _e('No upcoming bookings found.', 'custom-rental-manager'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="crcm-dashboard-section">
        <h2><?php _e('Quick Actions', 'custom-rental-manager'); ?></h2>
        <div class="crcm-quick-actions">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=crcm_vehicle')); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add New Vehicle', 'custom-rental-manager'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=crcm_booking')); ?>" class="button button-secondary">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('New Booking', 'custom-rental-manager'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=crcm-calendar')); ?>" class="button button-secondary">
                <span class="dashicons dashicons-calendar"></span>
                <?php _e('View Calendar', 'custom-rental-manager'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=crcm-settings')); ?>" class="button button-secondary">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Settings', 'custom-rental-manager'); ?>
            </a>
        </div>
    </div>

    <!-- Totaliweb Credit -->
    <?php if (crcm_get_setting('show_totaliweb_credit', true)): ?>
    <div class="crcm-dashboard-footer">
        <p>
            <?php printf(
                __('Custom Rental Car Manager developed by %s', 'custom-rental-manager'),
                '<a href="' . CRCM_BRAND_URL . '" target="_blank">Totaliweb</a>'
            ); ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<style>
.crcm-dashboard {
    background: #f1f1f1;
    margin: 20px 0;
    padding: 0;
}

.crcm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.crcm-stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
}

.crcm-stat-icon {
    margin-right: 15px;
}

.crcm-stat-icon .dashicons {
    font-size: 40px;
    color: #2271b1;
    width: 40px;
    height: 40px;
}

.crcm-stat-content h3 {
    margin: 0;
    font-size: 28px;
    color: #1d2327;
}

.crcm-stat-content p {
    margin: 5px 0 0 0;
    color: #646970;
    font-size: 14px;
}

.crcm-dashboard-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.crcm-dashboard-section h2 {
    margin-top: 0;
    color: #1d2327;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 10px;
}

.crcm-today-activity {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.crcm-activity-card {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    position: relative;
}

.crcm-activity-card h3 {
    font-size: 36px;
    margin: 0;
    color: #2271b1;
}

.crcm-activity-card .dashicons {
    position: absolute;
    top: 10px;
    right: 10px;
    color: #2271b1;
    font-size: 20px;
}

.crcm-status-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.crcm-status-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.crcm-status-count {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.crcm-status-label {
    font-size: 14px;
    color: #646970;
}

.crcm-quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.crcm-quick-actions .button {
    display: flex;
    align-items: center;
    gap: 5px;
}

.crcm-dashboard-footer {
    text-align: center;
    padding: 20px;
    color: #646970;
    font-size: 12px;
}

.crcm-dashboard-footer a {
    color: #2271b1;
    text-decoration: none;
}

@media (max-width: 768px) {
    .crcm-stats-grid {
        grid-template-columns: 1fr;
    }

    .crcm-quick-actions {
        flex-direction: column;
    }

    .crcm-quick-actions .button {
        width: 100%;
        justify-content: center;
    }
}
</style>
