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

// Get current month data
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$days_in_month = date('t', $first_day);
$first_day_of_week = date('w', $first_day);

// Get bookings for current month
$start_date = date('Y-m-01', $first_day);
$end_date = date('Y-m-t', $first_day);

$bookings = get_posts(array(
    'post_type' => 'crcm_booking',
    'post_status' => array('publish', 'private'),
    'posts_per_page' => -1,
    'date_query' => array(
        array(
            'after' => $start_date,
            'before' => $end_date,
            'inclusive' => true,
        ),
    ),
));

// Organize bookings by date
$bookings_by_date = array();
foreach ($bookings as $booking) {
    $booking_data = get_post_meta($booking->ID, '_crcm_booking_data', true);
    $booking_status = get_post_meta($booking->ID, '_crcm_booking_status', true);
    
    if (!$booking_data || !isset($booking_data['pickup_date'])) {
        continue;
    }
    
    $pickup_date = $booking_data['pickup_date'];
    $return_date = $booking_data['return_date'] ?? $pickup_date;
    
    // Add booking to pickup date
    $day = date('j', strtotime($pickup_date));
    if (!isset($bookings_by_date[$day])) {
        $bookings_by_date[$day] = array();
    }
    
    $vehicle = get_post($booking_data['vehicle_id']);
    $customer_data = get_post_meta($booking->ID, '_crcm_customer_data', true);
    
    $bookings_by_date[$day][] = array(
        'id' => $booking->ID,
        'vehicle_name' => $vehicle ? $vehicle->post_title : 'Unknown',
        'customer_name' => $customer_data ? $customer_data['first_name'] . ' ' . $customer_data['last_name'] : 'Unknown',
        'status' => $booking_status,
        'pickup_date' => $booking_data['pickup_date'],
        'return_date' => $return_date,
        'type' => 'pickup',
    );
}

// Navigation URLs
$prev_month = $current_month - 1;
$prev_year = $current_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $current_month + 1;
$next_year = $current_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$prev_url = admin_url('admin.php?page=crcm-calendar&month=' . $prev_month . '&year=' . $prev_year);
$next_url = admin_url('admin.php?page=crcm-calendar&month=' . $next_month . '&year=' . $next_year);
$today_url = admin_url('admin.php?page=crcm-calendar');
?>

<div class="wrap crcm-calendar">
    <h1><?php _e('Rental Calendar', 'custom-rental-manager'); ?></h1>
    
    <!-- Calendar Navigation -->
    <div class="crcm-calendar-nav">
        <div class="crcm-nav-buttons">
            <a href="<?php echo esc_url($prev_url); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e('Previous', 'custom-rental-manager'); ?>
            </a>
            
            <a href="<?php echo esc_url($today_url); ?>" class="button">
                <?php _e('Today', 'custom-rental-manager'); ?>
            </a>
            
            <a href="<?php echo esc_url($next_url); ?>" class="button">
                <?php _e('Next', 'custom-rental-manager'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
        
        <h2 class="crcm-calendar-title">
            <?php echo date_i18n('F Y', $first_day); ?>
        </h2>
        
        <div class="crcm-calendar-legend">
            <span class="crcm-legend-item crcm-pickup"><?php _e('Pickup', 'custom-rental-manager'); ?></span>
            <span class="crcm-legend-item crcm-active"><?php _e('Active Rental', 'custom-rental-manager'); ?></span>
            <span class="crcm-legend-item crcm-return"><?php _e('Return', 'custom-rental-manager'); ?></span>
        </div>
    </div>
    
    <!-- Calendar Grid -->
    <div class="crcm-calendar-container">
        <table class="crcm-calendar-table">
            <thead>
                <tr>
                    <th><?php _e('Sun', 'custom-rental-manager'); ?></th>
                    <th><?php _e('Mon', 'custom-rental-manager'); ?></th>
                    <th><?php _e('Tue', 'custom-rental-manager'); ?></th>
                    <th><?php _e('Wed', 'custom-rental-manager'); ?></th>
                    <th><?php _e('Thu', 'custom-rental-manager'); ?></th>
                    <th><?php _e('Fri', 'custom-rental-manager'); ?></th>
                    <th><?php _e('Sat', 'custom-rental-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $week_day = 0;
                echo '<tr>';
                
                // Empty cells for days before month starts
                for ($i = 0; $i < $first_day_of_week; $i++) {
                    echo '<td class="crcm-empty-day"></td>';
                    $week_day++;
                }
                
                // Days of the month
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $is_today = (date('Y-m-d') === date('Y-m-d', mktime(0, 0, 0, $current_month, $day, $current_year)));
                    $day_bookings = isset($bookings_by_date[$day]) ? $bookings_by_date[$day] : array();
                    
                    $cell_class = 'crcm-calendar-day';
                    if ($is_today) {
                        $cell_class .= ' crcm-today';
                    }
                    if (!empty($day_bookings)) {
                        $cell_class .= ' crcm-has-bookings';
                    }
                    
                    echo '<td class="' . $cell_class . '">';
                    echo '<div class="crcm-day-number">' . $day . '</div>';
                    
                    if (!empty($day_bookings)) {
                        echo '<div class="crcm-day-bookings">';
                        
                        $booking_count = 0;
                        foreach ($day_bookings as $booking) {
                            if ($booking_count >= 3) {
                                $remaining = count($day_bookings) - 3;
                                echo '<div class="crcm-booking-item crcm-more">+' . $remaining . ' ' . __('more', 'custom-rental-manager') . '</div>';
                                break;
                            }
                            
                            $item_class = 'crcm-booking-item crcm-pickup';
                            $status_class = 'crcm-status-' . $booking['status'];
                            
                            echo '<div class="' . $item_class . ' ' . $status_class . '" data-booking-id="' . $booking['id'] . '">';
                            echo '<span class="crcm-booking-vehicle">' . esc_html($booking['vehicle_name']) . '</span>';
                            echo '<span class="crcm-booking-customer">' . esc_html($booking['customer_name']) . '</span>';
                            echo '</div>';
                            
                            $booking_count++;
                        }
                        
                        echo '</div>';
                    }
                    
                    echo '</td>';
                    
                    $week_day++;
                    if ($week_day % 7 === 0) {
                        echo '</tr><tr>';
                    }
                }
                
                // Fill remaining cells
                while ($week_day % 7 !== 0) {
                    echo '<td class="crcm-empty-day"></td>';
                    $week_day++;
                }
                
                echo '</tr>';
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Quick Stats -->
    <div class="crcm-calendar-stats">
        <div class="crcm-stat-box">
            <h3><?php _e('This Month', 'custom-rental-manager'); ?></h3>
            <p><?php printf(__('%d Total Bookings', 'custom-rental-manager'), count($bookings)); ?></p>
        </div>
        
        <div class="crcm-stat-box">
            <h3><?php _e('Today', 'custom-rental-manager'); ?></h3>
            <?php
            $today_bookings = isset($bookings_by_date[date('j')]) ? count($bookings_by_date[date('j')]) : 0;
            ?>
            <p><?php printf(__('%d Bookings', 'custom-rental-manager'), $today_bookings); ?></p>
        </div>
        
        <div class="crcm-stat-box">
            <h3><?php _e('Quick Actions', 'custom-rental-manager'); ?></h3>
            <p>
                <a href="<?php echo admin_url('post-new.php?post_type=crcm_booking'); ?>" class="button button-primary">
                    <?php _e('New Booking', 'custom-rental-manager'); ?>
                </a>
            </p>
        </div>
    </div>
</div>

