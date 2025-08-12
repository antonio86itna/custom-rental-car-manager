<?php
/**
 * Search Results Page Template
 *
 * Displays vehicle search results using the [crcm_vehicle_list] shortcode.
 *
 * @package Costabilerent Theme
 */

get_header();

// Allowed query parameters to pass to the shortcode.
$allowed_params = array(
    'pickup_date',
    'return_date',
    'pickup_time',
    'return_time',
    'pickup_location',
    'return_location',
    'type',
    'per_page',
    'page',
);

$attrs = array();
foreach ( $allowed_params as $param ) {
    if ( isset( $_GET[ $param ] ) ) {
        $value      = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
        $attrs[] = sprintf( '%s="%s"', $param, esc_attr( $value ) );
    }
}

$shortcode = '[crcm_vehicle_list ' . implode( ' ', $attrs ) . ']';

echo do_shortcode( $shortcode );

get_footer();
