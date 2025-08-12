<?php
/**
 * Template Name: Customer Dashboard
 *
 * Displays the rental customer dashboard.
 *
 * @package CostabilerentTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

$user = wp_get_current_user();
if ( ! in_array( 'crcm_customer', (array) $user->roles, true ) ) {
	wp_safe_redirect( home_url() );
	exit;
}

get_header();

echo do_shortcode( '[crcm_customer_dashboard]' );

get_footer();
