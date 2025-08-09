<?php
/**
 * Pickup reminder email template for customers (EN).
 *
 * @var array $booking
 * @var array $customer
 */
$pickup_date = $booking['booking_data']['pickup_date'] ?? '';
$pickup_time = $booking['booking_data']['pickup_time'] ?? '';
?>
<p><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($customer['first_name'])); ?></p>
<p><?php _e('This is a friendly reminder that your rental pickup is approaching.', 'custom-rental-manager'); ?></p>
<p><strong><?php printf(__('Booking Number: %s', 'custom-rental-manager'), esc_html($booking['booking_number'])); ?></strong></p>
<p><?php printf(__('Pickup: %s at %s', 'custom-rental-manager'), esc_html($pickup_date), esc_html($pickup_time)); ?></p>

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
