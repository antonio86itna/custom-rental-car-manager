/**
 * Custom Rental Car Manager - Frontend JavaScript
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Global CRCM object
    window.CRCM = {
        init: function() {
            this.initSearchForm();
            this.initBookingForm();
            this.initCustomerDashboard();
            this.initDatePickers();
            this.initVehicleActions();
            this.initPagination();
        },

        // Initialize search form
        initSearchForm: function() {
            $('#crcm-vehicle-search').on('submit', function(e) {
                e.preventDefault();
                CRCM.searchVehicles();
            });

            // Set minimum dates
            const today = new Date().toISOString().split('T')[0];
            $('#pickup_date, #return_date').attr('min', today);

            // Auto-set return date when pickup date changes
            $('#pickup_date').on('change', function() {
                const pickupDate = new Date($(this).val());
                const returnDate = new Date(pickupDate);
                returnDate.setDate(returnDate.getDate() + 1);

                $('#return_date').attr('min', returnDate.toISOString().split('T')[0]);

                if (!$('#return_date').val()) {
                    $('#return_date').val(returnDate.toISOString().split('T')[0]);
                }
            });
        },

        // Search vehicles via AJAX
        searchVehicles: function(paged = 1) {
            const $form = $('#crcm-vehicle-search');
            const $button = $form.find('.crcm-search-btn');
            const $results = $('#crcm-search-results');

            const perPage = parseInt($form.data('per-page'), 10) || 6;
            const formData = {
                action: 'crcm_search_vehicles',
                nonce: crcm_ajax.nonce,
                pickup_date: $('#pickup_date').val(),
                return_date: $('#return_date').val(),
                vehicle_type: $('#vehicle_type').val(),
                pickup_location: $('#pickup_location').val(),
                posts_per_page: perPage,
                paged: paged
            };

            // Validate dates
            if (!formData.pickup_date || !formData.return_date) {
                CRCM.showError('Please select both pickup and return dates.');
                return;
            }

            if (new Date(formData.pickup_date) >= new Date(formData.return_date)) {
                CRCM.showError('Return date must be after pickup date.');
                return;
            }

            // Show loading
            $button.prop('disabled', true).html('<span class="crcm-loading"></span> Searching...');
            $results.show().find('#crcm-results-content').html('<div class="crcm-loading-message">Searching available vehicles...</div>');

            $.ajax({
                url: crcm_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        CRCM.displaySearchResults(response.data);
                    } else {
                        CRCM.showError(response.data || 'Search failed. Please try again.');
                    }
                },
                error: function() {
                    CRCM.showError('Connection error. Please check your internet connection and try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).html('Search Vehicles');
                }
            });
        },

        // Display search results
        displaySearchResults: function(data) {
            const vehicles = data.vehicles || [];
            const pagination = data.pagination || {current: 1, total: 1};
            const $container = $('#crcm-results-content');

            if (vehicles.length === 0) {
                $container.html('<div class="crcm-no-results"><p>No vehicles available for the selected dates.</p></div>');
                $('#crcm-pagination').html('');
                return;
            }

            let html = '<div class="crcm-vehicles-grid">';

            vehicles.forEach(function(vehicle) {
                html += CRCM.buildVehicleCard(vehicle);
            });

            html += '</div>';
            $container.html(html);

            CRCM.renderPagination(pagination.current, pagination.total);
        },

        // Initialize pagination events
        initPagination: function() {
            $(document).on('click', '.crcm-page-link', function(e) {
                e.preventDefault();
                const paged = $(this).data('page');
                CRCM.searchVehicles(paged);
            });
        },

        // Render pagination links
        renderPagination: function(current, total) {
            const $pagination = $('#crcm-pagination');
            if (total <= 1) {
                $pagination.html('');
                return;
            }

            let html = '';

            if (current > 1) {
                html += `<a href="#" class="crcm-page-link" data-page="${current - 1}">&laquo; Prev</a>`;
            }

            for (let i = 1; i <= total; i++) {
                const active = i === current ? 'active' : '';
                html += `<a href="#" class="crcm-page-link ${active}" data-page="${i}">${i}</a>`;
            }

            if (current < total) {
                html += `<a href="#" class="crcm-page-link" data-page="${current + 1}">Next &raquo;</a>`;
            }

            $pagination.html(html);
        },

        // Build vehicle card HTML
        buildVehicleCard: function(vehicle) {
            const imageHtml = vehicle.thumbnail ? 
                '<img src="' + vehicle.thumbnail + '" alt="' + vehicle.title + '" />' :
                '<div class="crcm-no-image"><span class="dashicons dashicons-car"></span></div>';

            return `
                <div class="crcm-vehicle-card">
                    <div class="crcm-vehicle-image">
                        ${imageHtml}
                    </div>
                    <div class="crcm-vehicle-content">
                        <h3>${vehicle.title}</h3>
                        <div class="crcm-vehicle-price">
                            <span class="crcm-price">
                                ${crcm_ajax.currency_symbol}${vehicle.daily_rate}
                                <small>/day</small>
                            </span>
                        </div>
                        <div class="crcm-vehicle-actions">
                            <a href="#" class="crcm-btn crcm-btn-primary crcm-book-now" data-vehicle-id="${vehicle.id}">
                                Book Now
                            </a>
                            <a href="${vehicle.permalink}" class="crcm-btn crcm-btn-secondary">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            `;
        },

        // Initialize booking form
        initBookingForm: function() {
            $('#crcm-booking-form').on('submit', function(e) {
                e.preventDefault();
                CRCM.submitBooking();
            });

            // Home delivery toggle
            $('#home_delivery').on('change', function() {
                const $addressGroup = $('.crcm-delivery-address');
                if ($(this).is(':checked')) {
                    $addressGroup.slideDown();
                } else {
                    $addressGroup.slideUp();
                }
            });

            // Calculate pricing when dates change
            $('#pickup_date, #return_date').on('change', function() {
                CRCM.calculatePricing();
            });

            // Calculate pricing when extras change
            $('input[name="extras[]"]').on('change', function() {
                CRCM.calculatePricing();
            });
        },

        // Submit booking
        submitBooking: function() {
            const $form = $('#crcm-booking-form');
            const $button = $form.find('.crcm-submit-btn');

            // Basic validation
            if (!CRCM.validateBookingForm()) {
                return;
            }

            const formData = $form.serialize() + '&action=crcm_create_booking&nonce=' + crcm_ajax.nonce;

            // Show loading
            $button.prop('disabled', true).html('<span class="crcm-loading"></span> Processing...');

            $.ajax({
                url: crcm_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            CRCM.showSuccess('Booking submitted successfully!');
                            $form[0].reset();
                        }
                    } else {
                        CRCM.showError(response.data || 'Booking failed. Please try again.');
                    }
                },
                error: function() {
                    CRCM.showError('Connection error. Please try again.');
                },
                complete: function() {
                    $button.prop('disabled', false).html('Submit Booking Request');
                }
            });
        },

        // Validate booking form
        validateBookingForm: function() {
            const $form = $('#crcm-booking-form');
            let isValid = true;

            // Clear previous errors
            $('.crcm-field-error').remove();

            // Check required fields
            $form.find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    CRCM.showFieldError($(this), 'This field is required.');
                    isValid = false;
                }
            });

            // Validate email
            const email = $('#email').val();
            if (email && !CRCM.isValidEmail(email)) {
                CRCM.showFieldError($('#email'), 'Please enter a valid email address.');
                isValid = false;
            }

            // Validate dates
            const pickupDate = new Date($('#pickup_date').val());
            const returnDate = new Date($('#return_date').val());
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (pickupDate < today) {
                CRCM.showFieldError($('#pickup_date'), 'Pickup date cannot be in the past.');
                isValid = false;
            }

            if (returnDate <= pickupDate) {
                CRCM.showFieldError($('#return_date'), 'Return date must be after pickup date.');
                isValid = false;
            }

            return isValid;
        },

        // Initialize customer dashboard
        initCustomerDashboard: function() {
            // Tab switching
            $('.crcm-tab-btn').on('click', function() {
                const tabId = $(this).data('tab');

                $('.crcm-tab-btn').removeClass('active');
                $(this).addClass('active');

                $('.crcm-tab-content').hide();
                $('#' + tabId + '-tab').show();
            });

            // Profile form submission
            $('.crcm-profile-form').on('submit', function(e) {
                e.preventDefault();
                CRCM.updateProfile();
            });
        },

        // Initialize date pickers
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.crcm-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0,
                    showAnim: 'slideDown'
                });
            }
        },

        // Initialize vehicle actions
        initVehicleActions: function() {
            $(document).on('click', '.crcm-book-now, [data-vehicle-id]', function(e) {
                e.preventDefault();
                const vehicleId = $(this).data('vehicle-id');

                if (vehicleId) {
                    // Redirect to booking form or open modal
                    const bookingUrl = '/booking-form/?vehicle=' + vehicleId;
                    window.location.href = bookingUrl;
                }
            });
        },

        // Calculate pricing
        calculatePricing: function() {
            const pickupDate = $('#pickup_date').val();
            const returnDate = $('#return_date').val();

            if (!pickupDate || !returnDate) {
                return;
            }

            const days = CRCM.calculateDays(pickupDate, returnDate);

            if (days <= 0) {
                return;
            }

            // Basic pricing calculation (extend as needed)
            let total = 0;
            const dailyRate = parseFloat($('#daily_rate').val() || 0);

            total = dailyRate * days;

            // Add extras
            $('input[name="extras[]"]:checked').each(function() {
                const extraCost = parseFloat($(this).data('cost') || 0);
                total += extraCost * days;
            });

            // Update display
            $('.crcm-pricing-summary .total').text(crcm_ajax.currency_symbol + total.toFixed(2));
            $('.crcm-pricing-summary .days').text(days + ' days');
        },

        // Calculate days between dates
        calculateDays: function(startDate, endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const timeDiff = end.getTime() - start.getTime();
            return Math.max(1, Math.ceil(timeDiff / (1000 * 3600 * 24)));
        },

        // Update profile
        updateProfile: function() {
            const $form = $('.crcm-profile-form');
            const formData = $form.serialize() + '&action=crcm_update_profile&nonce=' + crcm_ajax.nonce;

            $.ajax({
                url: crcm_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        CRCM.showSuccess('Profile updated successfully!');
                    } else {
                        CRCM.showError(response.data || 'Update failed.');
                    }
                },
                error: function() {
                    CRCM.showError('Connection error. Please try again.');
                }
            });
        },

        // Utility functions
        showError: function(message) {
            CRCM.showNotification(message, 'error');
        },

        showSuccess: function(message) {
            CRCM.showNotification(message, 'success');
        },

        showNotification: function(message, type) {
            const className = type === 'error' ? 'crcm-error' : 'crcm-success';
            const $notification = $('<div class="' + className + '">' + message + '</div>');

            // Remove existing notifications
            $('.crcm-error, .crcm-success').remove();

            // Add new notification
            $('body').prepend($notification);

            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        showFieldError: function($field, message) {
            const $error = $('<div class="crcm-field-error" style="color: #dc2626; font-size: 14px; margin-top: 4px;">' + message + '</div>');
            $field.closest('.crcm-form-group').append($error);
            $field.focus();
        },

        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    };

    // Cancel booking function (global)
    window.cancelBooking = function(bookingId) {
        if (!confirm('Are you sure you want to cancel this booking?')) {
            return;
        }

        $.ajax({
            url: crcm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'crcm_cancel_booking',
                booking_id: bookingId,
                nonce: crcm_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Cancellation failed.');
                }
            },
            error: function() {
                alert('Connection error. Please try again.');
            }
        });
    };

    // Initialize when document is ready
    $(document).ready(function() {
        CRCM.init();
    });

})(jQuery);
