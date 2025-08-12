jQuery(document).ready(function($) {
    let currentStep = 1;
    const totalSteps = 4;
    const dailyRate = parseFloat(crcmBookingData.daily_rate);
    const extraDailyRate = parseFloat(crcmBookingData.extra_daily_rate);
    let rentalDays = parseInt(crcmBookingData.rental_days);
    const currencySymbol = crcmBookingData.currency_symbol;
    const daysLabel = crcmBookingData.days_label;
    const freeLabel = crcmBookingData.free_label;
    
    // Initialize
    showStep(1);
    
    // Step navigation
    $('.crcm-next-btn').on('click', function() {
        const nextStep = parseInt($(this).data('next'));
        if (validateStep(currentStep)) {
            showStep(nextStep);
        }
    });
    
    $('.crcm-prev-btn').on('click', function() {
        const prevStep = parseInt($(this).data('prev'));
        showStep(prevStep);
    });

    // Header date/time change handlers
    $('#header_pickup_date, #header_return_date, #header_pickup_time, #header_return_time').on('change', function() {
        const pickupDate = $('#header_pickup_date').val();
        const returnDate = $('#header_return_date').val();
        const pickupTime = $('#header_pickup_time').val();
        const returnTime = $('#header_return_time').val();

        $('input[name="pickup_date"]').val(pickupDate);
        $('input[name="return_date"]').val(returnDate);
        $('input[name="pickup_time"]').val(pickupTime);
        $('input[name="return_time"]').val(returnTime);

        if (pickupDate && returnDate) {
            const pickup = new Date(pickupDate);
            const ret = new Date(returnDate);
            rentalDays = Math.max(1, Math.ceil((ret - pickup) / (1000 * 60 * 60 * 24)));
            $('#duration-summary').text(rentalDays + ' ' + daysLabel);
        }

        $('#pickup-summary').text(formatDate(pickupDate, pickupTime));
        $('#return-summary').text(formatDate(returnDate, returnTime));

        updatePricing();
    });
    
    // Show specific step
    function showStep(step) {
        // Hide all steps
        $('.crcm-form-step').removeClass('active');
        $('.crcm-step').removeClass('active completed');
        
        // Show current step
        $('#step-' + step).addClass('active');
        $('.crcm-step[data-step="' + step + '"]').addClass('active');
        
        // Mark completed steps
        for (let i = 1; i < step; i++) {
            $('.crcm-step[data-step="' + i + '"]').addClass('completed');
        }
        
        currentStep = step;
        
        // Update summary if on step 3 or 4
        if (step >= 3) {
            updateSummary();
        }
    }
    
    // Validate current step
    function validateStep(step) {
        let isValid = true;
        const $currentStep = $('#step-' + step);
        
        // Clear previous errors
        $('.crcm-field-error').remove();
        
        // Check required fields
        $currentStep.find('[required]').each(function() {
            if (!$(this).val().trim()) {
                showFieldError($(this), 'Campo obbligatorio');
                isValid = false;
            }
        });
        
        // Email validation
        if (step === 2) {
            const email = $('#email').val();
            if (email && !isValidEmail(email)) {
                showFieldError($('#email'), 'Email non valida');
                isValid = false;
            }
        }
        
        // Terms acceptance
        if (step === 3) {
            if (!$('#accept_terms').is(':checked')) {
                alert('Devi accettare i termini e condizioni per procedere.');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    // Home delivery toggle
    $('#home_delivery').on('change', function() {
        if ($(this).is(':checked')) {
            $('.crcm-delivery-address').slideDown();
        } else {
            $('.crcm-delivery-address').slideUp();
        }
    });
    
    // Auto-sync return location
    $('#pickup_location').on('change', function() {
        const selectedValue = $(this).val();
        if (selectedValue && !$('#return_location').val()) {
            $('#return_location').val(selectedValue);
        }
    });
    
    // Extras calculation
    $('input[name="extras[]"]').on('change', function() {
        updatePricing();
    });
    
    // Payment type change
    $('input[name="payment_type"]').on('change', function() {
        updateFinalAmount();
    });
    
    // Update pricing
    function updatePricing() {
        let total = (dailyRate + extraDailyRate) * rentalDays;
        let extrasTotal = 0;

        // Clear extras pricing
        $('#extras-pricing').empty();

        // Update base prices
        $('#base-price').text(formatPrice(dailyRate * rentalDays));
        $('#extra-price').text(formatPrice(extraDailyRate * rentalDays));
        
        // Calculate extras
        $('input[name="extras[]"]:checked').each(function() {
            const extraPrice = parseFloat($(this).data('price'));
            const extraName = $(this).closest('.crcm-extra-item').find('.crcm-extra-name').text();
            const extraTotal = extraPrice * rentalDays;

            extrasTotal += extraTotal;
            const priceText = extraTotal > 0 ? formatPrice(extraTotal) : freeLabel;

            // Add to sidebar
            $('#extras-pricing').append(
                '<div class="crcm-price-item">' +
                '<span>' + extraName + ' (' + rentalDays + ' ' + daysLabel + ')</span>' +
                '<span>' + priceText + '</span>' +
                '</div>'
            );
        });
        
        total += extrasTotal;
        
        // Update totals
        $('#sidebar-total').text(formatPrice(total));
        $('#full-amount').text(formatPrice(total));
        $('#deposit-amount').text(formatPrice(total * 0.3));
        
        updateFinalAmount();
    }
    
    // Update final amount based on payment type
    function updateFinalAmount() {
        const total = parseFloat($('#sidebar-total').text().replace(/[^0-9.-]+/g, ''));
        const paymentType = $('input[name="payment_type"]:checked').val();
        
        let finalAmount = total;
        if (paymentType === 'deposit') {
            finalAmount = total * 0.3;
        }
        
        $('#final-total').text(formatPrice(finalAmount));
    }
    
    // Update summary
    function updateSummary() {
        // This would be populated with booking details
        // Implementation depends on specific requirements
    }
    
    // Format price
    function formatPrice(amount) {
        return currencySymbol + amount.toFixed(2);
    }

    function formatDate(dateStr, timeStr) {
        if (!dateStr) {
            return '-';
        }
        const date = new Date(dateStr);
        const formatted = date.toLocaleDateString();
        return formatted + (timeStr ? ' alle ' + timeStr : '');
    }
    
    // Form submission
    $('#crcm-booking-form').on('submit', function(e) {
        e.preventDefault();

        if (!validateStep(currentStep)) {
            return;
        }

        $('#stripe-pay-btn').prop('disabled', true).html('💳 Elaborazione...');

        const formArray = $(this).serializeArray();
        const formData = {};
        formArray.forEach(function(field) {
            if (field.name.endsWith('[]')) {
                const key = field.name.replace('[]', '');
                if (!formData[key]) {
                    formData[key] = [];
                }
                formData[key].push(field.value);
            } else {
                formData[field.name] = field.value;
            }
        });

        const payload = {
            vehicle_id: parseInt(formData.vehicle_id),
            pickup_date: formData.pickup_date,
            return_date: formData.return_date,
            pickup_time: formData.pickup_time,
            return_time: formData.return_time,
            pickup_location: formData.pickup_location,
            return_location: formData.return_location,
            home_delivery: !!formData.home_delivery,
            delivery_address: formData.delivery_address || '',
            extras: formData.extras || [],
            insurance_type: formData.insurance_type || 'basic',
            customer_data: {
                first_name: formData.first_name,
                last_name: formData.last_name,
                email: formData.email,
                phone: formData.phone
            },
            notes: formData.notes || ''
        };

        $.ajax({
            url: window.location.origin + '/wp-json/crcm/v1/bookings',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(res) {
                const bookingId = res.booking_id;
                $.ajax({
                    url: window.location.origin + '/wp-json/crcm/v1/bookings/' + bookingId + '/checkout',
                    method: 'POST',
                    success: function(resp) {
                        window.location.href = resp.checkout_url;
                    },
                    error: function() {
                        alert('Errore creazione sessione di pagamento.');
                        $('#stripe-pay-btn').prop('disabled', false).html('💳 Paga con Stripe');
                    }
                });
            },
            error: function() {
                alert('Errore creazione prenotazione.');
                $('#stripe-pay-btn').prop('disabled', false).html('💳 Paga con Stripe');
            }
        });
    });
    
    // Utility functions
    function showFieldError($field, message) {
        const $error = $('<div class="crcm-field-error" style="color: #e74c3c; font-size: 12px; margin-top: 5px;">' + message + '</div>');
        $field.closest('.crcm-field-group').append($error);
        $field.focus();
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Initialize pricing
    updatePricing();
});
