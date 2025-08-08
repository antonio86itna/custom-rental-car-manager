<?php
/**
 * Admin Dashboard Template - FIXED
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
    
    <!-- Dashboard Stats Cards -->
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
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="crcm-stat-content">
                <h3><?php echo number_format($stats['active_bookings']); ?></h3>
                <p><?php _e('Active Rentals', 'custom-rental-manager'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Today's Activity -->
    <div class="crcm-dashboard-section">
        <h2><?php _e('Today\'s Activity', 'custom-rental-manager'); ?></h2>
        
        <div class="crcm-activity-grid">
            <div class="crcm-activity-card">
                <h3><?php echo number_format($stats['todays_pickups']); ?></h3>
                <p><?php _e('Pickups Today', 'custom-rental-manager'); ?></p>
            </div>
            
            <div class="crcm-activity-card">
                <h3><?php echo number_format($stats['todays_returns']); ?></h3>
                <p><?php _e('Returns Today', 'custom-rental-manager'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Booking Status Overview -->
    <div class="crcm-dashboard-section">
        <h2><?php _e('Booking Status Overview', 'custom-rental-manager'); ?></h2>
        
        <div class="crcm-status-grid">
            <div class="crcm-status-item">
                <?php echo number_format($stats['pending_bookings']); ?>
                <?php _e('Pending', 'custom-rental-manager'); ?>
            </div>
            <div class="crcm-status-item">
                <?php echo number_format($stats['confirmed_bookings']); ?>
                <?php _e('Confirmed', 'custom-rental-manager'); ?>
            </div>
            <div class="crcm-status-item">
                <?php echo number_format($stats['active_bookings']); ?>
                <?php _e('Active', 'custom-rental-manager'); ?>
            </div>
            <div class="crcm-status-item">
                <?php echo number_format($stats['completed_bookings']); ?>
                <?php _e('Completed', 'custom-rental-manager'); ?>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Bookings -->
    <div class="crcm-dashboard-section">
        <h2><?php _e('Upcoming Bookings', 'custom-rental-manager'); ?></h2>
        
        <?php if (!empty($upcoming_bookings)): ?>
            <div class="crcm-bookings-table">
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
                                <td><strong><?php echo esc_html($booking_number); ?></strong></td>
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
                                <td><?php echo crcm_get_status_badge($booking['status']); ?></td>
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
            <a href="<?php echo admin_url('post-new.php?post_type=crcm_vehicle'); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add New Vehicle', 'custom-rental-manager'); ?>
            </a>
            <a href="<?php echo admin_url('post-new.php?post_type=crcm_booking'); ?>" class="button button-primary">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('New Booking', 'custom-rental-manager'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=crcm-calendar'); ?>" class="button">
                <span class="dashicons dashicons-calendar"></span>
                <?php _e('View Calendar', 'custom-rental-manager'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=crcm-settings'); ?>" class="button">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Settings', 'custom-rental-manager'); ?>
            </a>
        </div>
    </div>
    
    <!-- Footer Credit -->
    <?php if (crcm_get_setting('show_totaliweb_credit', true)): ?>
        <div class="crcm-footer-credit">
            <p>
                <?php printf(
                    __('Custom Rental Car Manager developed by %s', 'custom-rental-manager'),
                    '<a href="' . CRCM_BRAND_URL . '" target="_blank">Totaliweb</a>'
                ); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

