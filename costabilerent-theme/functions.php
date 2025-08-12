<?php
/**
 * Theme functions and definitions
 *
 * @package Costabilerent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! function_exists( 'costabilerent_theme_setup' ) ) {
    /**
     * Set up theme defaults and registers support for WordPress features.
     *
     * @return void
     */
    function costabilerent_theme_setup() {
        load_theme_textdomain( 'costabilerent', get_template_directory() . '/languages' );

        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'custom-logo' );

        register_nav_menus(
            array(
                'primary' => __( 'Primary Menu', 'costabilerent' ),
            )
        );
    }
}
add_action( 'after_setup_theme', 'costabilerent_theme_setup' );

/**
 * Enqueue scripts and styles.
 *
 * @return void
 */
function costabilerent_enqueue_assets() {
    $version = wp_get_theme()->get( 'Version' );

    wp_enqueue_style(
        'costabilerent-style',
        get_stylesheet_uri(),
        array(),
        $version
    );

    $primary_color = get_theme_mod( 'costabilerent_primary_color', '#ff6600' );
    $css           = ":root { --primary-color: {$primary_color}; }";
    wp_add_inline_style( 'costabilerent-style', $css );

    wp_enqueue_style(
        'costabilerent-main',
        get_template_directory_uri() . '/assets/css/main.css',
        array(),
        $version
    );

    wp_enqueue_script(
        'costabilerent-script',
        get_template_directory_uri() . '/assets/js/main.js',
        array( 'jquery' ),
        $version,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'costabilerent_enqueue_assets' );

/**
 * Register Customizer settings for colors.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 * @return void
 */
function costabilerent_customize_register( $wp_customize ) {
    $wp_customize->add_setting(
        'costabilerent_primary_color',
        array(
            'default'           => '#ff6600',
            'sanitize_callback' => 'sanitize_hex_color',
        )
    );

    $wp_customize->add_control(
        new WP_Customize_Color_Control(
            $wp_customize,
            'costabilerent_primary_color',
            array(
                'label'   => __( 'Primary Color', 'costabilerent' ),
                'section' => 'colors',
            )
        )
    );
}
add_action( 'customize_register', 'costabilerent_customize_register' );

