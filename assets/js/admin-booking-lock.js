/**
 * Booking edit lock interactions
 */
(function($){
    $(function(){
        var allowed = ['#pickup_time', '#pickup_location', '#return_location', '#home_delivery', '#delivery_address', '#internal_notes', '#booking_notes'];
        $('#crcm_booking_details :input, #crcm_booking_customer :input, #crcm_booking_vehicle :input, #crcm_booking_pricing :input, #crcm_booking_status :input, #crcm_booking_notes :input').each(function(){
            var id = '#' + $(this).attr('id');
            if (allowed.indexOf(id) === -1) {
                $(this).prop('disabled', true);
            }
        });

        var data = window.crcm_booking_lock || {};

        $('#crcm-cancel-booking').on('click', function(){
            if(!confirm(data.confirm_cancel)) {
                return;
            }
            $.post(data.ajax_url, {
                action: 'crcm_admin_cancel_booking',
                booking_id: data.booking_id,
                nonce: data.nonce
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
            var total = $('#final_total_input').val() || 0;
            $('#crcm-refund-amount').val(total);
            $('#crcm-refund-modal').show();
        });

        $('#crcm-cancel-refund').on('click', function(){
            $('#crcm-refund-modal').hide();
        });

        $('#crcm-confirm-refund').on('click', function(){
            var amount = parseFloat($('#crcm-refund-amount').val()) || 0;
            $.post(data.ajax_url, {
                action: 'crcm_process_refund',
                booking_id: data.booking_id,
                refund_amount: amount,
                nonce: data.nonce
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
})(jQuery);
