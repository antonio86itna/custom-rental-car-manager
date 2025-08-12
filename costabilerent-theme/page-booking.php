<?php
/**
 * Booking Page Template
 *
 * Displays the booking form using the [crcm_booking_form] shortcode.
 *
 * @package Costabilerent Theme
 */

get_header();

// Allowed query parameters to pass to the shortcode.
$allowed_params = array(
    'vehicle',
    'pickup_date',
    'return_date',
    'pickup_time',
    'return_time',
    'pickup_location',
    'return_location',
);

$attrs = array();
foreach ( $allowed_params as $param ) {
    if ( isset( $_GET[ $param ] ) ) {
        $value    = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
        $attrs[] = sprintf( '%s="%s"', $param, esc_attr( $value ) );
    }
}

echo do_shortcode( '[crcm_booking_form ' . implode( ' ', $attrs ) . ']' );

get_footer();
