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
            <th align="right"><?php _e('Importo', 'custom-rental-manager'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($booking['pricing_breakdown']['line_items'] as $item) { ?>
        <?php
        $name          = esc_html($item['name'] ?? '');
        $amount        = floatval($item['amount'] ?? 0);
        $free          = ! empty($item['free']);
        $amount_display = $free ? __('Gratis', 'custom-rental-manager') : '€' . number_format($amount, 2);
        ?>
        <tr>
            <td><?php echo $name; ?><?php if ($free) { ?> (<?php _e('Incluso', 'custom-rental-manager'); ?>)<?php } ?></td>
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
    <?php printf(__('Totale: %s', 'custom-rental-manager'), '€' . number_format((float) $total_amount, 2)); ?>
</p>

<?php if ('pending' === $booking['status']) { ?>
<p><?php _e('Completa la tua prenotazione cliccando il pulsante qui sotto.', 'custom-rental-manager'); ?></p>
<?php } ?>
