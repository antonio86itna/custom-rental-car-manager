<?php
/**
 * Template email conferma prenotazione per admin (IT)
 *
 * Variabili disponibili:
 * - $booking (array)
 */
?>
<!DOCTYPE html>
<html>
<body>
    <p>È stata creata una nuova prenotazione.</p>
    <p><?php echo 'Numero prenotazione: ' . esc_html($booking['booking_number']); ?></p>
    <p><?php echo 'Cliente: ' . esc_html($booking['customer_data']['first_name']) . ' ' . esc_html($booking['customer_data']['last_name']); ?></p>
</body>
</html>
