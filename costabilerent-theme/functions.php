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

    $primary_color   = get_theme_mod( 'costabilerent_primary_color', '#ff6600' );
    $secondary_color = get_theme_mod( 'costabilerent_secondary_color', '#333333' );
    $css             = ":root { --primary-color: {$primary_color}; --secondary-color: {$secondary_color}; }";
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

    wp_enqueue_style(
        'crcm-frontend',
        get_template_directory_uri() . '/assets/css/frontend.css',
        array(),
        $version
    );

    wp_enqueue_script(
        'crcm-frontend',
        get_template_directory_uri() . '/assets/js/frontend.js',
        array( 'jquery' ),
        $version,
        true
    );

    if ( class_exists( 'CRCM_Plugin' ) ) {
        $plugin = CRCM_Plugin::get_instance();
        wp_localize_script(
            'crcm-frontend',
            'crcm_ajax',
            array(
                'ajax_url'        => admin_url( 'admin-ajax.php' ),
                'nonce'           => wp_create_nonce( 'crcm_nonce' ),
                'currency_symbol' => $plugin->get_setting( 'currency_symbol', '€' ),
                'booking_page_url'=> home_url( '/booking/' ),
            )
        );
    }

    wp_enqueue_style(
        'crcm-search-form',
        get_template_directory_uri() . '/assets/css/frontend-search-form.css',
        array(),
        $version
    );

    wp_enqueue_script(
        'crcm-search-form',
        get_template_directory_uri() . '/assets/js/frontend-search-form.js',
        array( 'jquery' ),
        $version,
        true
    );

    wp_enqueue_style(
        'crcm-vehicle-list',
        get_template_directory_uri() . '/assets/css/frontend-vehicle-list.css',
        array(),
        $version
    );

    wp_enqueue_script(
        'crcm-vehicle-list',
        get_template_directory_uri() . '/assets/js/frontend-vehicle-list.js',
        array( 'jquery' ),
        $version,
        true
    );

    wp_enqueue_style(
        'crcm-booking-form',
        get_template_directory_uri() . '/assets/css/frontend-booking-form.css',
        array(),
        $version
    );

    wp_enqueue_script(
        'crcm-booking-form',
        get_template_directory_uri() . '/assets/js/frontend-booking-form.js',
        array( 'jquery' ),
        $version,
        true
    );

    wp_enqueue_style(
        'crcm-customer-dashboard',
        get_template_directory_uri() . '/assets/css/frontend-customer-dashboard.css',
        array(),
        $version
    );
}
add_action( 'wp_enqueue_scripts', 'costabilerent_enqueue_assets' );

/**
 * Register Customizer settings for branding and options.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 * @return void
 */
function costabilerent_customize_register( $wp_customize ) {
    $wp_customize->add_section(
        'costabilerent_branding',
        array(
            'title'    => __( 'Costabilerent Branding', 'costabilerent' ),
            'priority' => 30,
        )
    );

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

    $wp_customize->add_setting(
        'costabilerent_secondary_color',
        array(
            'default'           => '#333333',
            'sanitize_callback' => 'sanitize_hex_color',
        )
    );

    $wp_customize->add_control(
        new WP_Customize_Color_Control(
            $wp_customize,
            'costabilerent_secondary_color',
            array(
                'label'   => __( 'Secondary Color', 'costabilerent' ),
                'section' => 'colors',
            )
        )
    );

    $wp_customize->add_setting(
        'costabilerent_popular_vehicle_count',
        array(
            'default'           => 6,
            'sanitize_callback' => 'absint',
        )
    );

    $wp_customize->add_control(
        'costabilerent_popular_vehicle_count',
        array(
            'label'       => __( 'Number of Popular Vehicles', 'costabilerent' ),
            'section'     => 'costabilerent_branding',
            'type'        => 'number',
            'input_attrs' => array(
                'min' => 1,
                'max' => 20,
            ),
        )
    );

    $wp_customize->add_setting(
        'costabilerent_logo',
        array(
            'sanitize_callback' => 'absint',
        )
    );

    $wp_customize->add_control(
        new WP_Customize_Media_Control(
            $wp_customize,
            'costabilerent_logo',
            array(
                'label'     => __( 'Brand Logo', 'costabilerent' ),
                'section'   => 'costabilerent_branding',
                'mime_type' => 'image',
            )
        )
    );
}
add_action( 'customize_register', 'costabilerent_customize_register' );

/**
 * Provide theme mods to the Custom Rental Car Manager plugin.
 *
 * @param mixed  $default Default value provided by the plugin.
 * @param string $option  Option key requested.
 * @return mixed
 */
function costabilerent_crcm_theme_option( $default, $option ) {
    switch ( $option ) {
        case 'popular_vehicle_count':
            return (int) get_theme_mod( 'costabilerent_popular_vehicle_count', $default );
        case 'primary_color':
            return get_theme_mod( 'costabilerent_primary_color', $default );
        case 'secondary_color':
            return get_theme_mod( 'costabilerent_secondary_color', $default );
        case 'logo':
            return get_theme_mod( 'costabilerent_logo', $default );
        default:
            return $default;
    }
}
add_filter( 'crcm_theme_option', 'costabilerent_crcm_theme_option', 10, 2 );

/**
 * Render the search form template for the Custom Rental Car Manager plugin.
 *
 * @param string $output Current output.
 * @param array  $atts   Shortcode attributes.
 * @return string
 */
function costabilerent_render_search_form( $output, $atts ) {
    $template = locate_template( 'templates/search-form.php', false, false );

    if ( $template ) {
        ob_start();
        include $template; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude,PHPCS.Squiz.PHP.DiscouragedFunctions
        $output = ob_get_clean();
    }

    return $output;
}
add_filter( 'crcm_search_form', 'costabilerent_render_search_form', 10, 2 );

/**
 * Render the vehicle list template for the Custom Rental Car Manager plugin.
 *
 * @param string $output Current output.
 * @param array  $atts   Shortcode attributes.
 * @return string
 */
function costabilerent_render_vehicle_list( $output, $atts ) {
    $template = locate_template( 'templates/vehicle-list.php', false, false );

    if ( $template ) {
        ob_start();
        include $template; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude,PHPCS.Squiz.PHP.DiscouragedFunctions
        $output = ob_get_clean();
    }

    return $output;
}
add_filter( 'crcm_vehicle_list', 'costabilerent_render_vehicle_list', 10, 2 );

/**
 * Render the booking form template for the Custom Rental Car Manager plugin.
 *
 * @param string $output Current output.
 * @param array  $atts   Shortcode attributes.
 * @param array  $data   Template data passed from the plugin.
 * @return string
 */
function costabilerent_render_booking_form( $output, $atts, $data ) {
    if ( is_array( $data ) ) {
        extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
    }

    $template = locate_template( 'templates/booking-form.php', false, false );

    if ( $template ) {
        ob_start();
        include $template; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude,PHPCS.Squiz.PHP.DiscouragedFunctions
        $output = ob_get_clean();
    }

    return $output;
}
add_filter( 'crcm_booking_form', 'costabilerent_render_booking_form', 10, 3 );

/**
 * Render the customer dashboard template for the Custom Rental Car Manager plugin.
 *
 * @param string $output Current output.
 * @param array  $atts   Shortcode attributes.
 * @return string
 */
function costabilerent_render_customer_dashboard( $output, $atts ) {
    $template = locate_template( 'templates/customer-dashboard.php', false, false );

    if ( $template ) {
        ob_start();
        include $template; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude,PHPCS.Squiz.PHP.DiscouragedFunctions
        $output = ob_get_clean();
    }

    return $output;
}
add_filter( 'crcm_customer_dashboard', 'costabilerent_render_customer_dashboard', 10, 2 );

