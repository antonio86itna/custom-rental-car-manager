<?php
/**
 * Booking status change email template for customers (EN)
 *
 * Available variables:
 * - $booking (array)
 * - $new_status (string)
 */
?>
<!DOCTYPE html>
<html>
<body>
    <p><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($booking['customer_data']['first_name'])); ?></p>
    <p><?php printf(__('Your booking status changed to %s.', 'custom-rental-manager'), esc_html($new_status)); ?></p>
</body>
</html>
