<?php
/**
 * Admin Calendar Template
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$vehicles = get_posts(array(
    'post_type' => 'crcm_vehicle',
    'post_status' => 'publish',
    'posts_per_page' => -1,
));
?>

<div class="wrap crcm-calendar">
    <h1><?php _e('Rental Calendar', 'custom-rental-manager'); ?></h1>

    <div class="crcm-calendar-filters">
        <label for="calendar-vehicle-filter"><?php _e('Filter by Vehicle:', 'custom-rental-manager'); ?></label>
        <select id="calendar-vehicle-filter">
            <option value=""><?php _e('All Vehicles', 'custom-rental-manager'); ?></option>
            <?php foreach ($vehicles as $vehicle): ?>
                <option value="<?php echo esc_attr($vehicle->ID); ?>"><?php echo esc_html($vehicle->post_title); ?></option>
            <?php endforeach; ?>
        </select>

        <button id="calendar-refresh" class="button"><?php _e('Refresh', 'custom-rental-manager'); ?></button>
    </div>

    <div id="crcm-calendar-container">
        <div id="crcm-calendar"></div>
    </div>

    <div class="crcm-calendar-legend">
        <h3><?php _e('Legend', 'custom-rental-manager'); ?></h3>
        <div class="legend-items">
            <span class="legend-item"><span class="legend-color crcm-booking-pending"></span> <?php _e('Pending', 'custom-rental-manager'); ?></span>
            <span class="legend-item"><span class="legend-color crcm-booking-confirmed"></span> <?php _e('Confirmed', 'custom-rental-manager'); ?></span>
            <span class="legend-item"><span class="legend-color crcm-booking-active"></span> <?php _e('Active', 'custom-rental-manager'); ?></span>
            <span class="legend-item"><span class="legend-color crcm-booking-completed"></span> <?php _e('Completed', 'custom-rental-manager'); ?></span>
            <span class="legend-item"><span class="legend-color crcm-booking-cancelled"></span> <?php _e('Cancelled', 'custom-rental-manager'); ?></span>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize FullCalendar (placeholder - would need FullCalendar library)
    var calendar = $('#crcm-calendar');

    // Mock calendar initialization
    calendar.html('<div class="crcm-calendar-placeholder"><p><?php _e('Calendar will be loaded here with FullCalendar library', 'custom-rental-manager'); ?></p></div>');

    function loadCalendarData() {
        var vehicleId = $('#calendar-vehicle-filter').val();
        var currentMonth = new Date().toISOString().slice(0, 7); // YYYY-MM format

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'crcm_get_calendar_data',
                nonce: crcm_admin.nonce,
                month: currentMonth,
                vehicle_id: vehicleId
            },
            success: function(response) {
                if (response.success) {
                    console.log('Calendar data loaded:', response.data);
                    // Update calendar with response.data
                }
            },
            error: function() {
                alert('<?php _e('Error loading calendar data', 'custom-rental-manager'); ?>');
            }
        });
    }

    $('#calendar-vehicle-filter').change(loadCalendarData);
    $('#calendar-refresh').click(loadCalendarData);

    // Load initial data
    loadCalendarData();
});
</script>

<style>
.crcm-calendar-filters {
    margin-bottom: 20px;
    padding: 15px;
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 6px;
}

.crcm-calendar-filters label {
    margin-right: 10px;
    font-weight: 600;
}

.crcm-calendar-filters select {
    margin-right: 10px;
    min-width: 200px;
}

#crcm-calendar-container {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 20px;
}

.crcm-calendar-placeholder {
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 6px;
}

.crcm-calendar-placeholder p {
    color: #666;
    font-size: 16px;
    margin: 0;
}

.crcm-calendar-legend {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 6px;
    padding: 15px;
}

.crcm-calendar-legend h3 {
    margin-top: 0;
    margin-bottom: 15px;
}

.legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    display: inline-block;
}

.crcm-booking-pending { background-color: #ffc107; }
.crcm-booking-confirmed { background-color: #17a2b8; }
.crcm-booking-active { background-color: #28a745; }
.crcm-booking-completed { background-color: #6c757d; }
.crcm-booking-cancelled { background-color: #dc3545; }
</style>
