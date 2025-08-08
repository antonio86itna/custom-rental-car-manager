(function($){
    'use strict';
    $(function(){
        // Dynamic fields based on vehicle type
        $('#vehicle_type').on('change', function(){
            const vehicleType = $(this).val();
            $.post(crcm_admin.ajax_url, {
                action: 'crcm_get_vehicle_fields',
                vehicle_type: vehicleType,
                nonce: crcm_admin.nonce
            }, function(response){
                if(response.success){
                    $('#crcm-dynamic-fields').html(response.data);
                    $('#crcm-dynamic-fields').trigger('vehicle_type_changed', [vehicleType]);
                }
            });
        });

        // Custom rates
        const ratesContainer = $('#custom-rates-container');
        let rateIndex = parseInt(ratesContainer.data('rate-index'), 10) || 0;
        $('#add-custom-rate').on('click', function(){
            const template = $('#crcm-custom-rate-template').html().replace(/__INDEX__/g, rateIndex);
            ratesContainer.append(template);
            rateIndex++;
        });
        $(document).on('click', '.remove-rate', function(){
            $(this).closest('.crcm-custom-rate-row').remove();
        });
        $(document).on('change', '.rate-type-selector', function(){
            const $row = $(this).closest('.crcm-custom-rate-row');
            const type = $(this).val();
            if(type === 'weekends'){
                $row.find('.date-fields').hide();
            } else {
                $row.find('.date-fields').show();
            }
        });

        // Features update
        $(document).on('vehicle_type_changed', '#crcm-dynamic-fields', function(e, vehicleType){
            updateFeatures(vehicleType);
        });

        function updateFeatures(vehicleType){
            $.post(crcm_admin.ajax_url, {
                action: 'crcm_get_vehicle_features',
                vehicle_type: vehicleType,
                nonce: crcm_admin.nonce
            }, function(response){
                if(response.success){
                    $('#crcm-features-container .crcm-features-grid').html(response.data);
                    $('#crcm-features-container').attr('data-vehicle-type', vehicleType);
                }
            });
        }

        // Availability rules
        const availabilityContainer = $('#availability-rules-container');
        let ruleIndex = parseInt(availabilityContainer.data('rule-index'), 10) || 0;
        const maxQuantity = parseInt(availabilityContainer.data('max-quantity'), 10) || 1;
        const allLabel = availabilityContainer.data('all-label');

        $('#add-availability-rule').on('click', function(e){
            e.preventDefault();
            let quantityOptions = '';
            for(let i = 1; i <= maxQuantity; i++){
                quantityOptions += `<option value="${i}">${i}</option>`;
            }
            quantityOptions += `<option value="all">${allLabel}</option>`;
            const template = $('#crcm-availability-rule-template').html()
                .replace(/__INDEX__/g, ruleIndex)
                .replace('__QUANTITY_OPTIONS__', quantityOptions);
            availabilityContainer.append(template);
            ruleIndex++;
        });

        $(document).on('click', '.remove-rule', function(e){
            e.preventDefault();
            $(this).closest('.crcm-availability-rule').remove();
        });

        // Extra services
        const extrasContainer = $('#extras-services-container');
        let serviceIndex = parseInt(extrasContainer.data('service-index'), 10) || 0;

        $('#add-extra-service').on('click', function(e){
            e.preventDefault();
            const template = $('#crcm-extra-service-template').html().replace(/__INDEX__/g, serviceIndex);
            extrasContainer.append(template);
            serviceIndex++;
        });

        $(document).on('click', '.remove-service', function(e){
            e.preventDefault();
            $(this).closest('.crcm-extra-service-row').remove();
        });

        // Misc toggles
        $('#cancellation_enabled').on('change', function(){
            if($(this).is(':checked')){
                $('.cancellation-days-row').show();
            } else {
                $('.cancellation-days-row').hide();
            }
        });

        $('#late_return_rule').on('change', function(){
            if($(this).is(':checked')){
                $('.late-return-time-row').show();
            } else {
                $('.late-return-time-row').hide();
            }
        });

        $('#featured_vehicle').on('change', function(){
            if($(this).is(':checked')){
                $('.featured-priority-row').show();
            } else {
                $('.featured-priority-row').hide();
            }
        });

        $('#min_rental_days, #max_rental_days').on('change', function(){
            const minDays = parseInt($('#min_rental_days').val(), 10) || 1;
            const maxDays = parseInt($('#max_rental_days').val(), 10) || 30;
            if(minDays > maxDays){
                alert(crcm_vehicle_meta.min_greater_max);
                $('#max_rental_days').val(minDays);
            }
        });
    });
})(jQuery);
