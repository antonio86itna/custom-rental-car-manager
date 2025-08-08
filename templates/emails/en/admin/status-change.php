<?php
/**
 * Booking status change email template for admins (EN)
 *
 * Available variables:
 * - $booking (array)
 * - $new_status (string)
 * - $old_status (string)
 */
?>
<!DOCTYPE html>
<html>
<body>
    <p><?php _e('Booking status updated.', 'custom-rental-manager'); ?></p>
    <p><?php printf(__('Booking Number: %s', 'custom-rental-manager'), esc_html($booking['booking_number'])); ?></p>
    <p><?php printf(__('Status: %s → %s', 'custom-rental-manager'), esc_html($old_status), esc_html($new_status)); ?></p>
</body>
</html>
