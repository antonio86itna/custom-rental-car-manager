<?php
/**
 * Booking status change email template for customers (EN).
 *
 * @var array  $booking
 * @var array  $customer
 * @var string $new_status
 */
$status_messages = array(
    'confirmed' => __('Your booking has been confirmed.', 'custom-rental-manager'),
    'active'    => __('Your rental is now active.', 'custom-rental-manager'),
    'completed' => __('Your rental has been completed.', 'custom-rental-manager'),
    'cancelled' => __('Your booking has been cancelled.', 'custom-rental-manager'),
);
$message = $status_messages[$new_status] ?? sprintf(__('Your booking status changed to %s.', 'custom-rental-manager'), $new_status);
?>
<p><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($customer['first_name'])); ?></p>
<p><?php echo esc_html($message); ?></p>
<p><strong><?php printf(__('Booking Number: %s', 'custom-rental-manager'), esc_html($booking['booking_number'])); ?></strong></p>

<?php if (! empty($booking['pricing_breakdown']['line_items'])) { ?>
<table style="width:100%; border-collapse:collapse;">
    <thead>
        <tr>
            <th align="left"><?php _e('Item', 'custom-rental-manager'); ?></th>
            <th align="right"><?php _e('Amount', 'custom-rental-manager'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($booking['pricing_breakdown']['line_items'] as $item) { ?>
        <?php
        $name          = esc_html($item['name'] ?? '');
        $amount        = floatval($item['amount'] ?? 0);
        $free          = ! empty($item['free']);
        $amount_display = $free ? __('Free', 'custom-rental-manager') : '€' . number_format($amount, 2);
        ?>
        <tr>
            <td><?php echo $name; ?><?php if ($free) { ?> (<?php _e('Included', 'custom-rental-manager'); ?>)<?php } ?></td>
            <td align="right"><?php echo esc_html($amount_display); ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>

<?php
$total_amount = $booking['pricing_breakdown']['final_total'] ?? 0;
?>
<p style="font-weight:bold; margin-top:15px;">
    <?php printf(__('Total: %s', 'custom-rental-manager'), '€' . number_format((float) $total_amount, 2)); ?>
</p>

<?php if ('pending' === $booking['status']) { ?>
<p><?php _e('Please complete your booking by clicking the button below.', 'custom-rental-manager'); ?></p>
<?php } ?>
