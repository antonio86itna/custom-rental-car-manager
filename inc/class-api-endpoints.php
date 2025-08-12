<?php
/**
 * API Endpoints Class
 * 
 * Handles all REST API endpoints for external integrations
 * and mobile app compatibility.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRCM_API_Endpoints {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route( 'crcm/v1', '/vehicles', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_vehicles' ),
            'permission_callback' => array( $this, 'check_public_permissions' ),
            'args'                => array(
                'pickup_date' => array(
                    'required' => false,
                    'type'     => 'string',
                    'format'   => 'date',
                ),
                'return_date' => array(
                    'required' => false,
                    'type'     => 'string',
                    'format'   => 'date',
                ),
                'vehicle_type' => array(
                    'required' => false,
                    'type'     => 'string',
                ),
                'location' => array(
                    'required' => false,
                    'type'     => 'integer',
                ),
                'per_page' => array(
                    'required' => false,
                    'type'     => 'integer',
                ),
                'page' => array(
                    'required' => false,
                    'type'     => 'integer',
                ),
            ),
        ) );

        register_rest_route( 'crcm/v1', '/vehicles/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_vehicle' ),
            'permission_callback' => array( $this, 'check_public_permissions' ),
            'args'                => array(
                'id' => array(
                    'required' => true,
                    'type'     => 'integer',
                ),
            ),
        ) );

        register_rest_route('crcm/v1', '/bookings', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_bookings' ),
                'permission_callback' => array( $this, 'check_manage_permissions' ),
                'args'                => array(
                    'per_page' => array(
                        'required' => false,
                        'type'     => 'integer',
                    ),
                    'page' => array(
                        'required' => false,
                        'type'     => 'integer',
                    ),
                    'status' => array(
                        'required' => false,
                        'type'     => 'string',
                    ),
                ),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_booking'),
                'permission_callback' => array( $this, 'check_public_permissions' ),
                'args' => array(
                    'vehicle_id' => array(
                        'required' => true,
                        'type' => 'integer',
                    ),
                    'pickup_date' => array(
                        'required' => true,
                        'type' => 'string',
                        'format' => 'date',
                    ),
                    'return_date' => array(
                        'required' => true,
                        'type' => 'string',
                        'format' => 'date',
                    ),
                    'customer_data' => array(
                        'required' => true,
                        'type' => 'object',
                    ),
                ),
            ),
        ));

        register_rest_route('crcm/v1', '/bookings/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_booking'),
            'permission_callback' => array($this, 'check_booking_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
            ),
        ));

        register_rest_route('crcm/v1', '/availability', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_availability'),
            'permission_callback' => array( $this, 'check_public_permissions' ),
            'args' => array(
                'vehicle_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'start_date' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                ),
                'end_date' => array(
                    'required' => true,
                    'type' => 'string',
                    'format' => 'date',
                ),
            ),
        ));

        register_rest_route('crcm/v1', '/calendar', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_calendar'),
            'permission_callback' => array($this, 'check_manage_permissions'),
            'args' => array(
                'month' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'vehicle_id' => array(
                    'required' => false,
                    'type' => 'integer',
                ),
                'per_page' => array(
                    'required' => false,
                    'type'     => 'integer',
                ),
                'page' => array(
                    'required' => false,
                    'type'     => 'integer',
                ),
            ),
        ));
    }

    /**
     * Get vehicles endpoint.
     *
     * Retrieves vehicles with optional date filtering and pagination support.
     *
     * @param WP_REST_Request $request Request data.
     * @return WP_REST_Response
     */
    public function get_vehicles( $request ) {
        $vehicle_manager = crcm()->vehicle_manager;

        $pickup_date = sanitize_text_field( $request->get_param( 'pickup_date' ) );
        $return_date = sanitize_text_field( $request->get_param( 'return_date' ) );

        $per_page = $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 20;
        $page     = $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1;

        if ( $pickup_date && $return_date ) {
            $vehicle_type = sanitize_text_field( $request->get_param( 'vehicle_type' ) );

            $results = $vehicle_manager->search_available_vehicles(
                $pickup_date,
                $return_date,
                $vehicle_type,
                $per_page,
                $page
            );

            $vehicles   = $results['vehicles'];
            $pagination = $results['pagination'];
        } else {
            $args = array(
                'post_type'      => 'crcm_vehicle',
                'post_status'    => 'publish',
                'posts_per_page' => $per_page,
                'paged'          => $page,
            );

            $query    = new WP_Query( $args );
            $posts    = $query->posts;
            $vehicles = array();

            foreach ( $posts as $post ) {
                $vehicle_data = get_post_meta( $post->ID, '_crcm_vehicle_data', true );
                $pricing_data = get_post_meta( $post->ID, '_crcm_pricing_data', true );

                $vehicles[] = array(
                    'id'             => $post->ID,
                    'title'          => $post->post_title,
                    'description'    => $post->post_content,
                    'vehicle_data'   => $vehicle_data,
                    'pricing_data'   => $pricing_data,
                    'featured_image' => get_the_post_thumbnail_url( $post->ID, 'medium' ),
                );
            }

            $pagination = array(
                'current' => $page,
                'total'   => (int) $query->max_num_pages,
            );
        }

        $pagination['per_page'] = $per_page;

        return new WP_REST_Response(
            array(
                'vehicles'   => $vehicles,
                'pagination' => $pagination,
            ),
            200
        );
    }

    /**
     * Get single vehicle endpoint
     */
    public function get_vehicle($request) {
        $vehicle_id = absint( $request->get_param( 'id' ) );
        if ( ! $vehicle_id ) {
            return new WP_Error( 'invalid_vehicle', __( 'Invalid vehicle ID', 'custom-rental-manager' ), array( 'status' => 400 ) );
        }

        $vehicle_manager = crcm()->vehicle_manager;

        $vehicle = $vehicle_manager->get_vehicle( $vehicle_id );

        if (!$vehicle) {
            return new WP_Error('vehicle_not_found', __('Vehicle not found', 'custom-rental-manager'), array('status' => 404));
        }

        return new WP_REST_Response($vehicle, 200);
    }

    /**
     * Get bookings endpoint.
     *
     * @param WP_REST_Request $request Request data.
     *
     * @return WP_REST_Response
     */
    public function get_bookings($request) {
        $per_page = $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 20;
        $page     = $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1;

        $args = array(
            'post_type'      => 'crcm_booking',
            'post_status'    => array( 'publish', 'private' ),
            'posts_per_page' => $per_page,
            'paged'          => $page,
        );

        // Add filters if provided
        $status = sanitize_text_field( $request->get_param( 'status' ) );
        if ( $status ) {
            $args['meta_query'][] = array(
                'key'     => '_crcm_booking_status',
                'value'   => $status,
                'compare' => '=',
            );
        }

        $query    = new WP_Query( $args );
        $posts    = $query->posts;
        $bookings = array();

        foreach ( $posts as $post ) {
            $booking_manager = crcm()->booking_manager;
            $booking         = $booking_manager->get_booking( $post->ID );
            if ( ! is_wp_error( $booking ) ) {
                $bookings[] = $booking;
            }
        }

        $pagination = array(
            'current'  => $page,
            'total'    => (int) $query->max_num_pages,
            'per_page' => $per_page,
        );

        return new WP_REST_Response(
            array(
                'bookings'   => $bookings,
                'pagination' => $pagination,
            ),
            200
        );
    }

    /**
     * Create booking endpoint
     */
    public function create_booking($request) {
        $booking_data = array(
            'vehicle_id'      => absint( $request->get_param( 'vehicle_id' ) ),
            'pickup_date'     => sanitize_text_field( $request->get_param( 'pickup_date' ) ),
            'return_date'     => sanitize_text_field( $request->get_param( 'return_date' ) ),
            'pickup_time'     => sanitize_text_field( $request->get_param( 'pickup_time' ) ?: '09:00' ),
            'return_time'     => sanitize_text_field( $request->get_param( 'return_time' ) ?: '18:00' ),
            'pickup_location' => sanitize_text_field( $request->get_param( 'pickup_location' ) ),
            'return_location' => sanitize_text_field( $request->get_param( 'return_location' ) ),
            'home_delivery'   => (bool) $request->get_param( 'home_delivery' ),
            'delivery_address'=> sanitize_text_field( $request->get_param( 'delivery_address' ) ),
            'extras'          => array_map( 'sanitize_text_field', (array) $request->get_param( 'extras' ) ),
            'insurance_type'  => sanitize_text_field( $request->get_param( 'insurance_type' ) ?: 'basic' ),
            'customer_data'   => array(
                'first_name' => sanitize_text_field( $request->get_param( 'customer_data' )['first_name'] ?? '' ),
                'last_name'  => sanitize_text_field( $request->get_param( 'customer_data' )['last_name'] ?? '' ),
                'email'      => sanitize_email( $request->get_param( 'customer_data' )['email'] ?? '' ),
                'phone'      => sanitize_text_field( $request->get_param( 'customer_data' )['phone'] ?? '' ),
            ),
            'notes'           => sanitize_textarea_field( $request->get_param( 'notes' ) ),
        );

        $booking_manager = crcm()->booking_manager;
        $result = $booking_manager->create_booking($booking_data);

        if (is_wp_error($result)) {
            return new WP_Error('booking_creation_failed', $result->get_error_message(), array('status' => 400));
        }

        // Trigger booking created action
        do_action('crcm_booking_created', $result['booking_id']);

        return new WP_REST_Response($result, 201);
    }

    /**
     * Get single booking endpoint
     */
    public function get_booking($request) {
        $booking_id = absint( $request->get_param( 'id' ) );
        if ( ! $booking_id ) {
            return new WP_Error( 'invalid_booking_id', __( 'Invalid booking ID', 'custom-rental-manager' ), array( 'status' => 400 ) );
        }

        $booking_manager = crcm()->booking_manager;

        $booking = $booking_manager->get_booking( $booking_id );

        if (is_wp_error($booking)) {
            return new WP_Error('booking_not_found', __('Booking not found', 'custom-rental-manager'), array('status' => 404));
        }

        return new WP_REST_Response($booking, 200);
    }

    /**
     * Get availability endpoint
     */
    public function get_availability($request) {
        $vehicle_id = absint( $request->get_param( 'vehicle_id' ) );
        $start_date = sanitize_text_field( $request->get_param( 'start_date' ) );
        $end_date   = sanitize_text_field( $request->get_param( 'end_date' ) );

        $vehicle_manager   = crcm()->vehicle_manager;
        $availability      = $vehicle_manager->check_availability( $vehicle_id, $start_date, $end_date );

        return new WP_REST_Response(
            array(
                'vehicle_id'        => $vehicle_id,
                'start_date'        => $start_date,
                'end_date'          => $end_date,
                'available_quantity'=> $availability,
            ),
            200
        );
    }

    /**
     * Get calendar endpoint.
     *
     * @param WP_REST_Request $request Request data.
     *
     * @return WP_REST_Response
     */
    public function get_calendar($request) {
        $month      = sanitize_text_field( $request->get_param( 'month' ) ?: date( 'Y-m' ) );
        $vehicle_id = absint( $request->get_param( 'vehicle_id' ) ?: 0 );
        $per_page   = $request->get_param( 'per_page' ) ? absint( $request->get_param( 'per_page' ) ) : 20;
        $page       = $request->get_param( 'page' ) ? absint( $request->get_param( 'page' ) ) : 1;

        $calendar_manager = new CRCM_Calendar_Manager();
        $calendar_data    = $calendar_manager->get_calendar_data( $month, $vehicle_id, $per_page, $page );

        return new WP_REST_Response( $calendar_data, 200 );
    }

    /**
     * Basic permission check for public endpoints.
     *
     * Validates REST nonce when provided and applies a simple
     * rate limiting based on the request IP to mitigate abuse.
     *
     * @param WP_REST_Request $request Current request.
     * @return true|WP_Error
     */
    public function check_public_permissions( $request ) {
        $ip   = $request->get_header( 'X-Forwarded-For' );
        $ip   = $ip ? explode( ',', $ip )[0] : ( $_SERVER['REMOTE_ADDR'] ?? '' );
        $key  = 'crcm_rate_' . md5( $ip );
        $hits = (int) get_transient( $key );

        if ( $hits >= 100 ) {
            return new WP_Error(
                'rest_rate_limited',
                __( 'Too many requests', 'custom-rental-manager' ),
                array( 'status' => 429 )
            );
        }

        set_transient( $key, $hits + 1, MINUTE_IN_SECONDS * 10 );

        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( $nonce && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return true;
        }

        if ( is_user_logged_in() ) {
            return current_user_can( 'read' );
        }

        return new WP_Error(
            'rest_forbidden',
            __( 'Invalid or missing nonce', 'custom-rental-manager' ),
            array( 'status' => rest_authorization_required_code() )
        );
    }

    /**
     * Check if user has manage permissions
     */
    public function check_manage_permissions( $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Invalid or missing nonce', 'custom-rental-manager' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        if ( current_user_can( 'manage_options' ) || current_user_can( 'crcm_manage_bookings' ) ) {
            return true;
        }

        return new WP_Error(
            'rest_forbidden',
            __( 'You are not allowed to access this resource.', 'custom-rental-manager' ),
            array( 'status' => rest_authorization_required_code() )
        );
    }

    /**
     * Check booking permissions (own booking or admin)
     */
    public function check_booking_permissions( $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Invalid or missing nonce', 'custom-rental-manager' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        if ( current_user_can( 'manage_options' ) || current_user_can( 'crcm_manage_bookings' ) ) {
            return true;
        }

        $booking_id      = absint( $request->get_param( 'id' ) );
        $current_user_id = get_current_user_id();

        if ( ! $current_user_id || ! $booking_id ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Authentication required', 'custom-rental-manager' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        // Check if this is the customer's own booking
        $customer_data = get_post_meta( $booking_id, '_crcm_customer_data', true );
        $current_user  = wp_get_current_user();

        if ( $customer_data && isset( $customer_data['email'] ) && $customer_data['email'] === $current_user->user_email ) {
            return true;
        }

        return new WP_Error(
            'rest_forbidden',
            __( 'You are not allowed to view this booking.', 'custom-rental-manager' ),
            array( 'status' => rest_authorization_required_code() )
        );
    }
}
