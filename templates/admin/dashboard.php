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

$stats = crcm_get_dashboard_stats();
$calendar_manager = new CRCM_Calendar_Manager();
$upcoming_bookings = $calendar_manager->get_upcoming_bookings(7);
$vehicles_out_today = $calendar_manager->get_vehicles_out_today();
$vehicles_returning_today = $calendar_manager->get_vehicles_returning_today();
?>

<div class="wrap crcm-dashboard">
    <div class="crcm-dashboard-header">
        <h1><?php _e('Rental Manager Dashboard', 'custom-rental-manager'); ?></h1>
        <p class="crcm-subtitle"><?php printf(__('Welcome to %s management dashboard', 'custom-rental-manager'), crcm()->get_setting('company_name', 'Costabilerent')); ?></p>
    </div>

    <!-- Stats Cards -->
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
                <h3><?php echo number_format($stats['bookings_this_month']); ?></h3>
                <p><?php _e('Bookings This Month', 'custom-rental-manager'); ?></p>
            </div>
        </div>

        <div class="crcm-stat-card">
            <div class="crcm-stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="crcm-stat-content">
                <h3><?php echo crcm_format_price($stats['revenue_this_month']); ?></h3>
                <p><?php _e('Revenue This Month', 'custom-rental-manager'); ?></p>
            </div>
        </div>

        <div class="crcm-stat-card">
            <div class="crcm-stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="crcm-stat-content">
                <h3><?php echo number_format($stats['active_bookings']); ?></h3>
                <p><?php _e('Active Rentals', 'custom-rental-manager'); ?></p>
            </div>
        </div>
    </div>

    <!-- Today's Activity -->
    <div class="crcm-dashboard-content">
        <div class="crcm-dashboard-section">
            <h2><?php _e('Today's Activity', 'custom-rental-manager'); ?></h2>

            <div class="crcm-today-grid">
                <!-- Vehicles Going Out -->
                <div class="crcm-activity-card">
                    <h3><?php _e('Vehicles Going Out', 'custom-rental-manager'); ?> <span class="count">(<?php echo count($vehicles_out_today); ?>)</span></h3>

                    <?php if (!empty($vehicles_out_today)): ?>
                        <ul class="crcm-activity-list">
                            <?php foreach ($vehicles_out_today as $booking): ?>
                                <?php
                                $booking_data = get_post_meta($booking->ID, '_crcm_booking_data', true);
                                $customer_data = get_post_meta($booking->ID, '_crcm_customer_data', true);
                                $booking_number = get_post_meta($booking->ID, '_crcm_booking_number', true);
                                $vehicle = get_post($booking_data['vehicle_id']);
                                ?>
                                <li>
                                    <strong><?php echo esc_html($booking_number); ?></strong><br>
                                    <?php echo esc_html($customer_data['first_name'] . ' ' . $customer_data['last_name']); ?><br>
                                    <small><?php echo $vehicle ? esc_html($vehicle->post_title) : ''; ?> - <?php echo esc_html($booking_data['pickup_time']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="crcm-no-activity"><?php _e('No vehicles going out today', 'custom-rental-manager'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Vehicles Coming Back -->
                <div class="crcm-activity-card">
                    <h3><?php _e('Vehicles Coming Back', 'custom-rental-manager'); ?> <span class="count">(<?php echo count($vehicles_returning_today); ?>)</span></h3>

                    <?php if (!empty($vehicles_returning_today)): ?>
                        <ul class="crcm-activity-list">
                            <?php foreach ($vehicles_returning_today as $booking): ?>
                                <?php
                                $booking_data = get_post_meta($booking->ID, '_crcm_booking_data', true);
                                $customer_data = get_post_meta($booking->ID, '_crcm_customer_data', true);
                                $booking_number = get_post_meta($booking->ID, '_crcm_booking_number', true);
                                $vehicle = get_post($booking_data['vehicle_id']);
                                ?>
                                <li>
                                    <strong><?php echo esc_html($booking_number); ?></strong><br>
                                    <?php echo esc_html($customer_data['first_name'] . ' ' . $customer_data['last_name']); ?><br>
                                    <small><?php echo $vehicle ? esc_html($vehicle->post_title) : ''; ?> - <?php echo esc_html($booking_data['return_time']); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="crcm-no-activity"><?php _e('No vehicles returning today', 'custom-rental-manager'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="crcm-dashboard-section">
            <h2><?php _e('Upcoming Bookings (Next 7 Days)', 'custom-rental-manager'); ?></h2>

            <?php if (!empty($upcoming_bookings)): ?>
                <div class="crcm-upcoming-bookings">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Booking #', 'custom-rental-manager'); ?></th>
                                <th><?php _e('Customer', 'custom-rental-manager'); ?></th>
                                <th><?php _e('Vehicle', 'custom-rental-manager'); ?></th>
                                <th><?php _e('Pickup Date', 'custom-rental-manager'); ?></th>
                                <th><?php _e('Status', 'custom-rental-manager'); ?></th>
                                <th><?php _e('Actions', 'custom-rental-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_bookings as $booking): ?>
                                <?php
                                $booking_data = get_post_meta($booking->ID, '_crcm_booking_data', true);
                                $customer_data = get_post_meta($booking->ID, '_crcm_customer_data', true);
                                $booking_number = get_post_meta($booking->ID, '_crcm_booking_number', true);
                                $booking_status = get_post_meta($booking->ID, '_crcm_booking_status', true);
                                $vehicle = get_post($booking_data['vehicle_id']);
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($booking_number); ?></strong></td>
                                    <td>
                                        <?php echo esc_html($customer_data['first_name'] . ' ' . $customer_data['last_name']); ?><br>
                                        <small><?php echo esc_html($customer_data['email']); ?></small>
                                    </td>
                                    <td><?php echo $vehicle ? esc_html($vehicle->post_title) : '-'; ?></td>
                                    <td><?php echo crcm_format_date($booking_data['pickup_date']); ?><br><small><?php echo esc_html($booking_data['pickup_time']); ?></small></td>
                                    <td><?php echo crcm_get_status_badge($booking_status); ?></td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($booking->ID); ?>" class="button button-small"><?php _e('Edit', 'custom-rental-manager'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p><?php _e('No upcoming bookings in the next 7 days.', 'custom-rental-manager'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="crcm-dashboard-section">
            <h2><?php _e('Quick Actions', 'custom-rental-manager'); ?></h2>

            <div class="crcm-quick-actions">
                <a href="<?php echo admin_url('post-new.php?post_type=crcm_vehicle'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add New Vehicle', 'custom-rental-manager'); ?>
                </a>

                <a href="<?php echo admin_url('post-new.php?post_type=crcm_booking'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Create Manual Booking', 'custom-rental-manager'); ?>
                </a>

                <a href="<?php echo admin_url('admin.php?page=crcm-calendar'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-calendar"></span>
                    <?php _e('View Calendar', 'custom-rental-manager'); ?>
                </a>

                <a href="<?php echo admin_url('admin.php?page=crcm-settings'); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Plugin Settings', 'custom-rental-manager'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Totaliweb Credit -->
    <?php if (crcm()->get_setting('show_totaliweb_credit', true)): ?>
    <div class="crcm-totaliweb-credit">
        <p><?php printf(__('Powered by %s', 'custom-rental-manager'), '<a href="' . CRCM_BRAND_URL . '" target="_blank">Totaliweb</a>'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.crcm-dashboard-header {
    margin-bottom: 30px;
}

.crcm-subtitle {
    color: #666;
    font-size: 16px;
    margin-top: 5px;
}

.crcm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.crcm-stat-card {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.crcm-stat-icon {
    margin-right: 15px;
}

.crcm-stat-icon .dashicons {
    font-size: 40px;
    color: #2563eb;
    width: 40px;
    height: 40px;
}

.crcm-stat-content h3 {
    margin: 0;
    font-size: 28px;
    font-weight: bold;
    color: #333;
}

.crcm-stat-content p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 14px;
}

.crcm-dashboard-section {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.crcm-dashboard-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
}

.crcm-today-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.crcm-activity-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 15px;
}

.crcm-activity-card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
}

.crcm-activity-card .count {
    color: #2563eb;
    font-weight: normal;
}

.crcm-activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.crcm-activity-list li {
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.crcm-activity-list li:last-child {
    border-bottom: none;
}

.crcm-no-activity {
    color: #666;
    font-style: italic;
    margin: 0;
}

.crcm-quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.crcm-quick-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.crcm-totaliweb-credit {
    text-align: center;
    margin-top: 30px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.crcm-totaliweb-credit p {
    margin: 0;
    color: #666;
    font-size: 12px;
}

.crcm-status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.crcm-status-pending { background: #fef3cd; color: #856404; }
.crcm-status-confirmed { background: #d1ecf1; color: #0c5460; }
.crcm-status-active { background: #d4edda; color: #155724; }
.crcm-status-completed { background: #e2e3e5; color: #383d41; }
.crcm-status-cancelled { background: #f8d7da; color: #721c24; }
.crcm-status-refunded { background: #fce4ec; color: #ad1457; }

@media (max-width: 768px) {
    .crcm-stats-grid {
        grid-template-columns: 1fr;
    }

    .crcm-today-grid {
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
