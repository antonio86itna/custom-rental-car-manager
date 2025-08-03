<?php
/**
 * Frontend Customer Dashboard Template
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_bookings = get_posts(array(
    'post_type' => 'crcm_booking',
    'meta_query' => array(
        array(
            'key' => '_crcm_customer_data',
            'value' => $current_user->user_email,
            'compare' => 'LIKE',
        ),
    ),
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
));
?>

<div class="crcm-customer-dashboard">
    <div class="crcm-dashboard-header">
        <h2><?php printf(__('Welcome, %s!', 'custom-rental-manager'), esc_html($current_user->first_name)); ?></h2>
        <p><?php _e('Manage your bookings and account information', 'custom-rental-manager'); ?></p>
    </div>

    <div class="crcm-dashboard-tabs">
        <button class="crcm-tab-btn active" data-tab="bookings"><?php _e('My Bookings', 'custom-rental-manager'); ?></button>
        <button class="crcm-tab-btn" data-tab="profile"><?php _e('Profile', 'custom-rental-manager'); ?></button>
    </div>

    <div class="crcm-tab-content" id="bookings-tab">
        <h3><?php _e('Your Bookings', 'custom-rental-manager'); ?></h3>

        <?php if (!empty($user_bookings)): ?>
            <div class="crcm-bookings-list">
                <?php foreach ($user_bookings as $booking): 
                    $booking_data = get_post_meta($booking->ID, '_crcm_booking_data', true);
                    $booking_status = get_post_meta($booking->ID, '_crcm_booking_status', true);
                    $booking_number = get_post_meta($booking->ID, '_crcm_booking_number', true);
                    $vehicle = get_post($booking_data['vehicle_id']);
                ?>
                    <div class="crcm-booking-item">
                        <div class="crcm-booking-header">
                            <h4><?php echo esc_html($booking_number); ?></h4>
                            <?php echo crcm_get_status_badge($booking_status); ?>
                        </div>

                        <div class="crcm-booking-details">
                            <p><strong><?php _e('Vehicle:', 'custom-rental-manager'); ?></strong> <?php echo $vehicle ? esc_html($vehicle->post_title) : __('Unknown', 'custom-rental-manager'); ?></p>
                            <p><strong><?php _e('Dates:', 'custom-rental-manager'); ?></strong> 
                                <?php echo esc_html(crcm_format_date($booking_data['pickup_date'])); ?> - 
                                <?php echo esc_html(crcm_format_date($booking_data['return_date'])); ?>
                            </p>
                            <p><strong><?php _e('Booked:', 'custom-rental-manager'); ?></strong> <?php echo esc_html(crcm_format_date($booking->post_date)); ?></p>
                        </div>

                        <?php if ($booking_status === 'pending' || $booking_status === 'confirmed'): ?>
                            <div class="crcm-booking-actions">
                                <button class="crcm-btn crcm-btn-danger" onclick="cancelBooking('<?php echo esc_attr($booking->ID); ?>')">
                                    <?php _e('Cancel Booking', 'custom-rental-manager'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="crcm-no-bookings">
                <p><?php _e('You have no bookings yet.', 'custom-rental-manager'); ?></p>
                <a href="#" class="crcm-btn crcm-btn-primary"><?php _e('Make a Booking', 'custom-rental-manager'); ?></a>
            </div>
        <?php endif; ?>
    </div>

    <div class="crcm-tab-content" id="profile-tab" style="display: none;">
        <h3><?php _e('Profile Information', 'custom-rental-manager'); ?></h3>

        <form class="crcm-profile-form">
            <div class="crcm-form-row">
                <div class="crcm-form-group">
                    <label for="profile_first_name"><?php _e('First Name', 'custom-rental-manager'); ?></label>
                    <input type="text" id="profile_first_name" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>" />
                </div>
                <div class="crcm-form-group">
                    <label for="profile_last_name"><?php _e('Last Name', 'custom-rental-manager'); ?></label>
                    <input type="text" id="profile_last_name" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" />
                </div>
            </div>

            <div class="crcm-form-group">
                <label for="profile_email"><?php _e('Email', 'custom-rental-manager'); ?></label>
                <input type="email" id="profile_email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" />
            </div>

            <button type="submit" class="crcm-btn crcm-btn-primary">
                <?php _e('Update Profile', 'custom-rental-manager'); ?>
            </button>
        </form>
    </div>
</div>

<style>
.crcm-customer-dashboard {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.crcm-dashboard-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
}

.crcm-dashboard-tabs {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.crcm-tab-btn {
    padding: 15px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
}

.crcm-tab-btn.active {
    border-bottom-color: #2563eb;
    color: #2563eb;
}

.crcm-booking-item {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 15px;
}

.crcm-booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.crcm-booking-details p {
    margin: 5px 0;
    color: #666;
}

.crcm-no-bookings {
    text-align: center;
    padding: 40px 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.crcm-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}

.crcm-btn-primary {
    background: #2563eb;
    color: white;
}

.crcm-btn-danger {
    background: #dc2626;
    color: white;
}

.crcm-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.crcm-form-group {
    margin-bottom: 15px;
}

.crcm-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.crcm-form-group input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

@media (max-width: 768px) {
    .crcm-form-row {
        grid-template-columns: 1fr;
    }

    .crcm-booking-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>
