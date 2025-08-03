/*
 * Custom Rental Car Manager - Admin JavaScript
 * Enhanced admin functionality and interactions
 * Author: Totaliweb
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Global CRCM Admin object
    window.CRCMAdmin = {
        init: function() {
            this.initDatePickers();
            this.initGalleryManager();
            this.initSettingsTabs();
            this.initLocationManager();
            this.initCalendarActions();
            this.initBookingActions();
            this.initDashboardRefresh();
            this.initTooltips();
        },

        // Initialize date pickers
        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('.crcm-datepicker').datepicker({
                    dateFormat: 'yy-mm-dd',
                    minDate: 0,
                    showOtherMonths: true,
                    selectOtherMonths: true
                });
            }
        },

        // Initialize gallery manager
        initGalleryManager: function() {
            var mediaUploader;

            // Add gallery images
            $(document).on('click', '#crcm-add-gallery-images', function(e) {
                e.preventDefault();

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: 'Choose Images',
                    button: {
                        text: 'Add Images'
                    },
                    multiple: true
                });

                mediaUploader.on('select', function() {
                    var attachments = mediaUploader.state().get('selection').toJSON();
                    var currentIds = $('#crcm-gallery-ids').val().split(',').filter(Boolean);

                    attachments.forEach(function(attachment) {
                        if (currentIds.indexOf(attachment.id.toString()) === -1) {
                            currentIds.push(attachment.id);

                            var imageHtml = '<div class="crcm-gallery-image" data-id="' + attachment.id + '">' +
                                '<img src="' + attachment.sizes.thumbnail.url + '" alt="" />' +
                                '<button type="button" class="crcm-remove-image" aria-label="Remove image">&times;</button>' +
                                '</div>';

                            $('#crcm-gallery-images').append(imageHtml);
                        }
                    });

                    $('#crcm-gallery-ids').val(currentIds.join(','));
                });

                mediaUploader.open();
            });

            // Remove gallery image
            $(document).on('click', '.crcm-remove-image', function() {
                var imageDiv = $(this).parent();
                var imageId = imageDiv.data('id');
                var currentIds = $('#crcm-gallery-ids').val().split(',').filter(Boolean);
                var index = currentIds.indexOf(imageId.toString());

                if (index > -1) {
                    currentIds.splice(index, 1);
                    $('#crcm-gallery-ids').val(currentIds.join(','));
                }

                imageDiv.remove();
            });
        },

        // Initialize settings tabs
        initSettingsTabs: function() {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();

                var target = $(this).attr('href');

                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                $('.tab-content').removeClass('active');
                $(target).addClass('active');

                // Store active tab
                localStorage.setItem('crcm_active_tab', target);
            });

            // Restore active tab
            var activeTab = localStorage.getItem('crcm_active_tab');
            if (activeTab && $(activeTab).length) {
                $('.nav-tab[href="' + activeTab + '"]').click();
            }
        },

        // Initialize location manager
        initLocationManager: function() {
            var locationIndex = $('.location-item').length;

            // Add location
            $('#add-location').on('click', function() {
                var html = '<div class="location-item">' +
                    '<table class="form-table">' +
                    '<tr>' +
                    '<th><label>Location Name</label></th>' +
                    '<td><input type="text" name="locations[' + locationIndex + '][name]" class="regular-text" /></td>' +
                    '</tr>' +
                    '<tr>' +
                    '<th><label>Address</label></th>' +
                    '<td>' +
                    '<textarea name="locations[' + locationIndex + '][address]" rows="3" class="large-text"></textarea>' +
                    '<button type="button" class="button remove-location">Remove</button>' +
                    '</td>' +
                    '</tr>' +
                    '</table>' +
                    '</div>';

                $('#locations-container').append(html);
                locationIndex++;
            });

            // Remove location
            $(document).on('click', '.remove-location', function() {
                if ($('.location-item').length > 1) {
                    $(this).closest('.location-item').remove();
                } else {
                    alert('At least one location is required.');
                }
            });
        },

        // Initialize calendar actions
        initCalendarActions: function() {
            // Calendar vehicle filter
            $('#calendar-vehicle-filter').on('change', function() {
                CRCMAdmin.loadCalendarData();
            });

            // Calendar refresh
            $('#calendar-refresh').on('click', function() {
                CRCMAdmin.loadCalendarData();
            });
        },

        loadCalendarData: function() {
            var vehicleId = $('#calendar-vehicle-filter').val();
            var currentMonth = new Date().toISOString().slice(0, 7);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crcm_get_calendar_data',
                    nonce: crcm_admin.nonce,
                    month: currentMonth,
                    vehicle_id: vehicleId
                },
                beforeSend: function() {
                    $('#crcm-calendar').html('<div class="crcm-admin-loading"><div class="crcm-admin-spinner"></div><p>Loading calendar...</p></div>');
                },
                success: function(response) {
                    if (response.success) {
                        CRCMAdmin.renderCalendarEvents(response.data);
                    } else {
                        $('#crcm-calendar').html('<p>Error loading calendar data.</p>');
                    }
                },
                error: function() {
                    $('#crcm-calendar').html('<p>Error loading calendar data.</p>');
                }
            });
        },

        renderCalendarEvents: function(events) {
            // This would typically integrate with FullCalendar or similar
            var html = '<div class="crcm-calendar-events">';

            if (events && events.length > 0) {
                events.forEach(function(event) {
                    html += '<div class="crcm-calendar-event crcm-booking-' + event.status + '">';
                    html += '<strong>' + event.title + '</strong><br>';
                    html += event.start + ' - ' + event.end + '<br>';
                    html += '<small>' + event.vehicle + '</small>';
                    html += '</div>';
                });
            } else {
                html += '<p>No events found for this period.</p>';
            }

            html += '</div>';
            $('#crcm-calendar').html(html);
        },

        // Initialize booking actions
        initBookingActions: function() {
            // Update booking status
            $(document).on('change', '#crcm_booking_status', function() {
                var bookingId = $('#post_ID').val();
                var newStatus = $(this).val();

                if (bookingId && newStatus) {
                    CRCMAdmin.updateBookingStatus(bookingId, newStatus);
                }
            });

            // Send email to customer
            $(document).on('click', '#crcm-send-email', function() {
                var bookingId = $('#post_ID').val();
                if (bookingId) {
                    CRCMAdmin.sendCustomerEmail(bookingId);
                }
            });

            // Generate contract
            $(document).on('click', '#crcm-generate-contract', function() {
                var bookingId = $('#post_ID').val();
                if (bookingId) {
                    CRCMAdmin.generateContract(bookingId);
                }
            });

            // Process refund
            $(document).on('click', '#crcm-process-refund', function() {
                var bookingId = $('#post_ID').val();
                if (bookingId) {
                    CRCMAdmin.showRefundModal(bookingId);
                }
            });
        },

        updateBookingStatus: function(bookingId, status) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crcm_update_booking_status',
                    booking_id: bookingId,
                    status: status,
                    nonce: crcm_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CRCMAdmin.showNotice('success', response.data.message);
                    } else {
                        CRCMAdmin.showNotice('error', response.data || 'Status update failed.');
                    }
                },
                error: function() {
                    CRCMAdmin.showNotice('error', 'Status update failed.');
                }
            });
        },

        sendCustomerEmail: function(bookingId) {
            // Show email template selection modal
            var modalHtml = '<div class="crcm-admin-modal">' +
                '<div class="crcm-admin-modal-content">' +
                '<h3>Send Email to Customer</h3>' +
                '<p>Select email template:</p>' +
                '<select id="email-template">' +
                '<option value="booking-confirmation">Booking Confirmation</option>' +
                '<option value="pickup-reminder">Pickup Reminder</option>' +
                '<option value="custom">Custom Message</option>' +
                '</select>' +
                '<div id="custom-message" style="display:none; margin-top:10px;">' +
                '<textarea placeholder="Custom message..." rows="5" style="width:100%;"></textarea>' +
                '</div>' +
                '<div class="modal-actions">' +
                '<button class="button button-primary" onclick="CRCMAdmin.sendEmail(' + bookingId + ')">Send Email</button>' +
                '<button class="button" onclick="CRCMAdmin.closeModal()">Cancel</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            $('body').append(modalHtml);

            $('#email-template').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#custom-message').show();
                } else {
                    $('#custom-message').hide();
                }
            });
        },

        generateContract: function(bookingId) {
            window.open(ajaxurl + '?action=crcm_generate_contract&booking_id=' + bookingId + '&nonce=' + crcm_admin.nonce, '_blank');
        },

        showRefundModal: function(bookingId) {
            var modalHtml = '<div class="crcm-admin-modal">' +
                '<div class="crcm-admin-modal-content">' +
                '<h3>Process Refund</h3>' +
                '<p><label>Refund Amount:</label><input type="number" id="refund-amount" step="0.01" /></p>' +
                '<p><label>Reason:</label><textarea id="refund-reason" rows="3"></textarea></p>' +
                '<div class="modal-actions">' +
                '<button class="button button-primary" onclick="CRCMAdmin.processRefund(' + bookingId + ')">Process Refund</button>' +
                '<button class="button" onclick="CRCMAdmin.closeModal()">Cancel</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            $('body').append(modalHtml);
        },

        processRefund: function(bookingId) {
            var amount = $('#refund-amount').val();
            var reason = $('#refund-reason').val();

            if (!amount || amount <= 0) {
                alert('Please enter a valid refund amount.');
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crcm_process_refund',
                    booking_id: bookingId,
                    refund_amount: amount,
                    refund_reason: reason,
                    nonce: crcm_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CRCMAdmin.showNotice('success', response.data.message);
                        CRCMAdmin.closeModal();
                        location.reload(); // Refresh to show updated payment data
                    } else {
                        CRCMAdmin.showNotice('error', response.data || 'Refund failed.');
                    }
                },
                error: function() {
                    CRCMAdmin.showNotice('error', 'Refund failed.');
                }
            });
        },

        closeModal: function() {
            $('.crcm-admin-modal').remove();
        },

        // Initialize dashboard refresh
        initDashboardRefresh: function() {
            // Auto-refresh dashboard data every 5 minutes
            if ($('.crcm-dashboard').length) {
                setInterval(function() {
                    CRCMAdmin.refreshDashboardStats();
                }, 300000); // 5 minutes
            }

            // Manual refresh button
            $(document).on('click', '.crcm-refresh-dashboard', function() {
                CRCMAdmin.refreshDashboardStats();
            });
        },

        refreshDashboardStats: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'crcm_get_dashboard_stats',
                    nonce: crcm_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        CRCMAdmin.updateDashboardStats(response.data);
                    }
                }
            });
        },

        updateDashboardStats: function(stats) {
            // Update stat cards with new data
            Object.keys(stats).forEach(function(key) {
                var element = $('.crcm-stat-' + key);
                if (element.length) {
                    element.find('h3').text(stats[key]);
                }
            });
        },

        // Initialize tooltips
        initTooltips: function() {
            $(document).on('mouseenter', '[data-tooltip]', function() {
                var tooltip = $(this).data('tooltip');
                var tooltipElement = $('<div class="crcm-tooltip">' + tooltip + '</div>');
                $('body').append(tooltipElement);

                var rect = this.getBoundingClientRect();
                tooltipElement.css({
                    position: 'absolute',
                    top: rect.top - tooltipElement.outerHeight() - 5,
                    left: rect.left + (rect.width / 2) - (tooltipElement.outerWidth() / 2),
                    zIndex: 10000
                });
            }).on('mouseleave', function() {
                $('.crcm-tooltip').remove();
            });
        },

        // Utility functions
        showNotice: function(type, message) {
            var noticeClass = 'notice-' + type;
            var noticeHtml = '<div class="notice ' + noticeClass + ' is-dismissible crcm-admin-notice">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
                '</div>';

            $('.wrap h1').after(noticeHtml);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('.crcm-admin-notice').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        formatPrice: function(amount) {
            return crcm_admin.currency_symbol + parseFloat(amount).toFixed(2);
        },

        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        CRCMAdmin.init();
    });

    // Accessibility improvements
    $(document).ready(function() {
        // Add ARIA labels
        $('.crcm-remove-image').attr('aria-label', 'Remove image');
        $('.remove-location').attr('aria-label', 'Remove location');

        // Keyboard navigation for tabs
        $('.nav-tab').on('keydown', function(e) {
            var tabs = $('.nav-tab');
            var currentIndex = tabs.index(this);

            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                var nextIndex = e.key === 'ArrowRight' ? 
                    (currentIndex + 1) % tabs.length : 
                    (currentIndex - 1 + tabs.length) % tabs.length;

                tabs.eq(nextIndex).focus().click();
            }
        });
    });

    // Handle modal dismiss
    $(document).on('click', '.notice-dismiss', function() {
        $(this).parent().fadeOut(function() {
            $(this).remove();
        });
    });

})(jQuery);

// Modal styles (injected via JavaScript for better encapsulation)
jQuery(document).ready(function($) {
    var modalStyles = `
        <style>
        .crcm-admin-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .crcm-admin-modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .crcm-admin-modal-content h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .crcm-admin-modal-content label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .crcm-admin-modal-content input,
        .crcm-admin-modal-content select,
        .crcm-admin-modal-content textarea {
            width: 100%;
            padding: 8px 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-actions {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .modal-actions .button {
            margin-left: 10px;
        }
        </style>
    `;

    $('head').append(modalStyles);
});
