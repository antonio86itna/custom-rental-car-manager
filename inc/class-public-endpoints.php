<?php
/**
 * Public Data Endpoints.
 *
 * Provides REST data for the theme to render forms, lists and checkouts.
 *
 * @package CustomRentalCarManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class handling public REST endpoints used by the theme.
 */
class CRCM_Public_Endpoints {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            'crcm/v1',
            '/search-form',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_search_form' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'crcm/v1',
            '/booking-form',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_booking_form' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'vehicle_id' => array(
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'pickup_date' => array(
                        'required'          => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'return_date' => array(
                        'required'          => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'pickup_time' => array(
                        'required'          => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'return_time' => array(
                        'required'          => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        register_rest_route(
            'crcm/v1',
            '/customer-dashboard',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_customer_dashboard' ),
                'permission_callback' => array( $this, 'check_customer_access' ),
            )
        );
    }

    /**
     * Provide data for the search form.
     *
     * @return WP_REST_Response
     */
    public function get_search_form() {
        $locations = array();
        foreach ( crcm_get_locations() as $location ) {
            $locations[] = array(
                'id'   => $location->term_id,
                'name' => $location->name,
            );
        }

        $data = apply_filters(
            'crcm_search_form_data',
            array(
                'locations' => $locations,
            )
        );

        return rest_ensure_response( $data );
    }

    /**
     * Provide data for the booking form.
     *
     * @param WP_REST_Request $request Request instance.
     *
     * @return WP_REST_Response
     */
    public function get_booking_form( WP_REST_Request $request ) {
        $vehicle_id  = $request->get_param( 'vehicle_id' );
        $pickup_date = $request->get_param( 'pickup_date' );
        $return_date = $request->get_param( 'return_date' );
        $pickup_time = $request->get_param( 'pickup_time' );
        $return_time = $request->get_param( 'return_time' );

        $pricing_data = get_post_meta( $vehicle_id, '_crcm_pricing_data', true );
        $daily_rate   = $pricing_data['daily_rate'] ?? 0;
        $rental_days  = 1;

        if ( $pickup_date && $return_date ) {
            try {
                $pickup = new DateTime( $pickup_date );
                $return = new DateTime( $return_date );
                $rental_days = max( 1, $return->diff( $pickup )->days );
            } catch ( Exception $e ) {
                $rental_days = 1;
            }
        }

        $end_date = new DateTime( $pickup_date ?: date( 'Y-m-d' ) );
        $end_date->add( new DateInterval( 'P' . $rental_days . 'D' ) );
        $base_total_calc  = crcm_calculate_vehicle_pricing( $vehicle_id, $pickup_date, $end_date->format( 'Y-m-d' ) );
        $extra_daily_rate = $rental_days > 0 ? max( 0, ( $base_total_calc - ( $daily_rate * $rental_days ) ) / $rental_days ) : 0;

        $currency_symbol = crcm_get_setting( 'currency_symbol', '€' );

        $data = apply_filters(
            'crcm_booking_form_data',
            array(
                'vehicle_id'       => $vehicle_id,
                'pickup_date'      => $pickup_date,
                'return_date'      => $return_date,
                'pickup_time'      => $pickup_time,
                'return_time'      => $return_time,
                'pricing_data'     => $pricing_data,
                'daily_rate'       => $daily_rate,
                'rental_days'      => $rental_days,
                'extra_daily_rate' => $extra_daily_rate,
                'currency_symbol'  => $currency_symbol,
            ),
            $request
        );

        return rest_ensure_response( $data );
    }

    /**
     * Provide data for the customer dashboard.
     *
     * @return WP_REST_Response
     */
    public function get_customer_dashboard() {
        $data = apply_filters( 'crcm_customer_dashboard_data', array() );
        return rest_ensure_response( $data );
    }

    /**
     * Ensure the current user can access the customer dashboard.
     *
     * @return true|WP_Error
     */
    public function check_customer_access() {
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Please log in to access your dashboard.', 'custom-rental-manager' ),
                array( 'status' => 401 )
            );
        }

        if ( ! crcm_user_is_customer() ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Access restricted to rental customers.', 'custom-rental-manager' ),
                array( 'status' => 403 )
            );
        }

        return true;
    }
}
