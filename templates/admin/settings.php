<?php
/**
 * Admin Settings Template
 *
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap crcm-settings">
    <h1><?php _e( 'Rental Manager Settings', 'custom-rental-manager' ); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="#company" class="nav-tab nav-tab-active"><?php _e( 'Company', 'custom-rental-manager' ); ?></a>
        <a href="#booking" class="nav-tab"><?php _e( 'Booking', 'custom-rental-manager' ); ?></a>
        <a href="#payment" class="nav-tab"><?php _e( 'Payment', 'custom-rental-manager' ); ?></a>
        <a href="#email" class="nav-tab"><?php _e( 'Email', 'custom-rental-manager' ); ?></a>
        <a href="#delivery" class="nav-tab"><?php _e( 'Home Delivery', 'custom-rental-manager' ); ?></a>
        <a href="#advanced" class="nav-tab"><?php _e( 'Advanced', 'custom-rental-manager' ); ?></a>
    </nav>

    <form method="post" action="options.php">
        <?php settings_fields( 'crcm_settings_group' ); ?>

        <div id="company" class="crcm-tab-content">
            <h2><?php _e( 'Company Information', 'custom-rental-manager' ); ?></h2>
            <table class="form-table">
                <?php do_settings_fields( 'crcm-settings', 'crcm_company_section' ); ?>
            </table>
        </div>

        <div id="booking" class="crcm-tab-content" style="display: none;">
            <h2><?php _e( 'Booking Settings', 'custom-rental-manager' ); ?></h2>
            <table class="form-table">
                <?php do_settings_fields( 'crcm-settings', 'crcm_booking_section' ); ?>
            </table>
        </div>

        <div id="payment" class="crcm-tab-content" style="display: none;">
            <h2><?php _e( 'Payment Settings', 'custom-rental-manager' ); ?></h2>
            <table class="form-table">
                <?php do_settings_fields( 'crcm-settings', 'crcm_payment_section' ); ?>
            </table>
        </div>

        <div id="email" class="crcm-tab-content" style="display: none;">
            <h2><?php _e( 'Email Settings', 'custom-rental-manager' ); ?></h2>
            <table class="form-table">
                <?php do_settings_fields( 'crcm-settings', 'crcm_email_section' ); ?>
            </table>
        </div>

        <div id="delivery" class="crcm-tab-content" style="display: none;">
            <h2><?php _e( 'Home Delivery Settings', 'custom-rental-manager' ); ?></h2>
            <table class="form-table">
                <?php do_settings_fields( 'crcm-settings', 'crcm_delivery_section' ); ?>
            </table>
        </div>

        <div id="advanced" class="crcm-tab-content" style="display: none;">
            <h2><?php _e( 'Advanced Settings', 'custom-rental-manager' ); ?></h2>
            <table class="form-table">
                <?php do_settings_fields( 'crcm-settings', 'crcm_advanced_section' ); ?>
            </table>
        </div>

        <?php submit_button( __( 'Save Settings', 'custom-rental-manager' ) ); ?>
    </form>
</div>
