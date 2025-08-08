<?php
/**
 * Booking confirmation email template for customers (EN)
 *
 * Available variables:
 * - $booking (array)
 */
?>
<!DOCTYPE html>
<html>
<body>
    <p><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($booking['customer_data']['first_name'])); ?></p>
    <p><?php _e('Thank you for your booking!', 'custom-rental-manager'); ?></p>
    <p><?php printf(__('Booking Number: %s', 'custom-rental-manager'), esc_html($booking['booking_number'])); ?></p>
</body>
</html>
