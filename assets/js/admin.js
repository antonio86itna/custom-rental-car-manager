/**
 * Admin JavaScript for Custom Rental Car Manager
 * 
 * Handles all AJAX interactions for the booking manager,
 * vehicle selection, customer search, and pricing calculations.
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
    
    // ===============================================
    // BOOKING MANAGER FUNCTIONALITY
    // ===============================================
    
    /**
     * Initialize booking manager
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
        
        // Date changes
        $('#pickup_date, #return_date').on('change', function() {
            calculateRentalDays();
            checkVehicleAvailability();
            if (selectedVehicleData) {
                calculatePricing();
            }
        });
        
        // Time changes
        $('#pickup_time, #return_time').on('change', function() {
            if (selectedVehicleData) {
                calculatePricing();
            }
        });
        
        // Extra services changes
        $(document).on('change', 'input[name="selected_extras[]"]', function() {
            calculatePricing();
        });
        
        // Insurance selection changes
        $(document).on('change', 'input[name="selected_insurance"]', function() {
            calculatePricing();
        });
        
        // Manual discount changes
        $('#manual_discount').on('input', function() {
            calculatePricing();
        });
        
        // Customer search
        initCustomerSearch();
        
        // Form validation
        initFormValidation();
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
            $('.rental-days-display').text(currentBookingDays + ' giorni');
            $('#rental_days').val(currentBookingDays);
            
            // Update pricing if vehicle is selected
            if (selectedVehicleData) {
                calculatePricing();
            }
        }
    }
    
    /**
     * Load vehicle data via AJAX
     */
    function loadVehicleData(vehicleId) {
        // Show loading state
        $('.vehicle-details-container').html('<div class="crcm-loading">🔄 Caricamento dati veicolo...</div>');
        $('.availability-status').html('<div class="crcm-loading">🔄 Controllo disponibilità...</div>');
        
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
                    
                    // Update vehicle details display
                    $('.vehicle-details-container').html(response.data.details);
                    
                    // Populate extras
                    populateExtrasSection(response.data.extras);
                    
                    // Populate insurance
                    populateInsuranceSection(response.data.insurance);
                    
                    // Check availability
                    checkVehicleAvailability();
                    
                    // Calculate initial pricing
                    calculatePricing();
                    
                } else {
                    showError('Errore nel caricamento dati veicolo: ' + response.data);
                    clearVehicleData();
                }
            },
            error: function() {
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
        $('.vehicle-details-container').html('<p class="description">Seleziona un veicolo per visualizzare i dettagli</p>');
        $('.extras-container').empty();
        $('.insurance-container').empty();
        $('.availability-status').html('<p class="description">Seleziona un veicolo per controllare la disponibilità</p>');
        resetPricingDisplay();
    }
    
    /**
     * Populate extras section
     */
    function populateExtrasSection(extras) {
        const container = $('.extras-container');
        container.empty();
        
        if (!extras || extras.length === 0) {
            container.html('<p class="description">Nessun servizio extra disponibile per questo veicolo</p>');
            return;
        }
        
        let html = '<h4>📋 Servizi Extra Disponibili</h4>';
        html += '<div class="extras-list">';
        
        extras.forEach(function(extra, index) {
            html += `
                <div class="extra-item">
                    <label>
                        <input type="checkbox" name="selected_extras[]" value="${index}" data-rate="${extra.daily_rate}">
                        <strong>${extra.name}</strong>
                        <span class="extra-price">+€${parseFloat(extra.daily_rate).toFixed(2)}/giorno</span>
                    </label>
                </div>
            `;
        });
        
        html += '</div>';
        container.html(html);
    }
    
    /**
     * Populate insurance section
     */
    function populateInsuranceSection(insurance) {
        const container = $('.insurance-container');
        container.empty();
        
        let html = '<h4>🛡️ Opzioni Assicurative</h4>';
        
        // Basic insurance (always included)
        html += `
            <div class="insurance-option basic">
                <label>
                    <input type="radio" name="selected_insurance" value="basic" checked>
                    <strong>Assicurazione Base (Inclusa)</strong>
                    <div class="insurance-details">
                        <small>✅ RCA - Responsabilità Civile Auto</small>
                    </div>
                </label>
            </div>
        `;
        
        // Premium insurance (if available)
        if (insurance && insurance.premium && insurance.premium.enabled) {
            html += `
                <div class="insurance-option premium">
                    <label>
                        <input type="radio" name="selected_insurance" value="premium" data-rate="${insurance.premium.daily_rate}">
                        <strong>Assicurazione Premium</strong>
                        <span class="insurance-price">+€${parseFloat(insurance.premium.daily_rate).toFixed(2)}/giorno</span>
                        <div class="insurance-details">
                            <small>✅ RCA + Franchigia €${insurance.premium.deductible} + Furto e Incendio</small>
                        </div>
                    </label>
                </div>
            `;
        }
        
        container.html(html);
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
     * Calculate pricing breakdown
     */
    function calculatePricing() {
        if (!selectedVehicleData || !currentBookingDays) {
            return;
        }
        
        const pricing = selectedVehicleData.pricing;
        if (!pricing) {
            return;
        }
        
        // Base total
        const baseRate = parseFloat(pricing.daily_rate) || 0;
        const baseTotal = baseRate * currentBookingDays;
        
        // Calculate extras total
        let extrasTotal = 0;
        $('input[name="selected_extras[]"]:checked').each(function() {
            const rate = parseFloat($(this).data('rate')) || 0;
            extrasTotal += rate * currentBookingDays;
        });
        
        // Calculate insurance total
        let insuranceTotal = 0;
        const selectedInsurance = $('input[name="selected_insurance"]:checked').val();
        if (selectedInsurance === 'premium') {
            const insuranceRate = parseFloat($('input[name="selected_insurance"]:checked').data('rate')) || 0;
            insuranceTotal = insuranceRate * currentBookingDays;
        }
        
        // Manual discount
        const discount = parseFloat($('#manual_discount').val()) || 0;
        
        // Final total
        const finalTotal = Math.max(0, baseTotal + extrasTotal + insuranceTotal - discount);
        
        // Update display
        updatePricingDisplay(baseTotal, extrasTotal, insuranceTotal, discount, finalTotal);
        
        // Update hidden fields
        updatePricingFields(baseTotal, extrasTotal, insuranceTotal, discount, finalTotal);
    }
    
    /**
     * Update pricing display
     */
    function updatePricingDisplay(baseTotal, extrasTotal, insuranceTotal, discount, finalTotal) {
        $('.base-total').text('€' + baseTotal.toFixed(2));
        $('.extras-total').text('€' + extrasTotal.toFixed(2));
        $('.insurance-total').text('€' + insuranceTotal.toFixed(2));
        $('.discount-total').text('-€' + discount.toFixed(2));
        $('.final-total').text('€' + finalTotal.toFixed(2));
    }
    
    /**
     * Reset pricing display
     */
    function resetPricingDisplay() {
        $('.base-total, .extras-total, .insurance-total, .discount-total, .final-total').text('€0.00');
    }
    
    /**
     * Update pricing hidden fields
     */
    function updatePricingFields(baseTotal, extrasTotal, insuranceTotal, discount, finalTotal) {
        $('#base_total').val(baseTotal.toFixed(2));
        $('#extras_total').val(extrasTotal.toFixed(2));
        $('#insurance_total').val(insuranceTotal.toFixed(2));
        $('#discount_total').val(discount.toFixed(2));
        $('#final_total').val(finalTotal.toFixed(2));
    }
    
    // ===============================================
    // CUSTOMER SEARCH FUNCTIONALITY
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
    // VEHICLE MANAGER FUNCTIONALITY
    // ===============================================
    
    /**
     * Initialize vehicle manager
     */
    function initVehicleManager() {
        // Vehicle type change
        $('#vehicle_type').on('change', function() {
            const vehicleType = $(this).val();
            loadVehicleFields(vehicleType);
            loadVehicleFeatures(vehicleType);
        });
        
        // Dynamic rate management
        initCustomRatesManagement();
        initAvailabilityRulesManagement();
        initExtrasManagement();
    }
    
    /**
     * Load vehicle fields based on type
     */
    function loadVehicleFields(vehicleType) {
        if (!vehicleType) return;
        
        $.ajax({
            url: crcm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'crcm_get_vehicle_fields',
                vehicle_type: vehicleType,
                nonce: crcm_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.vehicle-fields-container').html(response.data);
                }
            }
        });
    }
    
    /**
     * Initialize custom rates management
     */
    function initCustomRatesManagement() {
        // Add new rate
        $(document).on('click', '.add-custom-rate', function() {
            const container = $('.custom-rates-container');
            const index = container.find('.custom-rate-row').length;
            
            const html = `
                <div class="custom-rate-row">
                    <table class="form-table">
                        <tr>
                            <td><input type="text" name="pricing_data[custom_rates][${index}][name]" placeholder="Nome tariffa" /></td>
                            <td>
                                <select name="pricing_data[custom_rates][${index}][type]">
                                    <option value="date_range">Periodo</option>
                                    <option value="weekends">Fine Settimana</option>
                                    <option value="specific_days">Giorni Specifici</option>
                                </select>
                            </td>
                            <td><input type="date" name="pricing_data[custom_rates][${index}][start_date]" /></td>
                            <td><input type="date" name="pricing_data[custom_rates][${index}][end_date]" /></td>
                            <td><input type="number" name="pricing_data[custom_rates][${index}][extra_rate]" min="0" step="0.01" placeholder="0.00" /></td>
                            <td><button type="button" class="button remove-rate">❌</button></td>
                        </tr>
                    </table>
                </div>
            `;
            
            container.append(html);
        });
        
        // Remove rate
        $(document).on('click', '.remove-rate', function() {
            $(this).closest('.custom-rate-row').remove();
        });
    }
    
    /**
     * Initialize availability rules management
     */
    function initAvailabilityRulesManagement() {
        // Add new availability rule
        $(document).on('click', '.add-availability-rule', function() {
            const container = $('.availability-rules-container');
            const index = container.find('.availability-rule-row').length;
            const maxQuantity = $(this).data('max-quantity') || 1;
            
            let quantityOptions = '';
            for (let i = 1; i <= maxQuantity; i++) {
                quantityOptions += `<option value="${i}">${i}</option>`;
            }
            
            const html = `
                <div class="availability-rule-row">
                    <table class="form-table">
                        <tr>
                            <td><input type="text" name="availability_data[${index}][name]" placeholder="Nome regola" /></td>
                            <td><input type="date" name="availability_data[${index}][start_date]" /></td>
                            <td><input type="date" name="availability_data[${index}][end_date]" /></td>
                            <td>
                                <select name="availability_data[${index}][quantity_to_remove]">
                                    ${quantityOptions}
                                    <option value="all">Tutte</option>
                                </select>
                            </td>
                            <td><button type="button" class="button remove-rule">❌</button></td>
                        </tr>
                    </table>
                </div>
            `;
            
            container.append(html);
        });
        
        // Remove rule
        $(document).on('click', '.remove-rule', function() {
            $(this).closest('.availability-rule-row').remove();
        });
    }
    
    /**
     * Initialize extras management
     */
    function initExtrasManagement() {
        // Add new extra service
        $(document).on('click', '.add-extra-service', function() {
            const container = $('.extras-services-container');
            const index = container.find('.extra-service-row').length;
            
            const html = `
                <div class="extra-service-row">
                    <table class="form-table">
                        <tr>
                            <td>
                                <input type="text" name="extras_data[${index}][name]" placeholder="Nome servizio" />
                                <p class="description">Nome del servizio extra disponibile</p>
                            </td>
                            <td>
                                <input type="number" name="extras_data[${index}][daily_rate]" min="0" step="0.01" placeholder="0.00" />
                                <p class="description">Costo extra giornaliero</p>
                            </td>
                            <td><button type="button" class="button remove-extra">❌</button></td>
                        </tr>
                    </table>
                </div>
            `;
            
            container.append(html);
        });
        
        // Remove extra service
        $(document).on('click', '.remove-extra', function() {
            $(this).closest('.extra-service-row').remove();
        });
    }
    
    // ===============================================
    // FORM VALIDATION
    // ===============================================
    
    /**
     * Initialize form validation
     */
    function initFormValidation() {
        // Booking form validation
        $('#post').on('submit', function(e) {
            if ($(this).find('#post_type').val() === 'crcm_booking') {
                return validateBookingForm(e);
            }
        });
        
        // Vehicle form validation
        $('#post').on('submit', function(e) {
            if ($(this).find('#post_type').val() === 'crcm_vehicle') {
                return validateVehicleForm(e);
            }
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
            isValid = false;
        }
        
        // Check vehicle selection
        if (!$('#vehicle_id').val()) {
            errors.push('Seleziona un veicolo per la prenotazione');
            isValid = false;
        }
        
        // Check dates
        const pickupDate = $('#pickup_date').val();
        const returnDate = $('#return_date').val();
        
        if (!pickupDate || !returnDate) {
            errors.push('Inserisci date di ritiro e riconsegna');
            isValid = false;
        } else if (new Date(pickupDate) >= new Date(returnDate)) {
            errors.push('La data di riconsegna deve essere successiva a quella di ritiro');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            showError('Errori nel form:\n• ' + errors.join('\n• '));
        }
        
        return isValid;
    }
    
    /**
     * Validate vehicle form
     */
    function validateVehicleForm(e) {
        let isValid = true;
        const errors = [];
        
        // Check daily rate
        const dailyRate = $('#daily_rate').val();
        if (!dailyRate || parseFloat(dailyRate) <= 0) {
            errors.push('Inserisci una tariffa giornaliera valida');
            isValid = false;
        }
        
        // Check quantity
        const quantity = $('#quantity').val();
        if (!quantity || parseInt(quantity) <= 0) {
            errors.push('Inserisci una quantità disponibile valida');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            showError('Errori nel form:\n• ' + errors.join('\n• '));
        }
        
        return isValid;
    }
    
    // ===============================================
    // UTILITY FUNCTIONS
    // ===============================================
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        const notice = $(`
            <div class="notice notice-success is-dismissible">
                <p><strong>Successo:</strong> ${message}</p>
            </div>
        `);
        
        $('.wrap > h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        const notice = $(`
            <div class="notice notice-error is-dismissible">
                <p><strong>Errore:</strong> ${message}</p>
            </div>
        `);
        
        $('.wrap > h1').after(notice);
        
        setTimeout(function() {
            notice.fadeOut();
        }, 8000);
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
        
        // Initialize booking manager on booking pages
        if (currentScreen.includes('crcm_booking')) {
            initBookingManager();
        }
        
        // Initialize vehicle manager on vehicle pages
        if (currentScreen.includes('crcm_vehicle')) {
            initVehicleManager();
        }
        
        // Initialize date pickers globally
        $('.crcm-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
        
        // Initialize general UI improvements
        initUIImprovements();
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
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('⏳ ' + originalText);
            
            setTimeout(function() {
                $btn.prop('disabled', false).text(originalText);
            }, 3000);
        });
        
        // Improve form UX
        $('input[required], select[required]').on('blur', function() {
            if (!$(this).val()) {
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
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
});
