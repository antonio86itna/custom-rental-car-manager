<?php

/**
 * Front Page Template
 *
 * @package Costabilerent Theme
 */

get_header();
?>

<div class="crcm-search-wrapper">
    <?php echo do_shortcode('[crcm_search_form]'); ?>
</div>

<?php
// Get top rented vehicles from last 30 days.
$top_vehicle_ids = get_transient( 'crcm_top_rented_vehicle_ids' );

if ( false === $top_vehicle_ids ) {
    $bookings = get_posts(
        array(
            'post_type'      => 'crcm_booking',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'date_query'     => array(
                array(
                    'after' => date( 'Y-m-d', strtotime( '-30 days' ) ),
                ),
            ),
            'fields'         => 'ids',
        )
    );

    $vehicle_counts = array();

    foreach ( $bookings as $booking_id ) {
        $booking_data = get_post_meta( $booking_id, '_crcm_booking_data', true );

        if ( isset( $booking_data['vehicle_id'] ) ) {
            $vehicle_id = absint( $booking_data['vehicle_id'] );
            if ( ! isset( $vehicle_counts[ $vehicle_id ] ) ) {
                $vehicle_counts[ $vehicle_id ] = 0;
            }
            $vehicle_counts[ $vehicle_id ]++;
        }
    }

    arsort( $vehicle_counts );
    $top_vehicle_ids = array_slice( array_keys( $vehicle_counts ), 0, 4 );

    set_transient( 'crcm_top_rented_vehicle_ids', $top_vehicle_ids, HOUR_IN_SECONDS );
}

if ( $top_vehicle_ids ) :
    ?>
    <div class="crcm-top-vehicles">
        <?php
        foreach ( $top_vehicle_ids as $vehicle_id ) :
            $vehicle = get_post( $vehicle_id );
            if ( ! $vehicle || 'crcm_vehicle' !== $vehicle->post_type ) {
                continue;
            }

            $title        = get_the_title( $vehicle_id );
            $image        = get_the_post_thumbnail(
                $vehicle_id,
                'medium',
                array( 'class' => 'crcm-top-vehicles__image' )
            );
            $seats        = get_post_meta( $vehicle_id, '_crcm_seats', true );
            $transmission = get_post_meta( $vehicle_id, '_crcm_transmission', true );
            $rate         = get_post_meta( $vehicle_id, '_crcm_daily_rate', true );
            ?>
            <div class="crcm-top-vehicles__card">
                <?php if ( $image ) : ?>
                    <div class="crcm-top-vehicles__thumb">
                        <?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>
                <div class="crcm-top-vehicles__content">
                    <h3 class="crcm-top-vehicles__title"><?php echo esc_html( $title ); ?></h3>
                    <p class="crcm-top-vehicles__meta">
                        <?php echo esc_html( $transmission ); ?> |
                        <?php echo esc_html( $seats ); ?>
                        <?php echo esc_html__( 'Seats', 'costabilerent' ); ?>
                    </p>
                    <p class="crcm-top-vehicles__rate">
                        <?php echo esc_html( sprintf( '€%s/%s', $rate, __( 'day', 'costabilerent' ) ) ); ?>
                    </p>
                </div>
            </div>
            <?php
        endforeach;
        ?>
    </div>
<?php endif; ?>

<?php
get_footer();

