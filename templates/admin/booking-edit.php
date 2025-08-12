<?php
/**
 * Booking edit lock notice and field disabling.
 *
 * @package CustomRentalCarManager
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="notice notice-warning">
    <p><?php esc_html_e('This booking is locked. Only pickup time, pickup location, return location, internal notes and customer notes can be edited.', 'custom-rental-manager'); ?></p>
</div>

<div id="crcm-admin-actions" class="crcm-admin-actions">
    <button type="button" class="button button-secondary" id="crcm-cancel-booking"><?php esc_html_e('Cancella prenotazione', 'custom-rental-manager'); ?></button>
    <button type="button" class="button button-secondary" id="crcm-refund-booking"><?php esc_html_e('Cancella e rimborso', 'custom-rental-manager'); ?></button>
</div>

<div id="crcm-refund-modal" style="display:none;">
    <div class="crcm-refund-content">
        <label for="crcm-refund-amount"><?php esc_html_e('Importo rimborso', 'custom-rental-manager'); ?></label>
        <input type="number" step="0.01" id="crcm-refund-amount" />
        <div class="crcm-refund-buttons">
            <button type="button" class="button button-primary" id="crcm-confirm-refund"><?php esc_html_e('Conferma rimborso', 'custom-rental-manager'); ?></button>
            <button type="button" class="button" id="crcm-cancel-refund"><?php esc_html_e('Chiudi', 'custom-rental-manager'); ?></button>
        </div>
    </div>
</div>
