/**
 * Booking admin scripts
 */
jQuery(document).ready(function ($) {
    const ajaxUrl = crcm_booking.ajax_url;
    const nonce   = crcm_booking.nonce;
    const i18n    = crcm_booking.i18n;

    // Calculate rental days automatically with optional late return rule
    let lateReturnApplied = false;

    function calculateRentalDays() {
        const pickupDate = $('#pickup_date').val();
        const returnDate = $('#return_date').val();
        const pickupTime = $('#pickup_time').val();
        const returnTime = $('#return_time').val();

        if (pickupDate && returnDate) {
            const start = new Date(pickupDate + 'T' + pickupTime);
            const end   = new Date(returnDate + 'T' + returnTime);

            if (end > start) {
                let daysDiff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 3600 * 24));

                const misc = window.crcmVehicleMisc || {};
                if (misc.late_return_rule && returnTime && misc.late_return_time && returnTime > misc.late_return_time) {
                    daysDiff++;
                    lateReturnApplied = true;
                } else {
                    lateReturnApplied = false;
                }

                $('#rental-days-display').text(i18n.rental_days + ' ' + daysDiff);
                $('input[name="booking_data[rental_days]"]').val(daysDiff);

                $(document).trigger('booking_dates_changed', [daysDiff]);

                return daysDiff;
            }
        }
        return 1;
    }
    window.calculateRentalDays = calculateRentalDays;

    $('#pickup_date').on('change', function () {
        const pickupDate = $(this).val();
        const nextDay = new Date(pickupDate);
        nextDay.setDate(nextDay.getDate() + 1);

        $('#return_date').attr('min', nextDay.toISOString().split('T')[0]);

        if ($('#return_date').val() <= pickupDate) {
            $('#return_date').val(nextDay.toISOString().split('T')[0]);
        }

        calculateRentalDays();
    });

    $('#return_date, #pickup_time, #return_time').on('change', calculateRentalDays);
    calculateRentalDays();

    // Customer search
    let searchTimeout;

    $('#customer_search').on('input', function () {
        const query = $(this).val();
        const $results = $('#customer_search_results');

        clearTimeout(searchTimeout);

        if (query.length < 2) {
            $results.empty().hide();
            return;
        }

        searchTimeout = setTimeout(function () {
            $results.html('<div class="crcm-loading">' + i18n.searching + '</div>').show();

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'crcm_search_customers',
                    query: query,
                    nonce: nonce
                },
                success: function (response) {
                    if (response.success && response.data.length > 0) {
                        let html = '<div class="crcm-customer-results">';
                        response.data.forEach(function (customer) {
                            html += `
                                <div class="customer-result" data-customer-id="${customer.ID}">
                                    <div class="customer-info">
                                        <strong>${customer.display_name}</strong>
                                        <span class="customer-email">${customer.user_email}</span>
                                        ${customer.phone ? `<span class="customer-phone">${customer.phone}</span>` : ''}
                                    </div>
                                    <button type="button" class="button button-small select-customer">${i18n.select}</button>
                                </div>
                            `;
                        });
                        html += '</div>';
                        $results.html(html);
                    } else {
                        $results.html('<div class="no-results">' + i18n.no_results + '</div>');
                    }
                },
                error: function () {
                    $results.html('<div class="error">' + i18n.search_error + '</div>');
                }
            });
        }, 300);
    });

    // Select customer
    $(document).on('click', '.select-customer', function () {
        const $result = $(this).closest('.customer-result');
        const customerId = $result.data('customer-id');
        const customerName = $result.find('strong').text();
        const customerEmail = $result.find('.customer-email').text();
        const customerPhone = $result.find('.customer-phone').text();

        $('#selected_customer_id').val(customerId);
        $('#customer_search').val('');
        $('#customer_search_results').empty().hide();

        const customerCardHtml = `
            <div class="customer-card">
                <h4>${customerName}</h4>
                <p><strong>${i18n.email_label}</strong> ${customerEmail}</p>
                <p><strong>${i18n.role_label}</strong> ${i18n.rental_customer_role}</p>
                ${customerPhone ? `<p><strong>${i18n.phone_label}</strong> ${customerPhone}</p>` : ''}
                <button type="button" class="button button-secondary" id="change_customer">
                    ${i18n.change_customer}
                </button>
            </div>
        `;

        $('#selected_customer_info').html(customerCardHtml).show();
    });

    // Change customer
    $(document).on('click', '#change_customer', function () {
        $('#selected_customer_id').val('');
        $('#selected_customer_info').hide();
        $('#customer_search').focus();
    });

    // Hide results when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.crcm-customer-search-container').length) {
            $('#customer_search_results').hide();
        }
    });

    // Vehicle selection and availability
    $('#vehicle_id').on('change', function () {
        const vehicleId = $(this).val();

        if (!vehicleId) {
            $('#vehicle_details_display, #availability_check').hide();
            return;
        }

        $('#vehicle_details_content').html('<div class="crcm-loading">' + i18n.loading_vehicle + '</div>');
        $('#availability_status').html('<div class="crcm-loading">' + i18n.checking_availability + '</div>');
        $('#vehicle_details_display, #availability_check').show();

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'crcm_get_vehicle_booking_data',
                vehicle_id: vehicleId,
                nonce: nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#vehicle_details_content').html(response.data.details);

                    $(document).trigger('vehicle_selected', [vehicleId, response.data]);

                    checkVehicleAvailability(vehicleId);
                } else {
                    $('#vehicle_details_content').html('<div class="error">' + i18n.error_loading_details + '</div>');
                }
            },
            error: function () {
                $('#vehicle_details_content').html('<div class="error">' + i18n.connection_error + '</div>');
            }
        });
    });

    function checkVehicleAvailability(vehicleId) {
        const pickupDate = $('#pickup_date').val();
        const returnDate = $('#return_date').val();

        if (!pickupDate || !returnDate) {
            $('#availability_status').html('<div class="warning">' + i18n.select_dates + '</div>');
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'crcm_check_vehicle_availability',
                vehicle_id: vehicleId,
                pickup_date: pickupDate,
                return_date: returnDate,
                nonce: nonce
            },
            success: function (response) {
                if (response.success) {
                    const available = response.data.available_quantity;
                    const total = response.data.total_quantity;

                    if (available > 0) {
                        const units = i18n.units_available.replace('%1$s', available).replace('%2$s', total);
                        $('#availability_status').html(`
                            <div class="success">
                                <strong>${i18n.available}</strong><br>
                                ${units}
                            </div>
                        `);
                    } else {
                        $('#availability_status').html(`
                            <div class="error">
                                <strong>${i18n.not_available}</strong><br>
                                ${i18n.no_units_available}
                            </div>
                        `);
                    }
                } else {
                    $('#availability_status').html('<div class="error">' + i18n.availability_error + '</div>');
                }
            }
        });
    }

    $(document).on('booking_dates_changed', function () {
        const vehicleId = $('#vehicle_id').val();
        if (vehicleId) {
            checkVehicleAvailability(vehicleId);
        }
    });

    if ($('#vehicle_id').val()) {
        $('#vehicle_id').trigger('change');
    }

    // Pricing calculations
    let vehicleData = null;
    let rentalDays = 1;
    let baseTotal = 0;

    $(document).on('vehicle_selected', function (e, vehicleId, data) {
        vehicleData = data;
        window.crcmVehicleMisc = data.misc || {};
        loadVehicleExtrasAndInsurance(vehicleId);
        fetchBasePricing();
        if (window.calculateRentalDays) {
            window.calculateRentalDays();
        }
    });

    $(document).on('booking_dates_changed', function (e, days) {
        rentalDays = days;
        fetchBasePricing();
    });

    function loadVehicleExtrasAndInsurance(vehicleId) {
        if (!vehicleData || !vehicleData.extras || !vehicleData.insurance) {
            return;
        }

        if (vehicleData.extras.length > 0) {
            let extrasHtml = '';
            vehicleData.extras.forEach(function (extra, index) {
                extrasHtml += `
                    <label class="extra-option">
                        <input type="checkbox" name="pricing_breakdown[selected_extras][]" value="${index}" data-name="${extra.name}" data-rate="${extra.daily_rate}">
                        <span class="extra-name">${extra.name}</span>
                        <span class="extra-price">+€${parseFloat(extra.daily_rate).toFixed(2)}${i18n.per_day}</span>
                    </label>
                `;
            });
            $('#extras-list').html(extrasHtml);
            $('#extras-selection').show();
        } else {
            $('#extras-selection').hide();
        }

        if (vehicleData.insurance && vehicleData.insurance.premium && vehicleData.insurance.premium.enabled) {
            const insuranceHtml = `
                <label class="insurance-option">
                    <input type="radio" name="pricing_breakdown[selected_insurance]" value="basic" checked>
                    <span class="insurance-name">${i18n.basic_insurance}</span>
                    <span class="insurance-price">${i18n.included}</span>
                </label>
                <label class="insurance-option">
                    <input type="radio" name="pricing_breakdown[selected_insurance]" value="premium" data-rate="${vehicleData.insurance.premium.daily_rate}">
                    <span class="insurance-name">${i18n.premium_insurance}</span>
                    <span class="insurance-price">+€${parseFloat(vehicleData.insurance.premium.daily_rate).toFixed(2)}${i18n.per_day}</span>
                </label>
            `;
            $('#insurance-options').html(insuranceHtml);
            $('#insurance-selection').show();
        } else {
            $('#insurance-selection').hide();
        }
    }

    function fetchBasePricing() {
        if (!vehicleData) {
            return;
        }

        const pickupDate = $('#pickup_date').val();
        const returnDate = $('#return_date').val();
        const pickupTime = $('#pickup_time').val();
        const returnTime = $('#return_time').val();
        const vehicleId = $('#vehicle_id').val();

        if (!pickupDate || !returnDate || !vehicleId) {
            return;
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'crcm_calculate_booking_total',
                vehicle_id: vehicleId,
                pickup_date: pickupDate,
                return_date: returnDate,
                pickup_time: pickupTime,
                return_time: returnTime,
                nonce: nonce
            },
            success: function (response) {
                if (response.success) {
                    baseTotal = parseFloat(response.data.base_total) || 0;
                    rentalDays = parseInt(response.data.rental_days) || rentalDays;
                    $('#rental-days-display').text(i18n.rental_days + ' ' + rentalDays);
                    $('input[name="booking_data[rental_days]"]').val(rentalDays);
                    calculatePricing();
                }
            }
        });
    }

    function calculatePricing() {
        if (!vehicleData) {
            return;
        }

        let extrasTotal = 0;
        let lineItems = [];

        const baseDailyRate = rentalDays ? baseTotal / rentalDays : 0;
        const baseRate = vehicleData && vehicleData.pricing ? parseFloat(vehicleData.pricing.daily_rate) || 0 : 0;
        const extraDaily = baseDailyRate > baseRate ? baseDailyRate - baseRate : 0;
        if (lateReturnApplied && rentalDays > 1) {
            const baseDays = rentalDays - 1;
            if (baseDays > 0) {
                lineItems.push({
                    name: i18n.base_rate,
                    qty: baseDays,
                    amount: baseDailyRate * baseDays,
                    free: false,
                    type: 'daily',
                    base_rate: baseRate,
                    extra_rate: extraDaily
                });
            }
            lineItems.push({
                name: i18n.late_return_fee,
                qty: 1,
                amount: baseDailyRate,
                free: false,
                type: 'flat',
                base_rate: baseDailyRate,
                extra_rate: 0
            });
        } else {
            lineItems.push({
                name: i18n.base_rate,
                qty: rentalDays,
                amount: baseTotal,
                free: false,
                type: 'daily',
                base_rate: baseRate,
                extra_rate: extraDaily
            });
        }

        $('input[name="pricing_breakdown[selected_extras][]"]:checked').each(function () {
            const rate = parseFloat($(this).data('rate')) || 0;
            const name = $(this).data('name');
            const amount = rate * rentalDays;
            extrasTotal += amount;
            lineItems.push({
                name: name,
                qty: rentalDays,
                amount: amount,
                free: rate === 0,
                type: 'daily',
                base_rate: rate,
                extra_rate: 0
            });
        });

        let insuranceTotal = 0;
        const selectedInsurance = $('input[name="pricing_breakdown[selected_insurance]"]:checked');
        if (selectedInsurance.val() === 'premium') {
            const rate = parseFloat(selectedInsurance.data('rate')) || 0;
            insuranceTotal = rate * rentalDays;
            lineItems.push({
                name: i18n.premium_insurance,
                qty: rentalDays,
                amount: insuranceTotal,
                free: false,
                type: 'daily',
                base_rate: rate,
                extra_rate: 0
            });
        } else {
            lineItems.push({
                name: i18n.basic_insurance,
                qty: rentalDays,
                amount: 0,
                free: true,
                type: 'daily',
                base_rate: 0,
                extra_rate: 0
            });
        }

        const discount = parseFloat($('#manual_discount').val()) || 0;
        const finalTotal = Math.max(0, baseTotal + extrasTotal + insuranceTotal - discount);

        const $tbody = $('#pricing-breakdown-content');
        $tbody.find('tr.line-item').remove();
        lineItems.forEach(function (item) {
            const freeLabel = item.free ? ' (' + i18n.included + ')' : '';
            const amountDisplay = item.free ? '0.00' : item.amount.toFixed(2);
            const row = `<tr class="pricing-row line-item"><td>${item.name}${freeLabel}</td><td class="price-cell">€<span>${amountDisplay}</span></td></tr>`;
            $(row).insertBefore($tbody.find('tr.discount-row'));
        });

        $('#discount-total').text(discount.toFixed(2));
        $('#final-total').text(finalTotal.toFixed(2));

        $('#base_total_input').val(baseTotal);
        $('#extras_total_input').val(extrasTotal);
        $('#insurance_total_input').val(insuranceTotal);
        $('#final_total_input').val(finalTotal);
        $('#line_items_input').val(JSON.stringify(lineItems));

        $('.discount-row').toggle(discount > 0);
    }

    $(document).on('change', 'input[name="pricing_breakdown[selected_extras][]"]', calculatePricing);
    $(document).on('change', 'input[name="pricing_breakdown[selected_insurance]"]', calculatePricing);
    $('#manual_discount').on('input', calculatePricing);
});

