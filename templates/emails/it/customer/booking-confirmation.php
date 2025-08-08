<?php
/**
 * Template email conferma prenotazione per clienti (IT)
 *
 * Variabili disponibili:
 * - $booking (array)
 */
?>
<!DOCTYPE html>
<html>
<body>
    <p><?php echo 'Gentile ' . esc_html($booking['customer_data']['first_name']) . ','; ?></p>
    <p>Grazie per la tua prenotazione!</p>
    <p><?php echo 'Numero prenotazione: ' . esc_html($booking['booking_number']); ?></p>
</body>
</html>
