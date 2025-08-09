<?php
/**
 * Booking updated email template for admins (EN).
 *
 * @var array $booking
 * @var array $changes
 */
?>
<p><?php _e('A booking has been updated with the following changes:', 'custom-rental-manager'); ?></p>
<p><strong><?php printf(__('Booking Number: %s', 'custom-rental-manager'), esc_html($booking['booking_number'])); ?></strong></p>
<ul>
<?php foreach ($changes as $field => $values) { ?>
    <li><?php echo esc_html(ucwords(str_replace('_', ' ', $field))); ?>: <?php echo esc_html($values['old']); ?> → <?php echo esc_html($values['new']); ?></li>
<?php } ?>
</ul>
<p><?php printf(__('Customer: %s %s', 'custom-rental-manager'), esc_html($booking['customer_data']['first_name']), esc_html($booking['customer_data']['last_name'])); ?></p>
