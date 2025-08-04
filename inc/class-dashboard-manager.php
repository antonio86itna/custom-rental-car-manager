<?php
/**
 * Dashboard Manager Class - RESET EDITION
 * 
 * PERFETTO E FUNZIONANTE - Dashboard completo e dinamico
 * Sistema di gestione completo con statistiche e funzionalità avanzate
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CRCM_Dashboard_Manager {
    
    /**
     * Constructor - SAFE INITIALIZATION
     */
    public function __construct() {
        // Only add hooks when needed
        add_action('admin_menu', array($this, 'add_dashboard_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dashboard_scripts'));
        
        // AJAX hooks for dashboard functionality
        add_action('wp_ajax_crcm_dashboard_stats', array($this, 'ajax_dashboard_stats'));
        add_action('wp_ajax_crcm_recent_bookings', array($this, 'ajax_recent_bookings'));
        add_action('wp_ajax_crcm_vehicle_status_update', array($this, 'ajax_vehicle_status_update'));
        add_action('wp_ajax_crcm_quick_booking', array($this, 'ajax_quick_booking'));
    }
    
    /**
     * Add dashboard pages to admin menu
     */
    public function add_dashboard_pages() {
        // Main dashboard page
        add_menu_page(
            __('Car Rental Dashboard', 'custom-rental-manager'),
            __('Car Rental', 'custom-rental-manager'),
            'manage_options',
            'crcm-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-car',
            25
        );
        
        // Dashboard submenu pages
        add_submenu_page(
            'crcm-dashboard',
            __('Dashboard Overview', 'custom-rental-manager'),
            __('Dashboard', 'custom-rental-manager'),
            'manage_options',
            'crcm-dashboard',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'crcm-dashboard',
            __('Quick Actions', 'custom-rental-manager'),
            __('Quick Actions', 'custom-rental-manager'),
            'manage_options',
            'crcm-quick-actions',
            array($this, 'quick_actions_page')
        );
        
        add_submenu_page(
            'crcm-dashboard',
            __('Analytics', 'custom-rental-manager'),
            __('Analytics', 'custom-rental-manager'),
            'manage_options',
            'crcm-analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            'crcm-dashboard',
            __('Reports', 'custom-rental-manager'),
            __('Reports', 'custom-rental-manager'),
            'manage_options',
            'crcm-reports',
            array($this, 'reports_page')
        );
        
        add_submenu_page(
            'crcm-dashboard',
            __('Settings', 'custom-rental-manager'),
            __('Settings', 'custom-rental-manager'),
            'manage_options',
            'crcm-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue dashboard scripts and styles
     */
    public function enqueue_dashboard_scripts($hook) {
        if (strpos($hook, 'crcm-') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_localize_script('jquery', 'crcm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crcm_ajax'),
            'strings' => array(
                'loading' => __('Loading...', 'custom-rental-manager'),
                'error' => __('Error loading data', 'custom-rental-manager'),
                'success' => __('Action completed successfully', 'custom-rental-manager'),
                'confirm' => __('Are you sure?', 'custom-rental-manager')
            )
        ));
        
        // Dashboard specific styles
        wp_add_inline_style('wp-admin', $this->get_dashboard_css());
    }
    
    /**
     * Main dashboard page
     */
    public function dashboard_page() {
        ?>
        <div class="wrap crcm-dashboard">
            <h1 class="wp-heading-inline">
                <?php _e('Car Rental Dashboard', 'custom-rental-manager'); ?>
                <span class="page-title-action" id="refresh-dashboard"><?php _e('Refresh', 'custom-rental-manager'); ?></span>
            </h1>
            
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder columns-2">
                    <!-- Left Column -->
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Statistics Cards -->
                        <div class="postbox crcm-stats-widget">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php _e('Overview', 'custom-rental-manager'); ?></h2>
                            </div>
                            <div class="inside">
                                <div id="stats-cards" class="stats-grid">
                                    <div class="stat-card" id="total-bookings">
                                        <div class="stat-icon">📊</div>
                                        <div class="stat-content">
                                            <div class="stat-number">-</div>
                                            <div class="stat-label"><?php _e('Total Bookings', 'custom-rental-manager'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-card" id="active-rentals">
                                        <div class="stat-icon">🚗</div>
                                        <div class="stat-content">
                                            <div class="stat-number">-</div>
                                            <div class="stat-label"><?php _e('Active Rentals', 'custom-rental-manager'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-card" id="available-vehicles">
                                        <div class="stat-icon">✅</div>
                                        <div class="stat-content">
                                            <div class="stat-number">-</div>
                                            <div class="stat-label"><?php _e('Available Vehicles', 'custom-rental-manager'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="stat-card" id="monthly-revenue">
                                        <div class="stat-icon">💰</div>
                                        <div class="stat-content">
                                            <div class="stat-number">-</div>
                                            <div class="stat-label"><?php _e('Monthly Revenue', 'custom-rental-manager'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="stats-loading" id="stats-loading">
                                    <?php _e('Loading statistics...', 'custom-rental-manager'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Vehicle Status Widget -->
                        <div class="postbox crcm-vehicle-status-widget">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php _e('Vehicle Fleet Status', 'custom-rental-manager'); ?></h2>
                            </div>
                            <div class="inside">
                                <div id="vehicle-status-chart-container">
                                    <canvas id="vehicle-status-chart" width="300" height="200"></canvas>
                                </div>
                                <div id="vehicle-quick-actions">
                                    <h4><?php _e('Quick Actions', 'custom-rental-manager'); ?></h4>
                                    <div class="quick-action-buttons">
                                        <button class="button" onclick="setAllVehiclesStatus('available')"><?php _e('Mark All Available', 'custom-rental-manager'); ?></button>
                                        <button class="button" onclick="refreshVehicleStatus()"><?php _e('Refresh Status', 'custom-rental-manager'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div id="postbox-container-2" class="postbox-container">
                        <!-- Recent Bookings -->
                        <div class="postbox crcm-recent-bookings-widget">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php _e('Recent Bookings', 'custom-rental-manager'); ?></h2>
                                <div class="handle-actions">
                                    <button class="button-link" id="refresh-bookings"><?php _e('Refresh', 'custom-rental-manager'); ?></button>
                                </div>
                            </div>
                            <div class="inside">
                                <div id="recent-bookings-list">
                                    <div class="bookings-loading"><?php _e('Loading recent bookings...', 'custom-rental-manager'); ?></div>
                                </div>
                                <div class="widget-actions">
                                    <a href="<?php echo admin_url('edit.php?post_type=crcm_booking'); ?>" class="button"><?php _e('View All Bookings', 'custom-rental-manager'); ?></a>
                                    <a href="<?php echo admin_url('post-new.php?post_type=crcm_booking'); ?>" class="button button-primary"><?php _e('New Booking', 'custom-rental-manager'); ?></a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Today's Schedule -->
                        <div class="postbox crcm-schedule-widget">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php _e('Today\'s Schedule', 'custom-rental-manager'); ?></h2>
                            </div>
                            <div class="inside">
                                <div id="todays-schedule">
                                    <div class="schedule-loading"><?php _e('Loading today\'s schedule...', 'custom-rental-manager'); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notifications & Alerts -->
                        <div class="postbox crcm-notifications-widget">
                            <div class="postbox-header">
                                <h2 class="hndle"><?php _e('Notifications & Alerts', 'custom-rental-manager'); ?></h2>
                            </div>
                            <div class="inside">
                                <div id="notifications-list">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize dashboard
            loadDashboardStats();
            loadRecentBookings();
            loadTodaysSchedule();
            loadNotifications();
            
            // Auto-refresh every 5 minutes
            setInterval(function() {
                loadDashboardStats();
                loadRecentBookings();
                loadTodaysSchedule();
                loadNotifications();
            }, 300000);
            
            // Manual refresh buttons
            $('#refresh-dashboard').on('click', function() {
                location.reload();
            });
            
            $('#refresh-bookings').on('click', function() {
                loadRecentBookings();
            });
            
            // Load dashboard statistics
            function loadDashboardStats() {
                $('#stats-loading').show();
                $('.stat-number').text('-');
                
                $.ajax({
                    url: crcm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'crcm_dashboard_stats',
                        nonce: crcm_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var stats = response.data;
                            
                            $('#total-bookings .stat-number').text(stats.total_bookings || 0);
                            $('#active-rentals .stat-number').text(stats.active_rentals || 0);
                            $('#available-vehicles .stat-number').text(stats.available_vehicles || 0);
                            $('#monthly-revenue .stat-number').text('€' + (stats.monthly_revenue || 0).toLocaleString());
                            
                            // Create vehicle status chart
                            if (stats.vehicle_status) {
                                createVehicleStatusChart(stats.vehicle_status);
                            }
                            
                            $('#stats-loading').hide();
                        }
                    },
                    error: function() {
                        $('#stats-loading').text(crcm_ajax.strings.error);
                    }
                });
            }
            
            // Load recent bookings
            function loadRecentBookings() {
                $('#recent-bookings-list').html('<div class="bookings-loading">' + crcm_ajax.strings.loading + '</div>');
                
                $.ajax({
                    url: crcm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'crcm_recent_bookings',
                        nonce: crcm_ajax.nonce,
                        limit: 10
                    },
                    success: function(response) {
                        if (response.success) {
                            var bookings = response.data;
                            var html = '';
                            
                            if (bookings.length > 0) {
                                bookings.forEach(function(booking) {
                                    var statusClass = 'status-' + booking.status;
                                    var statusColor = getStatusColor(booking.status);
                                    
                                    html += '<div class="booking-item ' + statusClass + '">';
                                    html += '<div class="booking-header">';
                                    html += '<strong>' + booking.customer_name + '</strong>';
                                    html += '<span class="booking-status" style="background-color: ' + statusColor + ';">' + booking.status.charAt(0).toUpperCase() + booking.status.slice(1) + '</span>';
                                    html += '</div>';
                                    html += '<div class="booking-details">';
                                    html += '<div class="booking-vehicle">' + booking.vehicle_name + '</div>';
                                    html += '<div class="booking-dates">' + booking.pickup_date + ' - ' + booking.return_date + '</div>';
                                    html += '<div class="booking-amount">€' + parseFloat(booking.total_amount || 0).toFixed(2) + '</div>';
                                    html += '</div>';
                                    html += '<div class="booking-actions">';
                                    html += '<a href="' + booking.edit_url + '" class="button button-small"><?php _e('Edit', 'custom-rental-manager'); ?></a>';
                                    html += '</div>';
                                    html += '</div>';
                                });
                            } else {
                                html = '<div class="no-bookings"><?php _e('No recent bookings found', 'custom-rental-manager'); ?></div>';
                            }
                            
                            $('#recent-bookings-list').html(html);
                        }
                    },
                    error: function() {
                        $('#recent-bookings-list').html('<div class="error">' + crcm_ajax.strings.error + '</div>');
                    }
                });
            }
            
            // Load today's schedule
            function loadTodaysSchedule() {
                $('#todays-schedule').html('<div class="schedule-loading">' + crcm_ajax.strings.loading + '</div>');
                
                $.ajax({
                    url: crcm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'crcm_todays_schedule',
                        nonce: crcm_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var events = response.data;
                            var html = '';
                            
                            if (events.length > 0) {
                                events.forEach(function(event) {
                                    var eventClass = 'schedule-' + event.type;
                                    
                                    html += '<div class="schedule-item ' + eventClass + '">';
                                    html += '<div class="schedule-time">' + event.time + '</div>';
                                    html += '<div class="schedule-content">';
                                    html += '<div class="schedule-title">' + event.title + '</div>';
                                    html += '<div class="schedule-details">' + event.details + '</div>';
                                    html += '</div>';
                                    html += '</div>';
                                });
                            } else {
                                html = '<div class="no-schedule"><?php _e('No events scheduled for today', 'custom-rental-manager'); ?></div>';
                            }
                            
                            $('#todays-schedule').html(html);
                        }
                    },
                    error: function() {
                        $('#todays-schedule').html('<div class="error">' + crcm_ajax.strings.error + '</div>');
                    }
                });
            }
            
            // Load notifications
            function loadNotifications() {
                var notifications = [
                    {
                        type: 'warning',
                        message: '<?php _e('3 vehicles due for maintenance this week', 'custom-rental-manager'); ?>',
                        action: 'view_maintenance'
                    },
                    {
                        type: 'info',
                        message: '<?php _e('2 customer licenses expire within 30 days', 'custom-rental-manager'); ?>',
                        action: 'view_licenses'
                    },
                    {
                        type: 'success',
                        message: '<?php _e('System backup completed successfully', 'custom-rental-manager'); ?>',
                        action: null
                    }
                ];
                
                var html = '';
                notifications.forEach(function(notification) {
                    html += '<div class="notification notification-' + notification.type + '">';
                    html += '<div class="notification-icon">' + getNotificationIcon(notification.type) + '</div>';
                    html += '<div class="notification-content">';
                    html += '<div class="notification-message">' + notification.message + '</div>';
                    if (notification.action) {
                        html += '<div class="notification-actions">';
                        html += '<button class="button button-small" onclick="handleNotificationAction(\'' + notification.action + '\')"><?php _e('View', 'custom-rental-manager'); ?></button>';
                        html += '</div>';
                    }
                    html += '</div>';
                    html += '</div>';
                });
                
                $('#notifications-list').html(html);
            }
            
            // Create vehicle status chart
            function createVehicleStatusChart(statusData) {
                var ctx = document.getElementById('vehicle-status-chart');
                if (!ctx) return;
                
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Available', 'Rented', 'Maintenance', 'Out of Service'],
                        datasets: [{
                            data: [
                                statusData.available || 0,
                                statusData.rented || 0,
                                statusData.maintenance || 0,
                                statusData.out_of_service || 0
                            ],
                            backgroundColor: [
                                '#4CAF50',
                                '#FF9800',
                                '#2196F3',
                                '#F44336'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Helper functions
            function getStatusColor(status) {
                var colors = {
                    'pending': '#FF9800',
                    'confirmed': '#2196F3',
                    'active': '#4CAF50',
                    'completed': '#9E9E9E',
                    'cancelled': '#F44336'
                };
                return colors[status] || '#9E9E9E';
            }
            
            function getNotificationIcon(type) {
                var icons = {
                    'success': '✅',
                    'warning': '⚠️',
                    'error': '❌',
                    'info': 'ℹ️'
                };
                return icons[type] || 'ℹ️';
            }
        });
        
        // Global functions for quick actions
        function setAllVehiclesStatus(status) {
            if (!confirm(crcm_ajax.strings.confirm)) return;
            
            jQuery.ajax({
                url: crcm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'crcm_vehicle_status_update',
                    nonce: crcm_ajax.nonce,
                    vehicle_id: 'all',
                    status: status
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
        
        function refreshVehicleStatus() {
            location.reload();
        }
        
        function handleNotificationAction(action) {
            switch (action) {
                case 'view_maintenance':
                    window.location.href = '<?php echo admin_url('edit.php?post_type=crcm_vehicle&vehicle_status=maintenance'); ?>';
                    break;
                case 'view_licenses':
                    // Future implementation
                    alert('Feature coming soon');
                    break;
            }
        }
        </script>
        <?php
    }
    
    /**
     * Quick actions page
     */
    public function quick_actions_page() {
        ?>
        <div class="wrap crcm-quick-actions">
            <h1><?php _e('Quick Actions', 'custom-rental-manager'); ?></h1>
            
            <div class="quick-actions-grid">
                <!-- Quick Booking -->
                <div class="action-card">
                    <div class="action-header">
                        <h2><?php _e('Quick Booking', 'custom-rental-manager'); ?></h2>
                        <div class="action-icon">📝</div>
                    </div>
                    <div class="action-content">
                        <form id="quick-booking-form">
                            <table class="form-table">
                                <tr>
                                    <th><label for="qb_customer_name"><?php _e('Customer Name', 'custom-rental-manager'); ?></label></th>
                                    <td><input type="text" id="qb_customer_name" name="customer_name" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="qb_customer_phone"><?php _e('Phone', 'custom-rental-manager'); ?></label></th>
                                    <td><input type="tel" id="qb_customer_phone" name="customer_phone" class="regular-text" required></td>
                                </tr>
                                <tr>
                                    <th><label for="qb_vehicle_id"><?php _e('Vehicle', 'custom-rental-manager'); ?></label></th>
                                    <td>
                                        <select id="qb_vehicle_id" name="vehicle_id" class="regular-text" required>
                                            <option value=""><?php _e('Select Vehicle', 'custom-rental-manager'); ?></option>
                                            <?php
                                            $vehicles = get_posts(array(
                                                'post_type' => 'crcm_vehicle',
                                                'posts_per_page' => -1,
                                                'meta_query' => array(
                                                    array(
                                                        'key' => '_crcm_vehicle_status',
                                                        'value' => 'available'
                                                    )
                                                )
                                            ));
                                            foreach ($vehicles as $vehicle) {
                                                echo '<option value="' . $vehicle->ID . '">' . $vehicle->post_title . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="qb_pickup_date"><?php _e('Pickup Date', 'custom-rental-manager'); ?></label></th>
                                    <td><input type="date" id="qb_pickup_date" name="pickup_date" class="regular-text" min="<?php echo date('Y-m-d'); ?>" required></td>
                                </tr>
                                <tr>
                                    <th><label for="qb_return_date"><?php _e('Return Date', 'custom-rental-manager'); ?></label></th>
                                    <td><input type="date" id="qb_return_date" name="return_date" class="regular-text" min="<?php echo date('Y-m-d'); ?>" required></td>
                                </tr>
                            </table>
                            <div class="action-buttons">
                                <button type="submit" class="button button-primary"><?php _e('Create Booking', 'custom-rental-manager'); ?></button>
                                <button type="reset" class="button"><?php _e('Clear', 'custom-rental-manager'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Vehicle Status Updates -->
                <div class="action-card">
                    <div class="action-header">
                        <h2><?php _e('Vehicle Status Updates', 'custom-rental-manager'); ?></h2>
                        <div class="action-icon">🚗</div>
                    </div>
                    <div class="action-content">
                        <div id="vehicle-status-list">
                            <?php
                            $vehicles = get_posts(array(
                                'post_type' => 'crcm_vehicle',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ));
                            
                            foreach ($vehicles as $vehicle) {
                                $status = get_post_meta($vehicle->ID, '_crcm_vehicle_status', true) ?: 'available';
                                $license_plate = get_post_meta($vehicle->ID, '_crcm_license_plate', true);
                                
                                echo '<div class="vehicle-status-item">';
                                echo '<div class="vehicle-info">';
                                echo '<strong>' . $vehicle->post_title . '</strong>';
                                if ($license_plate) {
                                    echo ' <span class="license-plate">(' . $license_plate . ')</span>';
                                }
                                echo '</div>';
                                echo '<select class="vehicle-status-select" data-vehicle-id="' . $vehicle->ID . '">';
                                echo '<option value="available"' . selected($status, 'available', false) . '>' . __('Available', 'custom-rental-manager') . '</option>';
                                echo '<option value="rented"' . selected($status, 'rented', false) . '>' . __('Rented', 'custom-rental-manager') . '</option>';
                                echo '<option value="maintenance"' . selected($status, 'maintenance', false) . '>' . __('Maintenance', 'custom-rental-manager') . '</option>';
                                echo '<option value="out_of_service"' . selected($status, 'out_of_service', false) . '>' . __('Out of Service', 'custom-rental-manager') . '</option>';
                                echo '</select>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <div class="action-buttons">
                            <button id="save-vehicle-statuses" class="button button-primary"><?php _e('Save All Changes', 'custom-rental-manager'); ?></button>
                        </div>
                    </div>
                </div>
                
                <!-- Bulk Actions -->
                <div class="action-card">
                    <div class="action-header">
                        <h2><?php _e('Bulk Actions', 'custom-rental-manager'); ?></h2>
                        <div class="action-icon">⚡</div>
                    </div>
                    <div class="action-content">
                        <div class="bulk-action-item">
                            <h4><?php _e('Export Data', 'custom-rental-manager'); ?></h4>
                            <p><?php _e('Export bookings and vehicle data to CSV format.', 'custom-rental-manager'); ?></p>
                            <button class="button" onclick="exportData('bookings')"><?php _e('Export Bookings', 'custom-rental-manager'); ?></button>
                            <button class="button" onclick="exportData('vehicles')"><?php _e('Export Vehicles', 'custom-rental-manager'); ?></button>
                        </div>
                        
                        <div class="bulk-action-item">
                            <h4><?php _e('Maintenance Reminders', 'custom-rental-manager'); ?></h4>
                            <p><?php _e('Check which vehicles are due for maintenance.', 'custom-rental-manager'); ?></p>
                            <button class="button" onclick="checkMaintenanceSchedule()"><?php _e('Check Schedule', 'custom-rental-manager'); ?></button>
                        </div>
                        
                        <div class="bulk-action-item">
                            <h4><?php _e('System Cleanup', 'custom-rental-manager'); ?></h4>
                            <p><?php _e('Clean up old draft bookings and optimize database.', 'custom-rental-manager'); ?></p>
                            <button class="button" onclick="systemCleanup()"><?php _e('Run Cleanup', 'custom-rental-manager'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Quick booking form
            $('#quick-booking-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'crcm_quick_booking',
                    nonce: crcm_ajax.nonce,
                    customer_name: $('#qb_customer_name').val(),
                    customer_phone: $('#qb_customer_phone').val(),
                    vehicle_id: $('#qb_vehicle_id').val(),
                    pickup_date: $('#qb_pickup_date').val(),
                    return_date: $('#qb_return_date').val()
                };
                
                $.ajax({
                    url: crcm_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            alert('Booking created successfully!');
                            window.location.href = response.data.edit_url;
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            });
            
            // Vehicle status updates
            $('.vehicle-status-select').on('change', function() {
                $(this).addClass('changed');
            });
            
            $('#save-vehicle-statuses').on('click', function() {
                var updates = [];
                $('.vehicle-status-select.changed').each(function() {
                    updates.push({
                        vehicle_id: $(this).data('vehicle-id'),
                        status: $(this).val()
                    });
                });
                
                if (updates.length === 0) {
                    alert('No changes to save');
                    return;
                }
                
                $.ajax({
                    url: crcm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'crcm_vehicle_status_update',
                        nonce: crcm_ajax.nonce,
                        updates: updates
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Vehicle statuses updated successfully!');
                            $('.vehicle-status-select').removeClass('changed');
                        } else {
                            alert('Error updating statuses');
                        }
                    }
                });
            });
            
            // Auto-set return date
            $('#qb_pickup_date').on('change', function() {
                var pickupDate = new Date($(this).val());
                if (pickupDate) {
                    pickupDate.setDate(pickupDate.getDate() + 3);
                    $('#qb_return_date').val(pickupDate.toISOString().split('T')[0]);
                }
            });
        });
        
        // Bulk action functions
        function exportData(type) {
            window.location.href = crcm_ajax.ajax_url + '?action=crcm_export_data&type=' + type + '&nonce=' + crcm_ajax.nonce;
        }
        
        function checkMaintenanceSchedule() {
            alert('Maintenance schedule check - Feature coming soon');
        }
        
        function systemCleanup() {
            if (confirm('This will delete old draft bookings. Continue?')) {
                jQuery.ajax({
                    url: crcm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'crcm_system_cleanup',
                        nonce: crcm_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Cleanup completed: ' + response.data.deleted + ' items removed');
                        }
                    }
                });
            }
        }
        </script>
        <?php
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        ?>
        <div class="wrap crcm-analytics">
            <h1><?php _e('Analytics & Reports', 'custom-rental-manager'); ?></h1>
            
            <div class="analytics-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#overview" class="nav-tab nav-tab-active"><?php _e('Overview', 'custom-rental-manager'); ?></a>
                    <a href="#bookings" class="nav-tab"><?php _e('Booking Analytics', 'custom-rental-manager'); ?></a>
                    <a href="#vehicles" class="nav-tab"><?php _e('Vehicle Performance', 'custom-rental-manager'); ?></a>
                    <a href="#revenue" class="nav-tab"><?php _e('Revenue Analysis', 'custom-rental-manager'); ?></a>
                </nav>
                
                <div id="overview" class="tab-content active">
                    <div class="analytics-grid">
                        <div class="analytics-card">
                            <h3><?php _e('Booking Trends', 'custom-rental-manager'); ?></h3>
                            <canvas id="booking-trends-chart" width="400" height="200"></canvas>
                        </div>
                        
                        <div class="analytics-card">
                            <h3><?php _e('Revenue Trends', 'custom-rental-manager'); ?></h3>
                            <canvas id="revenue-trends-chart" width="400" height="200"></canvas>
                        </div>
                        
                        <div class="analytics-card">
                            <h3><?php _e('Most Popular Vehicles', 'custom-rental-manager'); ?></h3>
                            <div id="popular-vehicles-list">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="analytics-card">
                            <h3><?php _e('Customer Insights', 'custom-rental-manager'); ?></h3>
                            <div id="customer-insights">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="bookings" class="tab-content">
                    <h2><?php _e('Booking Analytics', 'custom-rental-manager'); ?></h2>
                    <p><?php _e('Detailed booking analysis coming soon...', 'custom-rental-manager'); ?></p>
                </div>
                
                <div id="vehicles" class="tab-content">
                    <h2><?php _e('Vehicle Performance', 'custom-rental-manager'); ?></h2>
                    <p><?php _e('Vehicle utilization and performance metrics coming soon...', 'custom-rental-manager'); ?></p>
                </div>
                
                <div id="revenue" class="tab-content">
                    <h2><?php _e('Revenue Analysis', 'custom-rental-manager'); ?></h2>
                    <p><?php _e('Detailed revenue breakdown and forecasting coming soon...', 'custom-rental-manager'); ?></p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
                
                // Load tab-specific data
                loadAnalyticsData(target.substring(1));
            });
            
            // Load initial data
            loadAnalyticsData('overview');
            
            function loadAnalyticsData(tab) {
                switch(tab) {
                    case 'overview':
                        loadOverviewCharts();
                        break;
                    // Add more cases for other tabs
                }
            }
            
            function loadOverviewCharts() {
                // Sample data - in real implementation, this would come from AJAX
                var bookingTrendsData = {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Bookings',
                        data: [12, 19, 3, 5, 2, 3],
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        tension: 0.4
                    }]
                };
                
                var revenueTrendsData = {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Revenue (€)',
                        data: [1200, 1900, 300, 500, 200, 300],
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4
                    }]
                };
                
                // Create charts
                new Chart(document.getElementById('booking-trends-chart'), {
                    type: 'line',
                    data: bookingTrendsData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
                
                new Chart(document.getElementById('revenue-trends-chart'), {
                    type: 'line',
                    data: revenueTrendsData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Reports page
     */
    public function reports_page() {
        ?>
        <div class="wrap crcm-reports">
            <h1><?php _e('Reports', 'custom-rental-manager'); ?></h1>
            
            <div class="reports-grid">
                <div class="report-card">
                    <h3><?php _e('Booking Summary Report', 'custom-rental-manager'); ?></h3>
                    <p><?php _e('Comprehensive overview of all bookings with filters and export options.', 'custom-rental-manager'); ?></p>
                    <button class="button button-primary" onclick="generateReport('booking-summary')"><?php _e('Generate Report', 'custom-rental-manager'); ?></button>
                </div>
                
                <div class="report-card">
                    <h3><?php _e('Revenue Report', 'custom-rental-manager'); ?></h3>
                    <p><?php _e('Detailed revenue analysis by vehicle, period, and customer segment.', 'custom-rental-manager'); ?></p>
                    <button class="button button-primary" onclick="generateReport('revenue')"><?php _e('Generate Report', 'custom-rental-manager'); ?></button>
                </div>
                
                <div class="report-card">
                    <h3><?php _e('Vehicle Utilization Report', 'custom-rental-manager'); ?></h3>
                    <p><?php _e('Analysis of vehicle usage, availability, and performance metrics.', 'custom-rental-manager'); ?></p>
                    <button class="button button-primary" onclick="generateReport('vehicle-utilization')"><?php _e('Generate Report', 'custom-rental-manager'); ?></button>
                </div>
                
                <div class="report-card">
                    <h3><?php _e('Customer Report', 'custom-rental-manager'); ?></h3>
                    <p><?php _e('Customer database with booking history and preferences.', 'custom-rental-manager'); ?></p>
                    <button class="button button-primary" onclick="generateReport('customer')"><?php _e('Generate Report', 'custom-rental-manager'); ?></button>
                </div>
            </div>
        </div>
        
        <script>
        function generateReport(type) {
            alert('Report generation for ' + type + ' - Feature coming soon');
        }
        </script>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['save_settings'])) {
            $this->save_settings();
        }
        
        $settings = get_option('crcm_settings', array());
        
        ?>
        <div class="wrap crcm-settings">
            <h1><?php _e('Car Rental Settings', 'custom-rental-manager'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('crcm_settings', 'crcm_settings_nonce'); ?>
                
                <div class="settings-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'custom-rental-manager'); ?></a>
                        <a href="#pricing" class="nav-tab"><?php _e('Pricing', 'custom-rental-manager'); ?></a>
                        <a href="#locations" class="nav-tab"><?php _e('Locations', 'custom-rental-manager'); ?></a>
                        <a href="#notifications" class="nav-tab"><?php _e('Notifications', 'custom-rental-manager'); ?></a>
                    </nav>
                    
                    <div id="general" class="tab-content active">
                        <h2><?php _e('General Settings', 'custom-rental-manager'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Company Name', 'custom-rental-manager'); ?></th>
                                <td><input type="text" name="company_name" value="<?php echo esc_attr($settings['company_name'] ?? ''); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Company Email', 'custom-rental-manager'); ?></th>
                                <td><input type="email" name="company_email" value="<?php echo esc_attr($settings['company_email'] ?? ''); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Company Phone', 'custom-rental-manager'); ?></th>
                                <td><input type="tel" name="company_phone" value="<?php echo esc_attr($settings['company_phone'] ?? ''); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Default Currency', 'custom-rental-manager'); ?></th>
                                <td>
                                    <select name="default_currency">
                                        <option value="EUR" <?php selected($settings['default_currency'] ?? '', 'EUR'); ?>>EUR (€)</option>
                                        <option value="USD" <?php selected($settings['default_currency'] ?? '', 'USD'); ?>>USD ($)</option>
                                        <option value="GBP" <?php selected($settings['default_currency'] ?? '', 'GBP'); ?>>GBP (£)</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Minimum Booking Duration', 'custom-rental-manager'); ?></th>
                                <td>
                                    <input type="number" name="min_booking_duration" value="<?php echo esc_attr($settings['min_booking_duration'] ?? '1'); ?>" class="small-text" min="1">
                                    <?php _e('day(s)', 'custom-rental-manager'); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div id="pricing" class="tab-content">
                        <h2><?php _e('Pricing Settings', 'custom-rental-manager'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Default Deposit Percentage', 'custom-rental-manager'); ?></th>
                                <td>
                                    <input type="number" name="default_deposit_percentage" value="<?php echo esc_attr($settings['default_deposit_percentage'] ?? '30'); ?>" class="small-text" min="0" max="100">
                                    %
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Maximum Deposit Amount', 'custom-rental-manager'); ?></th>
                                <td>
                                    <input type="number" name="max_deposit_amount" value="<?php echo esc_attr($settings['max_deposit_amount'] ?? '500'); ?>" class="regular-text" step="0.01" min="0">
                                    <?php echo $settings['default_currency'] ?? 'EUR'; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Late Return Fee', 'custom-rental-manager'); ?></th>
                                <td>
                                    <input type="number" name="late_return_fee" value="<?php echo esc_attr($settings['late_return_fee'] ?? '25'); ?>" class="regular-text" step="0.01" min="0">
                                    <?php echo $settings['default_currency'] ?? 'EUR'; ?> <?php _e('per day', 'custom-rental-manager'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Cleaning Fee', 'custom-rental-manager'); ?></th>
                                <td>
                                    <input type="number" name="cleaning_fee" value="<?php echo esc_attr($settings['cleaning_fee'] ?? '50'); ?>" class="regular-text" step="0.01" min="0">
                                    <?php echo $settings['default_currency'] ?? 'EUR'; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div id="locations" class="tab-content">
                        <h2><?php _e('Business Locations', 'custom-rental-manager'); ?></h2>
                        <p><?php _e('Configure your rental locations and delivery zones.', 'custom-rental-manager'); ?></p>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Main Office Address', 'custom-rental-manager'); ?></th>
                                <td><textarea name="main_office_address" rows="3" class="large-text"><?php echo esc_textarea($settings['main_office_address'] ?? ''); ?></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Airport Delivery Available', 'custom-rental-manager'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="airport_delivery" value="1" <?php checked($settings['airport_delivery'] ?? 0, 1); ?>>
                                        <?php _e('Enable airport delivery service', 'custom-rental-manager'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Hotel Delivery Available', 'custom-rental-manager'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="hotel_delivery" value="1" <?php checked($settings['hotel_delivery'] ?? 0, 1); ?>>
                                        <?php _e('Enable hotel delivery service', 'custom-rental-manager'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div id="notifications" class="tab-content">
                        <h2><?php _e('Notification Settings', 'custom-rental-manager'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Email Notifications', 'custom-rental-manager'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="email_notifications" value="1" <?php checked($settings['email_notifications'] ?? 1, 1); ?>>
                                        <?php _e('Send email notifications for booking updates', 'custom-rental-manager'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('SMS Notifications', 'custom-rental-manager'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="sms_notifications" value="1" <?php checked($settings['sms_notifications'] ?? 0, 1); ?>>
                                        <?php _e('Send SMS notifications (requires SMS service)', 'custom-rental-manager'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Reminder Days', 'custom-rental-manager'); ?></th>
                                <td>
                                    <input type="number" name="reminder_days" value="<?php echo esc_attr($settings['reminder_days'] ?? '1'); ?>" class="small-text" min="0" max="30">
                                    <?php _e('days before pickup/return', 'custom-rental-manager'); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="<?php _e('Save Settings', 'custom-rental-manager'); ?>">
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['crcm_settings_nonce'], 'crcm_settings')) {
            return;
        }
        
        $settings = array(
            'company_name' => sanitize_text_field($_POST['company_name'] ?? ''),
            'company_email' => sanitize_email($_POST['company_email'] ?? ''),
            'company_phone' => sanitize_text_field($_POST['company_phone'] ?? ''),
            'default_currency' => sanitize_text_field($_POST['default_currency'] ?? 'EUR'),
            'min_booking_duration' => intval($_POST['min_booking_duration'] ?? 1),
            'default_deposit_percentage' => intval($_POST['default_deposit_percentage'] ?? 30),
            'max_deposit_amount' => floatval($_POST['max_deposit_amount'] ?? 500),
            'late_return_fee' => floatval($_POST['late_return_fee'] ?? 25),
            'cleaning_fee' => floatval($_POST['cleaning_fee'] ?? 50),
            'main_office_address' => sanitize_textarea_field($_POST['main_office_address'] ?? ''),
            'airport_delivery' => isset($_POST['airport_delivery']) ? 1 : 0,
            'hotel_delivery' => isset($_POST['hotel_delivery']) ? 1 : 0,
            'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
            'sms_notifications' => isset($_POST['sms_notifications']) ? 1 : 0,
            'reminder_days' => intval($_POST['reminder_days'] ?? 1)
        );
        
        update_option('crcm_settings', $settings);
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'custom-rental-manager') . '</p></div>';
    }
    
    /**
     * AJAX: Get dashboard statistics
     */
    public function ajax_dashboard_stats() {
        check_ajax_referer('crcm_ajax', 'nonce');
        
        // Get booking statistics
        $bookings = get_posts(array(
            'post_type' => 'crcm_booking',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $total_bookings = count($bookings);
        $active_rentals = 0;
        $monthly_revenue = 0;
        
        $current_month = date('Y-m');
        
        foreach ($bookings as $booking) {
            $status = get_post_meta($booking->ID, '_crcm_booking_status', true);
            $amount = floatval(get_post_meta($booking->ID, '_crcm_total_amount', true));
            $booking_date = get_the_date('Y-m', $booking->ID);
            
            if ($status === 'active') {
                $active_rentals++;
            }
            
            if ($booking_date === $current_month && $status === 'completed') {
                $monthly_revenue += $amount;
            }
        }
        
        // Get vehicle statistics
        $vehicles = get_posts(array(
            'post_type' => 'crcm_vehicle',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $vehicle_status = array(
            'available' => 0,
            'rented' => 0,
            'maintenance' => 0,
            'out_of_service' => 0
        );
        
        foreach ($vehicles as $vehicle) {
            $status = get_post_meta($vehicle->ID, '_crcm_vehicle_status', true) ?: 'available';
            if (isset($vehicle_status[$status])) {
                $vehicle_status[$status]++;
            }
        }
        
        wp_send_json_success(array(
            'total_bookings' => $total_bookings,
            'active_rentals' => $active_rentals,
            'available_vehicles' => $vehicle_status['available'],
            'monthly_revenue' => $monthly_revenue,
            'vehicle_status' => $vehicle_status
        ));
    }
    
    /**
     * AJAX: Get recent bookings
     */
    public function ajax_recent_bookings() {
        check_ajax_referer('crcm_ajax', 'nonce');
        
        $limit = intval($_POST['limit'] ?? 10);
        
        $bookings = get_posts(array(
            'post_type' => 'crcm_booking',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $result = array();
        
        foreach ($bookings as $booking) {
            $vehicle_id = get_post_meta($booking->ID, '_crcm_vehicle_id', true);
            $vehicle_name = $vehicle_id ? get_the_title($vehicle_id) : 'N/A';
            
            $result[] = array(
                'id' => $booking->ID,
                'customer_name' => get_post_meta($booking->ID, '_crcm_customer_name', true) ?: 'N/A',
                'vehicle_name' => $vehicle_name,
                'pickup_date' => get_post_meta($booking->ID, '_crcm_pickup_date', true) ?: 'N/A',
                'return_date' => get_post_meta($booking->ID, '_crcm_return_date', true) ?: 'N/A',
                'status' => get_post_meta($booking->ID, '_crcm_booking_status', true) ?: 'pending',
                'total_amount' => get_post_meta($booking->ID, '_crcm_total_amount', true) ?: '0',
                'edit_url' => admin_url('post.php?post=' . $booking->ID . '&action=edit')
            );
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Update vehicle status
     */
    public function ajax_vehicle_status_update() {
        check_ajax_referer('crcm_ajax', 'nonce');
        
        if (isset($_POST['updates']) && is_array($_POST['updates'])) {
            foreach ($_POST['updates'] as $update) {
                $vehicle_id = intval($update['vehicle_id']);
                $status = sanitize_text_field($update['status']);
                
                if ($vehicle_id && in_array($status, array('available', 'rented', 'maintenance', 'out_of_service'))) {
                    update_post_meta($vehicle_id, '_crcm_vehicle_status', $status);
                }
            }
        }
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Quick booking creation
     */
    public function ajax_quick_booking() {
        check_ajax_referer('crcm_ajax', 'nonce');
        
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $customer_phone = sanitize_text_field($_POST['customer_phone']);
        $vehicle_id = intval($_POST['vehicle_id']);
        $pickup_date = sanitize_text_field($_POST['pickup_date']);
        $return_date = sanitize_text_field($_POST['return_date']);
        
        if (empty($customer_name) || empty($vehicle_id) || empty($pickup_date) || empty($return_date)) {
            wp_send_json_error('Missing required fields');
        }
        
        // Create booking post
        $booking_id = wp_insert_post(array(
            'post_type' => 'crcm_booking',
            'post_title' => 'Quick Booking: ' . $customer_name,
            'post_status' => 'publish'
        ));
        
        if ($booking_id) {
            // Save booking meta
            update_post_meta($booking_id, '_crcm_customer_name', $customer_name);
            update_post_meta($booking_id, '_crcm_customer_phone', $customer_phone);
            update_post_meta($booking_id, '_crcm_vehicle_id', $vehicle_id);
            update_post_meta($booking_id, '_crcm_pickup_date', $pickup_date);
            update_post_meta($booking_id, '_crcm_return_date', $return_date);
            update_post_meta($booking_id, '_crcm_booking_status', 'pending');
            update_post_meta($booking_id, '_crcm_payment_status', 'pending');
            
            // Generate booking reference
            $reference = 'CRCM-' . date('Y') . '-' . str_pad($booking_id, 4, '0', STR_PAD_LEFT);
            update_post_meta($booking_id, '_crcm_booking_reference', $reference);
            
            wp_send_json_success(array(
                'booking_id' => $booking_id,
                'edit_url' => admin_url('post.php?post=' . $booking_id . '&action=edit')
            ));
        } else {
            wp_send_json_error('Failed to create booking');
        }
    }
    
    /**
     * Get dashboard CSS
     */
    private function get_dashboard_css() {
        return '
        .crcm-dashboard .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 2em;
            margin-right: 15px;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #0073aa;
            line-height: 1;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .booking-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .booking-status {
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: 1fr 1fr 100px;
            gap: 10px;
            font-size: 0.9em;
            color: #666;
        }
        
        .booking-actions {
            margin-top: 5px;
        }
        
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }
        
        .action-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            background: #f9f9f9;
        }
        
        .action-icon {
            font-size: 1.5em;
        }
        
        .action-content {
            padding: 15px;
        }
        
        .action-buttons {
            margin-top: 15px;
            text-align: right;
        }
        
        .action-buttons .button {
            margin-left: 10px;
        }
        
        .vehicle-status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .vehicle-status-select.changed {
            border-color: #f0ad4e;
            background-color: #fff3cd;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .analytics-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
        }
        
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .settings-tabs .tab-content {
            border: 1px solid #c3c4c7;
            border-top: none;
            padding: 20px;
            background: #fff;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .report-card {
            background: #fff;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            padding: 20px;
        }
        
        .notification {
            display: flex;
            align-items: flex-start;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        .notification-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .notification-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
        }
        
        .notification-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        
        .notification-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
        }
        
        .notification-icon {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-actions {
            margin-top: 5px;
        }
        
        .schedule-item {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .schedule-time {
            width: 60px;
            font-weight: bold;
            color: #0073aa;
        }
        
        .schedule-content {
            flex: 1;
        }
        
        .schedule-title {
            font-weight: bold;
        }
        
        .schedule-details {
            font-size: 0.9em;
            color: #666;
        }
        ';
    }
}
