<?php
/**
 * Booking updated email template for customers (EN).
 *
 * @var array $booking
 * @var array $customer
 * @var array $changes
 */
?>
<p><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($customer['first_name'])); ?></p>
<p><?php _e('The details of your booking have been updated:', 'custom-rental-manager'); ?></p>
<ul>
<?php foreach ($changes as $field => $values) { ?>
    <li><?php echo esc_html(ucwords(str_replace('_', ' ', $field))); ?>: <?php echo esc_html($values['old']); ?> → <?php echo esc_html($values['new']); ?></li>
<?php } ?>
</ul>
<p><strong><?php printf(__('Booking Number: %s', 'custom-rental-manager'), esc_html($booking['booking_number'])); ?></strong></p>
