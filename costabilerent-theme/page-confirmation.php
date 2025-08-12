<?php
/**
 * Booking Confirmation Page
 *
 * @package Costabilerent Theme
 */

get_header();

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$booking    = $booking_id ? crcm()->booking_manager->get_booking($booking_id) : null;

if (!$booking || is_wp_error($booking)) :
    ?>
    <p><?php esc_html_e('Booking not found.', 'custom-rental-manager'); ?></p>
    <?php
    get_footer();
    return;
endif;

$currency_symbol = crcm_get_setting('currency_symbol', '€');
$vehicle_title   = get_the_title($booking['booking_data']['vehicle_id']);
?>

<div class="crcm-confirmation">
    <h1><?php esc_html_e('Booking Confirmed', 'custom-rental-manager'); ?></h1>
    <p>
        <?php
        printf(
            esc_html__('Your booking code is %s', 'custom-rental-manager'),
            '<strong>' . esc_html($booking['booking_number']) . '</strong>'
        );
        ?>
    </p>

    <h2><?php esc_html_e('Summary', 'custom-rental-manager'); ?></h2>
    <ul>
        <li><strong><?php esc_html_e('Vehicle', 'custom-rental-manager'); ?>:</strong> <?php echo esc_html($vehicle_title); ?></li>
        <li><strong><?php esc_html_e('Pickup', 'custom-rental-manager'); ?>:</strong> <?php echo esc_html(date_i18n('d/m/Y', strtotime($booking['booking_data']['pickup_date'])) . ' ' . $booking['booking_data']['pickup_time']); ?></li>
        <li><strong><?php esc_html_e('Return', 'custom-rental-manager'); ?>:</strong> <?php echo esc_html(date_i18n('d/m/Y', strtotime($booking['booking_data']['return_date'])) . ' ' . $booking['booking_data']['return_time']); ?></li>
        <li><strong><?php esc_html_e('Total', 'custom-rental-manager'); ?>:</strong> <?php echo esc_html(crcm_format_price($booking['pricing_breakdown']['final_total'] ?? 0, $currency_symbol)); ?></li>
    </ul>
</div>

<?php get_footer(); ?>

