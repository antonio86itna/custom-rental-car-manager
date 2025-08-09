<?php
/**
 * Template email aggiornamento prenotazione per admin (IT).
 *
 * @var array $booking
 * @var array $changes
 */
?>
<p><?php _e('Una prenotazione è stata aggiornata con le seguenti modifiche:', 'custom-rental-manager'); ?></p>
<p><strong><?php printf(__('Numero di prenotazione: %s', 'custom-rental-manager'), esc_html($booking['booking_number'])); ?></strong></p>
<ul>
<?php foreach ($changes as $field => $values) { ?>
    <li><?php echo esc_html(ucwords(str_replace('_', ' ', $field))); ?>: <?php echo esc_html($values['old']); ?> → <?php echo esc_html($values['new']); ?></li>
<?php } ?>
</ul>
<p><?php printf(__('Cliente: %s %s', 'custom-rental-manager'), esc_html($booking['customer_data']['first_name']), esc_html($booking['customer_data']['last_name'])); ?></p>
