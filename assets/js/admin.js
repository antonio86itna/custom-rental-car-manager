/**
 * Enhanced Admin JavaScript for Custom Rental Car Manager
 * 
 * UPDATED with advanced pricing calculations, insurance integration,
 * custom rates handling, and late return penalty calculations.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    let selectedVehicleData = null;
    let currentBookingDays = 1;
    let currentPricing = {};
    
    // ===============================================
    // ENHANCED BOOKING MANAGER FUNCTIONALITY
    // ===============================================
    
    /**
     * Initialize enhanced booking manager
     */
    function initBookingManager() {
        // Date picker initialization
        $('.crcm-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            changeMonth: true,
            changeYear: true,
            onSelect: function() {
                calculateRentalDays();
                checkVehicleAvailability();
                if (selectedVehicleData) {
                    calculateAdvancedPricing();
                }
            }
        });
        
        // Vehicle selection change
        $('#vehicle_id').on('change', function() {
            const vehicleId = $(this).val();
            if (vehicleId) {
                loadVehicleData(vehicleId);
            } else {
                clearVehicleData();
            }
        });
        
        // Date and time changes
        $('#pickup_date, #return_date, #pickup_time, #return_time').on('change', function() {
            calculateRentalDays();
            checkVehicleAvailability();
            if (selectedVehicleData) {
                calculateAdvancedPricing();
            }
        });
        
        // Service selections changes
        $(document).on('change', 'input[name="selected_extras[]"], input[name="selected_insurance"], #manual_discount', function() {
            if (selectedVehicleData) {
                calculateAdvancedPricing();
            }
        });
        
        // Customer search
        initCustomerSearch();
        
        // Form validation
        initFormValidation();
        
        // Initialize existing data if editing
        initializeExistingBooking();
    }
    
    /**
     * Initialize existing booking data for editing
     */
    function initializeExistingBooking() {
        const vehicleId = $('#vehicle_id').val();
        if (vehicleId) {
            loadVehicleData(vehicleId);
        }
    }
    
    /**
     * Calculate rental days between pickup and return
     */
    function calculateRentalDays() {
        const pickupDate = $('#pickup_date').val();
        const returnDate = $('#return_date').val();
        
        if (pickupDate && returnDate) {
            const pickup = new Date(pickupDate);
            const returnD = new Date(returnDate);
            const timeDiff = returnD.getTime() - pickup.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            currentBookingDays = Math.max(1, daysDiff);
            
            // Update display
            $('.rental-days-display').text(currentBookingDays + ' giorni di noleggio');
            $('#rental_days').val(currentBookingDays);
            
            console.log('Calculated rental days:', currentBookingDays);
        }
    }
    
    /**
     * Load vehicle data via AJAX with enhanced data
     */
    function loadVehicleData(vehicleId) {
        console.log('Loading vehicle data for ID:', vehicleId);
        
        // Show loading state
        $('.vehicle-details-container').html('<div class="crcm-loading">🔄 Caricamento dati veicolo...</div>');
        $('.availability-status').html('<div class="crcm-loading">🔄 Controllo disponibilità...</div>');
        $('.extras-container, .insurance-container').html('<div class="crcm-loading">🔄 Caricamento servizi...</div>');
        
        $.ajax({
            url: crcm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'crcm_get_vehicle_booking_data',
                vehicle_id: vehicleId,
                nonce: crcm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    selectedVehicleData = response.data;
                    console.log('Vehicle data loaded:', selectedVehicleData);
                    
                    // Update vehicle details display
                    $('.vehicle-details-container').html(response.data.details);
                    
                    // Populate extras with enhanced display
                    populateExtrasSection(response.data.extras);
                    
                    // Populate insurance with premium options
                    populateInsuranceSection(response.data.insurance);
                    
                    // Check availability
                    checkVehicleAvailability();
                    
                    // Calculate advanced pricing
                    calculateAdvancedPricing();
                    
                } else {
                    showError('Errore nel caricamento dati veicolo: ' + response.data);
                    clearVehicleData();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showError('Errore di connessione durante il caricamento dei dati veicolo');
                clearVehicleData();
            }
        });
    }
    
    /**
     * Clear vehicle data
     */
    function clearVehicleData() {
        selectedVehicleData = null;
        currentPricing = {};
        $('.vehicle-details-container').html('<p class="description">Seleziona un veicolo per visualizzare i dettagli</p>');
        $('.extras-container').html('<p class="description">Seleziona un veicolo per visualizzare i servizi extra</p>');
        $('.insurance-container').html('<p class="description">Seleziona un veicolo per visualizzare le opzioni assicurative</p>');
        $('.availability-status').html('<p class="description">Seleziona un veicolo per controllare la disponibilità</p>');
        resetPricingDisplay();
        clearCalculationLog();
    }
    
    /**
     * Populate extras section with checkboxes
     */
    function populateExtrasSection(extras) {
        const container = $('.extras-container');
        container.empty();
        
        if (!extras || extras.length === 0) {
            container.html('<p class="description">Nessun servizio extra disponibile per questo veicolo</p>');
            return;
        }
        
        let html = '<div class="extras-list">';
        
        extras.forEach(function(extra, index) {
            html += `
                <div class="extra-item">
                    <label>
                        <input type="checkbox" name="selected_extras[]" value="${index}" data-rate="${extra.daily_rate}" data-name="${extra.name}">
                        <strong>${extra.name}</strong>
                        <span class="extra-price">+€${parseFloat(extra.daily_rate).toFixed(2)}/giorno</span>
                    </label>
                </div>
            `;
        });
        
        html += '</div>';
        container.html(html);
        
        console.log('Populated extras:', extras.length, 'items');
    }
    
    /**
     * Populate insurance section with radio buttons
     */
    function populateInsuranceSection(insurance) {
        const container = $('.insurance-container');
        container.empty();
        
        let html = '<div class="insurance-options">';
        
        // Basic insurance (always included)
        html += `
            <div class="insurance-option basic">
                <label>
                    <input type="radio" name="selected_insurance" value="basic" checked data-rate="0">
                    <strong>🛡️ Assicurazione Base (Inclusa)</strong>
                    <div class="insurance-details">
                        <small>✅ RCA - Responsabilità Civile Auto</small>
                    </div>
                </label>
            </div>
        `;
        
        // Premium insurance (if available)
        if (insurance && insurance.premium && insurance.premium.enabled) {
            const deductible = insurance.premium.deductible || 500;
            const dailyRate = parseFloat(insurance.premium.daily_rate || 0);
            
            html += `
                <div class="insurance-option premium">
                    <label>
                        <input type="radio" name="selected_insurance" value="premium" data-rate="${dailyRate}">
                        <strong>🏆 Assicurazione Premium</strong>
                        <span class="insurance-price">+€${dailyRate.toFixed(2)}/giorno</span>
                        <div class="insurance-details">
                            <small>✅ RCA + Franchigia €${deductible} + Furto e Incendio + Danni Accidentali</small>
                        </div>
                    </label>
                </div>
            `;
        }
        
        html += '</div>';
        container.html(html);
        
        console.log('Populated insurance options, premium available:', !!(insurance && insurance.premium && insurance.premium.enabled));
    }
    
    /**
     * Check vehicle availability
     */
    function checkVehicleAvailability() {
        const vehicleId = $('#vehicle_id').val();
        const pickupDate = $('#pickup_date').val();
        const returnDate = $('#return_date').val();
        
        if (!vehicleId || !pickupDate || !returnDate) {
            return;
        }
        
        $('.availability-status').html('<div class="crcm-loading">🔄 Controllo disponibilità...</div>');
        
        $.ajax({
            url: crcm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'crcm_check_vehicle_availability',
                vehicle_id: vehicleId,
                pickup_date: pickupDate,
                return_date: returnDate,
                nonce: crcm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = '';
                    
                    if (data.is_available) {
                        html = `
                            <div class="availability-success">
                                ✅ <strong>Disponibile</strong><br>
                                ${data.available_quantity} di ${data.total_quantity} unità disponibili
                            </div>
                        `;
                    } else {
                        html = `
                            <div class="availability-error">
                                ❌ <strong>Non Disponibile</strong><br>
                                Tutte le unità sono già prenotate per questo periodo
                            </div>
                        `;
                    }
                    
                    $('.availability-status').html(html);
                } else {
                    $('.availability-status').html('<div class="availability-error">❌ Errore nel controllo disponibilità</div>');
                }
            },
            error: function() {
                $('.availability-status').html('<div class="availability-error">❌ Errore di connessione</div>');
            }
        });
    }
    
    /**
     * ENHANCED: Calculate advanced pricing with all components
     */
    function calculateAdvancedPricing() {
        const vehicleId = $('#vehicle_id').val();
        const pickupDate = $('#pickup_date').val();
        const returnDate = $('#return_date').val();
        const pickupTime = $('#pickup_time').val();
        const returnTime = $('#return_time').val();
        
        if (!vehicleId || !pickupDate || !returnDate || !selectedVehicleData) {
            console.log('Missing data for pricing calculation');
            return;
        }
        
        // Collect selected extras
        const selectedExtras = [];
        $('input[name="selected_extras[]"]:checked').each(function() {
            selectedExtras.push(parseInt($(this).val()));
        });
        
        // Get selected insurance
        const selectedInsurance = $('input[name="selected_insurance"]:checked').val() || 'basic';
        
        // Get manual discount
        const manualDiscount = parseFloat($('#manual_discount').val()) || 0;
        
        console.log('Calculating pricing with:', {
            vehicleId,
            pickupDate,
            returnDate,
            pickupTime,
            returnTime,
            selectedExtras,
            selectedInsurance,
            manualDiscount
        });
        
        // Show loading
        updatePricingDisplay(0, 0, 0, 0, 0, 0, 0, true);
        
        $.ajax({
            url: crcm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'crcm_calculate_advanced_pricing',
                vehicle_id: vehicleId,
                pickup_date: pickupDate,
                return_date: returnDate,
                pickup_time: pickupTime,
                return_time: returnTime,
                selected_extras: selectedExtras,
                selected_insurance: selectedInsurance,
                manual_discount: manualDiscount,
                nonce: crcm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    currentPricing = response.data;
                    console.log('Pricing calculated:', currentPricing);
                    
                    // Update pricing display
                    updatePricingDisplay(
                        currentPricing.base_total,
                        currentPricing.custom_rates_total,
                        currentPricing.extras_total,
                        currentPricing.insurance_total,
                        currentPricing.late_return_penalty,
                        currentPricing.discount_total,
                        currentPricing.final_total
                    );
                    
                    // Update calculation log
                    updateCalculationLog(currentPricing.calculation_log);
                    
                    // Update hidden fields
                    updatePricingFields(currentPricing);
                    
                } else {
                    showError('Errore nel calcolo prezzi: ' + response.data);
                    resetPricingDisplay();
                }
            },
            error: function(xhr, status, error) {
                console.error('Pricing calculation error:', error);
                showError('Errore di connessione durante il calcolo prezzi');
                resetPricingDisplay();
            }
        });
    }
    
    /**
     * Update pricing display with all components
     */
    function updatePricingDisplay(baseTotal, customRatesTotal, extrasTotal, insuranceTotal, lateReturnPenalty, discountTotal, finalTotal, loading = false) {
        if (loading) {
            $('.base-total, .custom-rates-total, .extras-total, .insurance-total, .late-return-penalty, .discount-total, .final-total').text('🔄');
            return;
        }
        
        $('.base-total').text('€' + baseTotal.toFixed(2));
        $('.custom-rates-total').text('€' + customRatesTotal.toFixed(2));
        $('.extras-total').text('€' + extrasTotal.toFixed(2));
        $('.insurance-total').text('€' + insuranceTotal.toFixed(2));
        $('.late-return-penalty').text('€' + lateReturnPenalty.toFixed(2));
        $('.discount-total').text('-€' + discountTotal.toFixed(2));
        $('.final-total').text('€' + finalTotal.toFixed(2));
        
        // Show/hide rows based on values
        $('.custom-rates-row').toggle(customRatesTotal > 0);
        $('.late-return-row').toggle(lateReturnPenalty > 0);
        $('.discount-row').toggle(discountTotal > 0);
        
        console.log('Updated pricing display:', {
            baseTotal,
            customRatesTotal,
            extrasTotal,
            insuranceTotal,
            lateReturnPenalty,
            discountTotal,
            finalTotal
        });
    }
    
    /**
     * Reset pricing display
     */
    function resetPricingDisplay() {
        $('.base-total, .custom-rates-total, .extras-total, .insurance-total, .late-return-penalty, .discount-total, .final-total').text('€0.00');
        $('.custom-rates-row, .late-return-row, .discount-row').hide();
        clearCalculationLog();
    }
    
    /**
     * Update calculation log
     */
    function updateCalculationLog(calculationLog) {
        if (!calculationLog || !calculationLog.length) {
            clearCalculationLog();
            return;
        }
        
        const logContent = $('.log-content');
        const logText = calculationLog.join('\n');
        logContent.html('<pre>' + logText + '</pre>');
        
        console.log('Updated calculation log with', calculationLog.length, 'lines');
    }
    
    /**
     * Clear calculation log
     */
    function clearCalculationLog() {
        $('.log-content').html('<p class="description">I calcoli dettagliati appariranno qui quando selezioni un veicolo e imposti le date</p>');
    }
    
    /**
     * Update pricing hidden fields
     */
    function updatePricingFields(pricing) {
        $('#base_total').val(pricing.base_total.toFixed(2));
        $('#custom_rates_total').val(pricing.custom_rates_total.toFixed(2));
        $('#extras_total').val(pricing.extras_total.toFixed(2));
        $('#insurance_total').val(pricing.insurance_total.toFixed(2));
        $('#late_return_penalty').val(pricing.late_return_penalty.toFixed(2));
        $('#final_total').val(pricing.final_total.toFixed(2));
        $('#rental_days').val(pricing.rental_days);
        
        // Update selected services for saving
        const selectedExtras = [];
        $('input[name="selected_extras[]"]:checked').each(function() {
            selectedExtras.push({
                index: $(this).val(),
                name: $(this).data('name'),
                rate: $(this).data('rate')
            });
        });
        
        // Store selected services data
        $('<input>').attr({
            type: 'hidden',
            name: 'pricing_breakdown[selected_extras]',
            value: JSON.stringify(selectedExtras)
        }).appendTo('#post');
        
        $('<input>').attr({
            type: 'hidden',
            name: 'pricing_breakdown[selected_insurance]',
            value: $('input[name="selected_insurance"]:checked').val()
        }).appendTo('#post');
        
        console.log('Updated pricing fields with final total:', pricing.final_total);
    }
    
    // ===============================================
    // CUSTOMER SEARCH FUNCTIONALITY (UNCHANGED)
    // ===============================================
    
    /**
     * Initialize customer search
     */
    function initCustomerSearch() {
        const searchInput = $('#customer_search');
        const resultsContainer = $('.customer-search-results');
        const selectedCustomer = $('.selected-customer-info');
        let searchTimeout;
        
        // Search input
        searchInput.on('input', function() {
            const query = $(this).val().trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                resultsContainer.hide().empty();
                return;
            }
            
            searchTimeout = setTimeout(function() {
                searchCustomers(query);
            }, 300);
        });
        
        // Hide results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.customer-search-container').length) {
                resultsContainer.hide();
            }
        });
        
        // Create new customer button
        $('.create-customer-btn').on('click', function(e) {
            e.preventDefault();
            showCreateCustomerModal();
        });
    }
    
    /**
     * Search customers via AJAX
     */
    function searchCustomers(query) {
        const resultsContainer = $('.customer-search-results');
        resultsContainer.html('<div class="crcm-loading">🔍 Ricerca clienti...</div>').show();
        
        $.ajax({
            url: crcm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'crcm_search_customers',
                query: query,
                nonce: crcm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayCustomerResults(response.data);
                } else {
                    resultsContainer.html('<div class="no-results">❌ ' + response.data + '</div>');
                }
            },
            error: function() {
                resultsContainer.html('<div class="no-results">❌ Errore nella ricerca clienti</div>');
            }
        });
    }
    
    /**
     * Display customer search results
     */
    function displayCustomerResults(customers) {
        const resultsContainer = $('.customer-search-results');
        
        if (!customers.length) {
            resultsContainer.html('<div class="no-results">👤 Nessun cliente trovato</div>');
            return;
        }
        
        let html = '<div class="customer-results-list">';
        
        customers.forEach(function(customer) {
            html += `
                <div class="customer-result-item" data-customer-id="${customer.ID}">
                    <div class="customer-name">${customer.display_name}</div>
                    <div class="customer-email">${customer.user_email}</div>
                    ${customer.phone ? `<div class="customer-phone">📞 ${customer.phone}</div>` : ''}
                </div>
            `;
        });
        
        html += '</div>';
        resultsContainer.html(html);
        
        // Handle customer selection
        $('.customer-result-item').on('click', function() {
            const customerId = $(this).data('customer-id');
            const customerName = $(this).find('.customer-name').text();
            const customerEmail = $(this).find('.customer-email').text();
            const customerPhone = $(this).find('.customer-phone').text().replace('📞 ', '');
            
            selectCustomer(customerId, customerName, customerEmail, customerPhone);
        });
    }
    
    /**
     * Select a customer
     */
    function selectCustomer(id, name, email, phone) {
        // Update hidden field
        $('#customer_id').val(id);
        
        // Update display
        const selectedInfo = $('.selected-customer-info');
        let html = `
            <div class="selected-customer">
                <div class="customer-name"><strong>${name}</strong></div>
                <div class="customer-email"><strong>Email:</strong> ${email}</div>
                <div class="customer-role"><strong>Ruolo:</strong> Rental Customer</div>
                ${phone ? `<div class="customer-phone"><strong>Telefono:</strong> ${phone}</div>` : ''}
                <button type="button" class="button-link remove-customer">❌ Rimuovi selezione</button>
            </div>
        `;
        
        selectedInfo.html(html).show();
        
        // Clear search
        $('#customer_search').val('');
        $('.customer-search-results').hide();
        
        // Handle remove selection
        $('.remove-customer').on('click', function() {
            $('#customer_id').val('');
            selectedInfo.hide().empty();
        });
    }
    
    /**
     * Show create customer modal
     */
    function showCreateCustomerModal() {
        const modal = `
            <div id="create-customer-modal" class="crcm-modal">
                <div class="crcm-modal-content">
                    <span class="crcm-modal-close">&times;</span>
                    <h2>👤 Crea Nuovo Cliente</h2>
                    <form id="create-customer-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="new_customer_name">Nome Completo *</label></th>
                                <td><input type="text" id="new_customer_name" required /></td>
                            </tr>
                            <tr>
                                <th><label for="new_customer_email">Email *</label></th>
                                <td><input type="email" id="new_customer_email" required /></td>
                            </tr>
                            <tr>
                                <th><label for="new_customer_phone">Telefono</label></th>
                                <td><input type="tel" id="new_customer_phone" /></td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary">✅ Crea Cliente</button>  
                            <button type="button" class="button cancel-create">❌ Annulla</button>
                        </p>
                    </form>
                </div>
            </div>
        `;
        
        $('body').append(modal);
        $('#create-customer-modal').show();
        
        // Handle form submission
        $('#create-customer-form').on('submit', function(e) {
            e.preventDefault();
            createNewCustomer();
        });
        
        // Handle modal close
        $('.crcm-modal-close, .cancel-create').on('click', function() {
            $('#create-customer-modal').remove();
        });
    }
    
    /**
     * Create new customer via AJAX
     */
    function createNewCustomer() {
        const name = $('#new_customer_name').val().trim();
        const email = $('#new_customer_email').val().trim();
        const phone = $('#new_customer_phone').val().trim();
        
        if (!name || !email) {
            showError('Nome e email sono obbligatori');
            return;
        }
        
        $('.button').prop('disabled', true);
        
        $.ajax({
            url: crcm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'crcm_create_customer',
                name: name,
                email: email,
                phone: phone,
                nonce: crcm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const customer = response.data;
                    selectCustomer(customer.user_id, customer.name, customer.email, phone);
                    $('#create-customer-modal').remove();
                    showSuccess('Cliente creato con successo!');
                } else {
                    showError('Errore nella creazione cliente: ' + response.data);
                }
            },
            error: function() {
                showError('Errore di connessione durante la creazione del cliente');
            },
            complete: function() {
                $('.button').prop('disabled', false);
            }
        });
    }
    
    // ===============================================
    // FORM VALIDATION (ENHANCED)
    // ===============================================
    
    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Booking form validation
        $('#post').on('submit', function(e) {
            if ($(this).find('input[name="post_type"]').val() === 'crcm_booking') {
                return validateBookingForm(e);
            }
        });
        
        // Real-time validation
        $('#pickup_date, #return_date').on('change', function() {
            validateDates();
        });
        
        $('#customer_id').on('change', function() {
            validateCustomer();
        });
        
        $('#vehicle_id').on('change', function() {
            validateVehicle();
        });
    }
    
    /**
     * Validate booking form
     */
    function validateBookingForm(e) {
        let isValid = true;
        const errors = [];
        
        // Check customer selection
        if (!$('#customer_id').val()) {
            errors.push('Seleziona un cliente per la prenotazione');
            $('#customer_search').addClass('error');
            isValid = false;
        }
        
        // Check vehicle selection
        if (!$('#vehicle_id').val()) {
            errors.push('Seleziona un veicolo per la prenotazione');
            $('#vehicle_id').addClass('error');
            isValid = false;
        }
        
        // Check dates
        if (!validateDates()) {
            isValid = false;
        }
        
        // Check availability
        const availabilityStatus = $('.availability-status .availability-error').length;
        if (availabilityStatus > 0) {
            errors.push('Il veicolo selezionato non è disponibile per le date scelte');
            isValid = false;
        }
        
        // Check pricing calculation
        if (!currentPricing.final_total || currentPricing.final_total <= 0) {
            errors.push('Errore nel calcolo del prezzo totale');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            showError('Errori nel form:\n• ' + errors.join('\n• '));
            
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.error').first().offset().top - 100
            }, 500);
        }
        
        return isValid;
    }
    
    /**
     * Validate dates
     */
    function validateDates() {
        const pickupDate = $('#pickup_date').val();
        const returnDate = $('#return_date').val();
        let isValid = true;
        
        $('#pickup_date, #return_date').removeClass('error');
        
        if (!pickupDate || !returnDate) {
            $('#pickup_date, #return_date').addClass('error');
            isValid = false;
        } else {
            const pickup = new Date(pickupDate);
            const returnD = new Date(returnDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (pickup < today) {
                $('#pickup_date').addClass('error');
                isValid = false;
            }
            
            if (returnD <= pickup) {
                $('#return_date').addClass('error');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    /**
     * Validate customer
     */
    function validateCustomer() {
        const customerId = $('#customer_id').val();
        $('#customer_search').toggleClass('error', !customerId);
        return !!customerId;
    }
    
    /**
     * Validate vehicle
     */
    function validateVehicle() {
        const vehicleId = $('#vehicle_id').val();
        $('#vehicle_id').toggleClass('error', !vehicleId);
        return !!vehicleId;
    }
    
    // ===============================================
    // UTILITY FUNCTIONS (ENHANCED)
    // ===============================================
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        const notice = $(`
            <div class="notice notice-success is-dismissible">
                <p><strong>✅ Successo:</strong> ${message}</p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>
            </div>
        `);
        
        $('.wrap').prepend(notice);
        
        setTimeout(function() {
            notice.fadeOut();
        }, 8000);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        const notice = $(`
            <div class="notice notice-error is-dismissible">
                <p><strong>❌ Errore:</strong> ${message}</p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>
            </div>
        `);
        
        $('.wrap').prepend(notice);
        
        setTimeout(function() {
            notice.fadeOut();
        }, 12000);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
    /**
     * Show loading state
     */
    function showLoading(element, message = 'Caricamento...') {
        $(element).html(`<div class="crcm-loading">🔄 ${message}</div>`);
    }
    
    // ===============================================
    // INITIALIZATION
    // ===============================================
    
    /**
     * Initialize everything based on current page
     */
    function init() {
        const currentScreen = $('body').attr('class');
        
        console.log('Initializing CRCM Admin JS, screen:', currentScreen);
        
        // Initialize booking manager on booking pages
        if (currentScreen && currentScreen.includes('crcm_booking')) {
            console.log('Initializing booking manager');
            initBookingManager();
        }
        
        // Initialize date pickers globally
        $('.crcm-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
        
        // Initialize general UI improvements
        initUIImprovements();
        
        console.log('CRCM Admin JS initialized successfully');
    }
    
    /**
     * Initialize UI improvements
     */
    function initUIImprovements() {
        // Make admin notices dismissible
        $(document).on('click', '.notice-dismiss', function() {
            $(this).parent().fadeOut();
        });
        
        // Add loading states to buttons
        $('.button-primary').on('click', function() {
            const $btn = $(this);
            if ($btn.attr('type') === 'submit') {
                const originalText = $btn.text();
                $btn.prop('disabled', true).text('⏳ Salvando...');
                
                setTimeout(function() {
                    $btn.prop('disabled', false).text(originalText);
                }, 5000);
            }
        });
        
        // Improve form UX
        $('input[required], select[required]').on('blur', function() {
            $(this).toggleClass('error', !$(this).val());
        });
        
        // Auto-focus first input in modals
        $(document).on('shown.bs.modal', '.crcm-modal', function() {
            $(this).find('input:first').focus();
        });
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.which === 83) {
                e.preventDefault();
                $('#publish, #save-post').click();
            }
        });
    }
    
    // Start everything
    init();
    
    // Also initialize on AJAX complete (for dynamic content)
    $(document).ajaxComplete(function() {
        $('.crcm-datepicker:not(.hasDatepicker)').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    });
    
    // Debug function
    window.crcmDebug = function() {
        console.log('CRCM Debug Info:', {
            selectedVehicleData,
            currentBookingDays,
            currentPricing,
            ajaxUrl: crcm_admin.ajax_url,
            nonce: crcm_admin.nonce
        });
    };
    
    console.log('CRCM Enhanced Admin JS loaded successfully');
});
