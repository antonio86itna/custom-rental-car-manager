<?php
/**
 * Template email conferma prenotazione per clienti (IT).
 *
 * @var array $booking
 * @var array $customer
 */
?>
<p><?php printf(__('Caro %s,', 'custom-rental-manager'), esc_html($customer['first_name'])); ?></p>
<p><?php _e('Grazie per la tua prenotazione!', 'custom-rental-manager'); ?></p>
<p><strong><?php printf(__('Numero di prenotazione: %s', 'custom-rental-manager'), esc_html($booking['booking_number'])); ?></strong></p>

<?php if (! empty($booking['pricing_breakdown']['line_items'])) { ?>
<table style="width:100%; border-collapse:collapse;">
    <thead>
        <tr>
            <th align="left"><?php _e('Voce', 'custom-rental-manager'); ?></th>
            <th align="right"><?php _e('Qty', 'custom-rental-manager'); ?></th>
            <th align="right"><?php _e('Importo', 'custom-rental-manager'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php
    $currency_symbol = crcm_get_setting('currency_symbol', '€');
    foreach ($booking['pricing_breakdown']['line_items'] as $item) {
        $amount        = floatval($item['amount'] ?? 0);
        $free          = ! empty($item['free']);
        $label         = crcm_format_line_item_label($item, $currency_symbol);
        $qty           = intval($item['qty'] ?? 0);
        $amount_display = $free ? __('Gratis', 'custom-rental-manager') : crcm_format_price($amount, $currency_symbol);
        ?>
        <tr>
            <td><?php echo $label; ?><?php if ($free) { ?> (<?php _e('Incluso', 'custom-rental-manager'); ?>)<?php } ?></td>
            <td align="right"><?php echo esc_html($qty); ?></td>
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
    <?php printf(__('Totale: %s', 'custom-rental-manager'), crcm_format_price((float) $total_amount, $currency_symbol)); ?>
</p>

<?php if ('pending' === $booking['status']) { ?>
<p><?php _e('Completa la tua prenotazione cliccando il pulsante qui sotto.', 'custom-rental-manager'); ?></p>
<?php } ?>
