<?php
/**
 * Booking confirmation email template for admins (EN)
 *
 * Available variables:
 * - $booking (array)
 */
?>
<!DOCTYPE html>
<html>
<body>
    <p><?php _e('A new booking has been created.', 'custom-rental-manager'); ?></p>
    <p><?php printf(__('Booking Number: %s', 'custom-rental-manager'), esc_html($booking['booking_number'])); ?></p>
    <p><?php printf(__('Customer: %s %s', 'custom-rental-manager'), esc_html($booking['customer_data']['first_name']), esc_html($booking['customer_data']['last_name'])); ?></p>
</body>
</html>
