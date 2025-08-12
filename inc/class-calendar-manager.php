<?php
/**
 * Calendar Manager Class
 * 
 * Handles calendar functionality, availability management,
 * and dashboard calendar displays.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRCM_Calendar_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_crcm_get_calendar_data', array($this, 'ajax_get_calendar_data'));
        add_action('wp_ajax_crcm_update_availability', array($this, 'ajax_update_availability'));
    }

    /**
     * Get calendar data for dashboard
     */
    public function ajax_get_calendar_data() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'custom-rental-manager'));
        }

        $month      = sanitize_text_field( $_POST['month'] ?? date( 'Y-m' ) );
        $vehicle_id = intval( $_POST['vehicle_id'] ?? 0 );
        $per_page   = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
        $page       = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

        $calendar_data = $this->get_calendar_data( $month, $vehicle_id, $per_page, $page );
        wp_send_json_success( $calendar_data );
    }

    /**
     * Get calendar data for a specific month.
     *
     * @param string $month      Month in 'Y-m' format.
     * @param int    $vehicle_id Optional vehicle ID filter.
     * @param int    $per_page   Number of bookings per page.
     * @param int    $page       Current page number.
     *
     * @return array{events: array, pagination: array} Calendar events and pagination info.
     */
    public function get_calendar_data( $month, $vehicle_id = 0, $per_page = 20, $page = 1 ) {
        $start_date = $month . '-01';
        $end_date   = date( 'Y-m-t', strtotime( $start_date ) );

        $cache_key = 'crcm_calendar_' . md5( $month . '_' . $vehicle_id . '_' . $per_page . '_' . $page );
        $cached    = wp_cache_get( $cache_key, 'crcm_calendar' );
        if ( false !== $cached ) {
            return $cached;
        }

        // Get bookings for the month
        $booking_args = array(
            'post_type'      => 'crcm_booking',
            'post_status'    => array( 'publish', 'private' ),
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_crcm_pickup_date',
                        'value'   => array( $start_date, $end_date ),
                        'compare' => 'BETWEEN',
                        'type'    => 'DATE',
                    ),
                    array(
                        'key'     => '_crcm_return_date',
                        'value'   => array( $start_date, $end_date ),
                        'compare' => 'BETWEEN',
                        'type'    => 'DATE',
                    ),
                ),
            ),
        );

        if ( $vehicle_id > 0 ) {
            $booking_args['meta_query'][] = array(
                'key'     => '_crcm_vehicle_id',
                'value'   => $vehicle_id,
                'compare' => '=',
            );
        }

        $query    = new WP_Query( $booking_args );
        $bookings = $query->posts;

        $calendar_events = array();

        foreach ( $bookings as $booking_id ) {
            $booking_data   = get_post_meta( $booking_id, '_crcm_booking_data', true );
            $customer_data  = get_post_meta( $booking_id, '_crcm_customer_data', true );
            $booking_status = get_post_meta( $booking_id, '_crcm_booking_status', true );
            $booking_number = get_post_meta( $booking_id, '_crcm_booking_number', true );

            $vehicle = get_post( $booking_data['vehicle_id'] );

            $calendar_events[] = array(
                'id'        => $booking_id,
                'title'     => $booking_number . ' - ' . $customer_data['first_name'] . ' ' . $customer_data['last_name'],
                'start'     => $booking_data['pickup_date'],
                'end'       => date( 'Y-m-d', strtotime( $booking_data['return_date'] . ' +1 day' ) ), // FullCalendar needs exclusive end date
                'vehicle'   => $vehicle ? $vehicle->post_title : '',
                'customer'  => $customer_data['first_name'] . ' ' . $customer_data['last_name'],
                'phone'     => $customer_data['phone'],
                'status'    => $booking_status,
                'className' => 'crcm-booking-' . $booking_status,
                'extendedProps' => array(
                    'booking_number'   => $booking_number,
                    'pickup_time'      => $booking_data['pickup_time'],
                    'return_time'      => $booking_data['return_time'],
                    'pickup_location'  => $booking_data['pickup_location'],
                    'return_location'  => $booking_data['return_location'],
                    'home_delivery'    => $booking_data['home_delivery'],
                    'delivery_address' => $booking_data['delivery_address'],
                ),
            );
        }

        $pagination = array(
            'current'  => $page,
            'total'    => (int) $query->max_num_pages,
            'per_page' => $per_page,
        );

        $result = array(
            'events'     => $calendar_events,
            'pagination' => $pagination,
        );

        wp_cache_set( $cache_key, $result, 'crcm_calendar', DAY_IN_SECONDS );

        return $result;
    }

    /**
     * Update vehicle availability
     */
    public function ajax_update_availability() {
        check_ajax_referer('crcm_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'custom-rental-manager'));
        }

        global $wpdb;

        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $price_override = floatval($_POST['price_override'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $table_name = $wpdb->prefix . 'crcm_availability';

        // Check if record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE vehicle_id = %d AND date = %s",
            $vehicle_id, $date
        ));

        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $table_name,
                array(
                    'available_quantity' => $quantity,
                    'price_override' => $price_override > 0 ? $price_override : null,
                    'notes' => $notes,
                ),
                array(
                    'vehicle_id' => $vehicle_id,
                    'date' => $date,
                ),
                array('%d', '%f', '%s'),
                array('%d', '%s')
            );
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $table_name,
                array(
                    'vehicle_id' => $vehicle_id,
                    'date' => $date,
                    'available_quantity' => $quantity,
                    'price_override' => $price_override > 0 ? $price_override : null,
                    'notes' => $notes,
                ),
                array('%d', '%s', '%d', '%f', '%s')
            );
        }

        if ($result !== false) {
            wp_send_json_success(__('Availability updated successfully', 'custom-rental-manager'));
        } else {
            wp_send_json_error(__('Failed to update availability', 'custom-rental-manager'));
        }
    }

    /**
     * Get upcoming bookings for dashboard.
     *
     * @param int $days     Number of days to look ahead.
     * @param int $per_page Number of bookings per page.
     * @param int $page     Current page number.
     *
     * @return array
     */
    public function get_upcoming_bookings( $days = 7, $per_page = 20, $page = 1 ) {
        $start_date = date( 'Y-m-d' );
        $end_date   = date( 'Y-m-d', strtotime( '+' . $days . ' days' ) );

        $args = array(
            'post_type'      => 'crcm_booking',
            'post_status'    => array( 'publish', 'private' ),
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'meta_query'     => array(
                array(
                    'key'     => '_crcm_pickup_date',
                    'value'   => array( $start_date, $end_date ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ),
                array(
                    'key'     => '_crcm_booking_status',
                    'value'   => array( 'confirmed', 'active' ),
                    'compare' => 'IN',
                ),
            ),
            'orderby' => 'meta_value',
            'meta_key' => '_crcm_pickup_date',
            'order' => 'ASC',
        );

        $query = new WP_Query( $args );

        return $query->posts;
    }

    /**
     * Get vehicles out today.
     *
     * @param int $per_page Number of bookings per page.
     * @param int $page     Current page number.
     *
     * @return array
     */
    public function get_vehicles_out_today( $per_page = 20, $page = 1 ) {
        $today = date( 'Y-m-d' );

        $args = array(
            'post_type'      => 'crcm_booking',
            'post_status'    => array( 'publish', 'private' ),
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'meta_query'     => array(
                array(
                    'key'     => '_crcm_pickup_date',
                    'value'   => $today,
                    'compare' => '=',
                    'type'    => 'DATE',
                ),
                array(
                    'key'     => '_crcm_booking_status',
                    'value'   => array( 'confirmed', 'active' ),
                    'compare' => 'IN',
                ),
            ),
        );

        $query = new WP_Query( $args );

        return $query->posts;
    }

    /**
     * Get vehicles returning today.
     *
     * @param int $per_page Number of bookings per page.
     * @param int $page     Current page number.
     *
     * @return array
     */
    public function get_vehicles_returning_today( $per_page = 20, $page = 1 ) {
        $today = date( 'Y-m-d' );

        $args = array(
            'post_type'      => 'crcm_booking',
            'post_status'    => array( 'publish', 'private' ),
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'meta_query'     => array(
                array(
                    'key'     => '_crcm_return_date',
                    'value'   => $today,
                    'compare' => '=',
                    'type'    => 'DATE',
                ),
                array(
                    'key'     => '_crcm_booking_status',
                    'value'   => 'active',
                    'compare' => '=',
                ),
            ),
        );

        $query = new WP_Query( $args );

        return $query->posts;
    }
}
