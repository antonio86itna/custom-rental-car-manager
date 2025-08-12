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
$preferred_language = get_user_meta($current_user->ID, 'crcm_preferred_language', true) ?: 'it';
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
                    $booking_data    = get_post_meta($booking->ID, '_crcm_booking_data', true);
                    $booking_status  = get_post_meta($booking->ID, '_crcm_booking_status', true);
                    $booking_number  = get_post_meta($booking->ID, '_crcm_booking_number', true);
                    $payment_data    = get_post_meta($booking->ID, '_crcm_payment_data', true);
                    $payment_status  = $payment_data['payment_status'] ?? '';
                    $vehicle         = get_post($booking_data['vehicle_id']);
                    $pricing_breakdown = get_post_meta($booking->ID, '_crcm_pricing_breakdown', true);
                    $currency_symbol  = crcm_get_setting('currency_symbol', '€');
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
                            <?php if (!empty($pricing_breakdown['line_items'])) : ?>
                                <ul class="crcm-price-details">
                                    <?php foreach ($pricing_breakdown['line_items'] as $item) : ?>
                                        <?php
                                        $label  = crcm_format_line_item_label($item, $currency_symbol);
                                        $amount = crcm_format_price((float) ($item['amount'] ?? 0), $currency_symbol);
                                        ?>
                                        <li><?php echo $label; ?> - <?php echo $amount; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (!empty($pricing_breakdown['final_total'])) : ?>
                                    <p><strong><?php _e('Totale:', 'custom-rental-manager'); ?></strong> <?php echo crcm_format_price((float) $pricing_breakdown['final_total'], $currency_symbol); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <?php
                        $show_pay_button = ('completed' !== $payment_status);
                        $can_cancel      = in_array($booking_status, array('pending', 'confirmed'), true);
                        if ($show_pay_button || $can_cancel) :
                        ?>
                            <div class="crcm-booking-actions">
                                <?php if ($show_pay_button) : ?>
                                    <a class="crcm-btn crcm-btn-primary" href="<?php echo esc_url(crcm()->payment_manager->get_checkout_url($booking->ID)); ?>">
                                        <?php _e('Paga ora', 'custom-rental-manager'); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ($can_cancel) : ?>
                                    <button class="crcm-btn crcm-btn-danger" onclick="cancelBooking('<?php echo esc_attr($booking->ID); ?>')">
                                        <?php _e('Cancel Booking', 'custom-rental-manager'); ?>
                                    </button>
                                <?php endif; ?>
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

            <div class="crcm-form-group">
                <label for="profile_preferred_language"><?php _e('Preferred Language', 'custom-rental-manager'); ?></label>
                <select id="profile_preferred_language" name="preferred_language">
                    <option value="it" <?php selected($preferred_language, 'it'); ?>><?php _e('Italian', 'custom-rental-manager'); ?></option>
                    <option value="en" <?php selected($preferred_language, 'en'); ?>><?php _e('English', 'custom-rental-manager'); ?></option>
                </select>
            </div>

            <button type="submit" class="crcm-btn crcm-btn-primary">
                <?php _e('Update Profile', 'custom-rental-manager'); ?>
            </button>
        </form>
    </div>
</div>

