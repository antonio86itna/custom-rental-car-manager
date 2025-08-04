/**
 * Enhanced Admin JavaScript for Custom Rental Car Manager
 * 
 * COMPLETE ECOSYSTEM INTEGRATION with fixed AJAX connections
 * and proper error handling for booking management.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('CRCM Admin JS: Starting initialization...');
    
    // Check if crcm_admin object exists
    if (typeof crcm_admin === 'undefined') {
        console.error('CRCM Admin JS: crcm_admin object not found! Check wp_localize_script.');
        return;
    }
    
    console.log('CRCM Admin JS: Configuration loaded', crcm_admin);
    
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
        console.log('CRCM: Initializing booking manager...');
        
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
            console.log('CRCM: Vehicle selection changed to:', vehicleId);
            
            if (vehicleId) {
                loadVehicleData(vehicleId);
            } else {
                clearVehicleData();
            }
        });
        
        // Date and time changes
        $('#pickup_date, #return_date, #pickup_time, #return_time').on('change', function() {
            console.log('CRCM: Date/time changed');
            calculateRentalDays();
            checkVehicleAvailability();
            if (selectedVehicleData) {
                calculateAdvancedPricing();
            }
        });
        
        // Service selections changes
        $(document).on('change', 'input[name="selected_extras[]"], input[name="selected_insurance"], #manual_discount', function() {
            console.log('CRCM: Service selection changed');
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
        
        console.log('CRCM: Booking manager initialized successfully');
    }
    
    /**
     * Initialize existing booking data for editing
     */
    function initializeExistingBooking() {
        const vehicleId = $('#vehicle_id').val();
        if (vehicleId) {
            console.log('CRCM: Loading existing vehicle data for editing:', vehicleId);
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
            $('.rental-days-display').text('Rental days: ' + currentBookingDays);
            $('#rental_days').val(currentBookingDays);
            
            console.log('CRCM: Calculated rental days:', currentBookingDays);
        }
    }
    
    /**
     * Load vehicle data via AJAX with enhanced error handling
     */
    function loadVehicleData(vehicleId) {
        console.log('CRCM: Loading vehicle data for ID:', vehicleId);
        
        // Show loading state
        $('.vehicle-details-container').html('<div class="crcm-loading">🔄 Loading vehicle data...</div>');
        $('.availability-status').html('<div class="crcm-loading">🔄 Checking availability...</div>');
        $('.extras-container, .insurance-container .premium-insurance-container').html('<div class="crcm-loading">🔄 Loading services...</div>');
        
        $.ajax({
            url: crcm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'crcm_get_vehicle_booking_data',
                vehicle_id: vehicleId,
                nonce: crcm_admin.nonce
            },
            timeout: 10000, // 10 second timeout
            success: function(response) {
                console.log('CRCM: Vehicle data AJAX response:', response);
                
                if (response.success) {
                    selectedVehicleData = response.data;
                    console.log('CRCM: Vehicle data loaded successfully:', selectedVehicleData);
                    
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
                    console.error('CRCM: Vehicle data loading failed:', response.data);
                    showError('Error loading vehicle data: ' + response.data);
                    clearVehicleData();
                }
            },
            error: function(xhr, status, error) {
                console.error('CRCM: Vehicle data AJAX error:', {xhr, status, error});
                console.error('CRCM: Response text:', xhr.responseText);
                
                let errorMessage = 'Connection error loading vehicle data.';
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Permission denied. Please refresh the page.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please check server logs.';
                }
                
                showError(errorMessage);
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
        $('.vehicle-details-container').html('<p class="description">Select a vehicle to view details</p>');
        $('.extras-container').html('<p class="description">Select a vehicle to view available extra services</p>');
        $('.premium-insurance-container').html('');
        $('.availability-status').html('<p class="description">Select a vehicle and dates to check availability</p>');
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
            container.html('<p class="description">No extra services available for this vehicle</p>');
            return;
        }
        
        let html = '<div class="extras-list">';
        
        extras.forEach(function(extra, index) {
            html += `
                <div class="extra-item">
                    <label>
                        <input type="checkbox" name="selected_extras[]" value="${index}" data-rate="${extra.daily_rate}" data-name="${extra.name}">
                        <strong>${extra.name}</strong>
                        <span class="extra-price">+€${parseFloat(extra.daily_rate).toFixed(2)}/day</span>
                    </label>
                </div>
            `;
        });
        
        html += '</div>';
        container.html(html);
        
        console.log('CRCM: Populated extras:', extras.length, 'items');
    }
    
    /**
     * Populate insurance section with radio buttons
     */
    function populateInsuranceSection(insurance) {
        const container = $('.premium-insurance-container');
        container.empty();
        
        // Premium insurance (if available)
        if (insurance && insurance.premium && insurance.premium.enabled) {
            const deductible = insurance.premium.deductible || 500;
            const dailyRate = parseFloat(insurance.premium.daily_rate || 0);
            
            let html = `
                <div class="insurance-option">
                    <label>
                        <input type="radio" name="pricing_breakdown[selected_insurance]" value="premium" data-rate="${dailyRate}">
                        <strong>Premium Insurance</strong>
                        <span class="insurance-price">+€${dailyRate.toFixed(2)}/day</span>
                        <br><small>RCA + €${deductible} Deductible + Theft & Fire + Accidental Damage</small>
                    </label>
                </div>
            `;
            
            container.html(html);
            console.log('CRCM: Populated premium insurance, daily rate:', dailyRate);
        } else {
            console.log('CRCM: No premium insurance available for this vehicle');
        }
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
        
        console.log('CRCM: Checking availability for vehicle:', vehicleId);
        
        $('.availability-status').html('<div class="crcm-loading">🔄 Checking availability...</div>');
        
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
                console.log('CRCM: Availability check response:', response);
                
                if (response.success) {
                    const data = response.data;
                    let html = '';
                    
                    if (data.is_available) {
                        html = `
                            <div class="availability-success">
                                ✅ <strong>Available</strong><br>
                                ${data.available_quantity} of ${data.total_quantity} units available
                            </div>
                        `;
                    } else {
                        html = `
                            <div class="availability-error">
                                ❌ <strong>Not Available</strong><br>
                                All units are booked for this period
                            </div>
                        `;
                    }
                    
                    $('.availability-status').html(html);
                } else {
                    $('.availability-status').html('<div class="availability-error">❌ Error checking availability</div>');
                }
            },
            error: function() {
                $('.availability-status').html('<div class="availability-error">❌ Connection error</div>');
            }
        });
    }
    
    /**
     * Calculate advanced pricing with all components
     */
    function calculateAdvancedPricing() {
        const vehicleId = $('#vehicle_id').val();
        const pickupDate = $('#pickup_date').val();
        const returnDate = $('#return_date').val();
        const pickupTime = $('#pickup_time').val();
        const returnTime = $('#return_time').val();
        
        if (!vehicleId || !pickupDate || !returnDate || !selectedVehicleData) {
            console.log('CRCM: Missing data for pricing calculation');
            return;
        }
        
        // Collect selected extras
        const selectedExtras = [];
        $('input[name="selected_extras[]"]:checked').each(function() {
            selectedExtras.push(parseInt($(this).val()));
        });
        
        // Get selected insurance
        const selectedInsurance = $('input[name="pricing_breakdown[selected_insurance]"]:checked').val() || 'basic';
        
        // Get manual discount
        const manualDiscount = parseFloat($('#manual_discount').val()) || 0;
        
        console.log('CRCM: Calculating pricing with:', {
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
                action: 'crcm_calculate_booking_total',
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
            timeout: 15000, // 15 second timeout
            success: function(response) {
                console.log('CRCM: Pricing calculation response:', response);
                
                if (response.success) {
                    currentPricing = response.data;
                    console.log('CRCM: Pricing calculated successfully:', currentPricing);
                    
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
                    console.error('CRCM: Pricing calculation failed:', response.data);
                    showError('Error calculating prices: ' + response.data);
                    resetPricingDisplay();
                }
            },
            error: function(xhr, status, error) {
                console.error('CRCM: Pricing calculation AJAX error:', {xhr, status, error});
                console.error('CRCM: Response text:', xhr.responseText);
                
                let errorMessage = 'Connection error during price calculation.';
                if (status === 'timeout') {
                    errorMessage = 'Price calculation timed out. Please try again.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Permission denied during price calculation.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error during price calculation. Check server logs.';
                }
                
                showError(errorMessage);
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
        
        console.log('CRCM: Updated pricing display:', {
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
        
        console.log('CRCM: Updated calculation log with', calculationLog.length, 'lines');
    }
    
    /**
     * Clear calculation log
     */
    function clearCalculationLog() {
        $('.log-content').html('<p class="description">Detailed calculations will appear here when you select a vehicle and set dates</p>');
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
        
        console.log('CRCM: Updated pricing fields with final total:', pricing.final_total);
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
        
        // Remove customer selection
        $(document).on('click', '.remove-customer', function() {
            $('#customer_id').val('');
            selectedCustomer.hide().empty();
        });
    }
    
    /**
     * Search customers via AJAX
     */
    function searchCustomers(query) {
        const resultsContainer = $('.customer-search-results');
        resultsContainer.html('<div class="crcm-loading">🔍 Searching customers...</div>').show();
        
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
                resultsContainer.html('<div class="no-results">❌ Error searching customers</div>');
            }
        });
    }
    
    /**
     * Display customer search results
     */
    function displayCustomerResults(customers) {
        const resultsContainer = $('.customer-search-results');
        
        if (!customers.length) {
            resultsContainer.html('<div class="no-results">👤 No customers found</div>');
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
                <h4>${name}</h4>
                <p><strong>Email:</strong> ${email}</p>
                <p><strong>Role:</strong> Rental Customer</p>
                ${phone ? `<p><strong>Phone:</strong> ${phone}</p>` : ''}
                <button type="button" class="button remove-customer">Remove Selection</button>
            </div>
        `;
        
        selectedInfo.html(html).show();
        
        // Clear search
        $('#customer_search').val('');
        $('.customer-search-results').hide();
    }
    
    /**
     * Show create customer modal
     */
    function showCreateCustomerModal() {
        const modal = `
            <div id="create-customer-modal" class="crcm-modal">
                <div class="crcm-modal-content">
                    <span class="crcm-modal-close">&times;</span>
                    <h2>👤 Create New Customer</h2>
                    <form id="create-customer-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="new_customer_name">Full Name *</label></th>
                                <td><input type="text" id="new_customer_name" required /></td>
                            </tr>
                            <tr>
                                <th><label for="new_customer_email">Email *</label></th>
                                <td><input type="email" id="new_customer_email" required /></td>
                            </tr>
                            <tr>
                                <th><label for="new_customer_phone">Phone</label></th>
                                <td><input type="tel" id="new_customer_phone" /></td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary">✅ Create Customer</button>  
                            <button type="button" class="button cancel-create">❌ Cancel</button>
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
            showError('Name and email are required');
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
                    showSuccess('Customer created successfully!');
                } else {
                    showError('Error creating customer: ' + response.data);
                }
            },
            error: function() {
                showError('Connection error during customer creation');
            },
            complete: function() {
                $('.button').prop('disabled', false);
            }
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
            errors.push('Select a customer for the booking');
            $('#customer_search').addClass('error');
            isValid = false;
        }
        
        // Check vehicle selection
        if (!$('#vehicle_id').val()) {
            errors.push('Select a vehicle for the booking');
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
            errors.push('Selected vehicle is not available for chosen dates');
            isValid = false;
        }
        
        // Check pricing calculation
        if (!currentPricing.final_total || currentPricing.final_total <= 0) {
            errors.push('Error in total price calculation');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            showError('Form errors:\n• ' + errors.join('\n• '));
            
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
    // UTILITY FUNCTIONS
    // ===============================================
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        const notice = $(`
            <div class="notice notice-success is-dismissible">
                <p><strong>✅ Success:</strong> ${message}</p>
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
                <p><strong>❌ Error:</strong> ${message}</p>
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
    
    // ===============================================
    // INITIALIZATION
    // ===============================================
    
    /**
     * Initialize everything based on current page
     */
    function init() {
        const currentScreen = $('body').attr('class');
        
        console.log('CRCM: Initializing, screen classes:', currentScreen);
        
        // Initialize booking manager on booking pages
        if (currentScreen && currentScreen.includes('crcm_booking')) {
            console.log('CRCM: Initializing booking manager');
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
        
        console.log('CRCM: Initialization completed successfully');
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
                $btn.prop('disabled', true).text('⏳ Saving...');
                
                setTimeout(function() {
                    $btn.prop('disabled', false).text(originalText);
                }, 5000);
            }
        });
        
        // Improve form UX
        $('input[required], select[required]').on('blur', function() {
            $(this).toggleClass('error', !$(this).val());
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
            nonce: crcm_admin.nonce,
            adminObject: crcm_admin
        });
    };
    
    console.log('CRCM Enhanced Admin JS loaded successfully');
    
    // Add CSS for modal
    $('<style>').prop('type', 'text/css').html(`
        .crcm-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .crcm-modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 500px;
            border-radius: 5px;
        }
        .crcm-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .crcm-modal-close:hover {
            color: black;
        }
        .error {
            border-color: #dc3232 !important;
            box-shadow: 0 0 2px rgba(204, 0, 0, 0.8);
        }
    `).appendTo('head');
});
