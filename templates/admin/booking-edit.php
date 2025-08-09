<?php
/**
 * Booking edit lock notice and field disabling.
 *
 * @package CustomRentalCarManager
 */

if (!defined('ABSPATH')) {
    exit;
}
global $post;
$booking_id = $post->ID;
$nonce      = wp_create_nonce('crcm_admin_nonce');
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

<script>
    jQuery(function($){
        const allowed = ['#pickup_time', '#pickup_location', '#return_location', '#home_delivery', '#delivery_address', '#internal_notes', '#booking_notes'];
        $('#crcm_booking_details :input, #crcm_booking_customer :input, #crcm_booking_vehicle :input, #crcm_booking_pricing :input, #crcm_booking_status :input, #crcm_booking_notes :input').each(function(){
            const id = '#' + $(this).attr('id');
            if (allowed.indexOf(id) === -1) {
                $(this).prop('disabled', true);
            }
        });

        const bookingData = {
            ajax_url: ajaxurl,
            booking_id: <?php echo (int) $booking_id; ?>,
            nonce: '<?php echo esc_js($nonce); ?>'
        };

        $('#crcm-cancel-booking').on('click', function(){
            if(!confirm('<?php echo esc_js(__('Sei sicuro di voler cancellare questa prenotazione?', 'custom-rental-manager')); ?>')) {
                return;
            }
            $.post(bookingData.ajax_url, {
                action: 'crcm_admin_cancel_booking',
                booking_id: bookingData.booking_id,
                nonce: bookingData.nonce
            }, function(response){
                if(response.success){
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data);
                }
            });
        });

        $('#crcm-refund-booking').on('click', function(){
            const total = $('#final_total_input').val() || 0;
            $('#crcm-refund-amount').val(total);
            $('#crcm-refund-modal').show();
        });

        $('#crcm-cancel-refund').on('click', function(){
            $('#crcm-refund-modal').hide();
        });

        $('#crcm-confirm-refund').on('click', function(){
            const amount = parseFloat($('#crcm-refund-amount').val()) || 0;
            $.post(bookingData.ajax_url, {
                action: 'crcm_process_refund',
                booking_id: bookingData.booking_id,
                refund_amount: amount,
                nonce: bookingData.nonce
            }, function(response){
                if(response.success){
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data);
                }
            });
        });
    });
</script>
