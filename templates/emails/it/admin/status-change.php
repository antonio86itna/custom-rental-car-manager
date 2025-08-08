<?php
/**
 * Template email cambio stato prenotazione per admin (IT)
 *
 * Variabili disponibili:
 * - $booking (array)
 * - $new_status (string)
 * - $old_status (string)
 */
?>
<!DOCTYPE html>
<html>
<body>
    <p>Aggiornamento stato prenotazione.</p>
    <p><?php echo 'Numero prenotazione: ' . esc_html($booking['booking_number']); ?></p>
    <p><?php echo 'Stato: ' . esc_html($old_status) . ' → ' . esc_html($new_status); ?></p>
</body>
</html>
