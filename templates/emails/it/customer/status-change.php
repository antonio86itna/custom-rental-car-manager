<?php
/**
 * Template email cambio stato prenotazione per clienti (IT)
 *
 * Variabili disponibili:
 * - $booking (array)
 * - $new_status (string)
 */
?>
<!DOCTYPE html>
<html>
<body>
    <p><?php echo 'Gentile ' . esc_html($booking['customer_data']['first_name']) . ','; ?></p>
    <p><?php echo 'Lo stato della tua prenotazione è cambiato in ' . esc_html($new_status) . '.'; ?></p>
</body>
</html>
