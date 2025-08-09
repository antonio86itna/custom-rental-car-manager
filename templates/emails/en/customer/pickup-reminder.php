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
    <?php
    $currency_symbol = crcm_get_setting('currency_symbol', '€');
    foreach ($booking['pricing_breakdown']['line_items'] as $item) {
        $amount        = floatval($item['amount'] ?? 0);
        $free          = ! empty($item['free']);
        $label         = crcm_format_line_item_label($item, $currency_symbol);
        $amount_display = $free ? __('Free', 'custom-rental-manager') : crcm_format_price($amount, $currency_symbol);
        ?>
        <tr>
            <td><?php echo $label; ?><?php if ($free) { ?> (<?php _e('Included', 'custom-rental-manager'); ?>)<?php } ?></td>
            <td align="right"><?php echo esc_html($amount_display); ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php } ?>

<?php
$total_amount    = $booking['pricing_breakdown']['final_total'] ?? 0;
$currency_symbol = crcm_get_setting('currency_symbol', '€');
?>
<p style="font-weight:bold; margin-top:15px;">
    <?php printf(__('Total: %s', 'custom-rental-manager'), crcm_format_price((float) $total_amount, $currency_symbol)); ?>
</p>

<?php if ('pending' === $booking['status']) { ?>
<p><?php _e('Please complete your booking by clicking the button below.', 'custom-rental-manager'); ?></p>
<?php } ?>
