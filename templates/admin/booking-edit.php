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
<script>
    jQuery(function($){
        const allowed = ['#pickup_time', '#pickup_location', '#return_location', '#home_delivery', '#delivery_address', '#internal_notes', '#booking_notes'];
        $('#crcm_booking_details :input, #crcm_booking_customer :input, #crcm_booking_vehicle :input, #crcm_booking_pricing :input, #crcm_booking_status :input, #crcm_booking_notes :input').each(function(){
            const id = '#' + $(this).attr('id');
            if (allowed.indexOf(id) === -1) {
                $(this).prop('disabled', true);
            }
        });
    });
</script>
