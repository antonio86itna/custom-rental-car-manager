<?php
/**
 * Admin Settings Template
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['crcm_settings_nonce'] ?? '', 'crcm_save_settings')) {
    $settings = array();
    
    // Company settings
    $settings['company_name'] = sanitize_text_field($_POST['company_name'] ?? '');
    $settings['company_address'] = sanitize_textarea_field($_POST['company_address'] ?? '');
    $settings['company_phone'] = sanitize_text_field($_POST['company_phone'] ?? '');
    $settings['company_email'] = sanitize_email($_POST['company_email'] ?? '');
    $settings['company_website'] = esc_url_raw($_POST['company_website'] ?? '');
    
    // Currency & Pricing
    $settings['currency_symbol'] = sanitize_text_field($_POST['currency_symbol'] ?? '€');
    $settings['currency_position'] = sanitize_text_field($_POST['currency_position'] ?? 'before');
    $settings['default_tax_rate'] = floatval($_POST['default_tax_rate'] ?? 0);
    
    // Booking settings
    $settings['booking_advance_days'] = intval($_POST['booking_advance_days'] ?? 365);
    $settings['min_booking_hours'] = intval($_POST['min_booking_hours'] ?? 24);
    $settings['cancellation_hours'] = intval($_POST['cancellation_hours'] ?? 72);
    $settings['late_return_fee'] = floatval($_POST['late_return_fee'] ?? 25);
    
    // Email settings
    $settings['email_from_name'] = sanitize_text_field($_POST['email_from_name'] ?? '');
    $settings['email_from_email'] = sanitize_email($_POST['email_from_email'] ?? '');
    $settings['enable_booking_confirmation'] = isset($_POST['enable_booking_confirmation']) ? 1 : 0;
    $settings['enable_pickup_reminder'] = isset($_POST['enable_pickup_reminder']) ? 1 : 0;
    $settings['enable_admin_notifications'] = isset($_POST['enable_admin_notifications']) ? 1 : 0;
    
    // Home delivery
    $settings['enable_home_delivery'] = isset($_POST['enable_home_delivery']) ? 1 : 0;
    $settings['home_delivery_fee'] = floatval($_POST['home_delivery_fee'] ?? 25);
    $settings['home_delivery_radius'] = intval($_POST['home_delivery_radius'] ?? 20);
    
    // Payment settings
    $settings['enable_online_payment'] = isset($_POST['enable_online_payment']) ? 1 : 0;
    $settings['deposit_percentage'] = intval($_POST['deposit_percentage'] ?? 30);
    $settings['minimum_deposit'] = floatval($_POST['minimum_deposit'] ?? 200);
    
    // Branding
    $settings['show_totaliweb_credit'] = isset($_POST['show_totaliweb_credit']) ? 1 : 0;
    $settings['custom_css'] = wp_kses_post($_POST['custom_css'] ?? '');
    
    update_option('crcm_settings', $settings);
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'custom-rental-manager') . '</p></div>';
}

// Get current settings
$settings = get_option('crcm_settings', array());
$defaults = array(
    'company_name' => 'Costabilerent',
    'company_address' => 'Ischia, Italy',
    'company_phone' => '+39 123 456 789',
    'company_email' => 'info@costabilerent.com',
    'company_website' => 'https://costabilerent.com',
    'currency_symbol' => '€',
    'currency_position' => 'before',
    'default_tax_rate' => 22,
    'booking_advance_days' => 365,
    'min_booking_hours' => 24,
    'cancellation_hours' => 72,
    'late_return_fee' => 25,
    'email_from_name' => 'Costabilerent',
    'email_from_email' => 'info@costabilerent.com',
    'enable_booking_confirmation' => 1,
    'enable_pickup_reminder' => 1,
    'enable_admin_notifications' => 1,
    'enable_home_delivery' => 1,
    'home_delivery_fee' => 25,
    'home_delivery_radius' => 20,
    'enable_online_payment' => 0,
    'deposit_percentage' => 30,
    'minimum_deposit' => 200,
    'show_totaliweb_credit' => 1,
    'custom_css' => '',
);

$settings = wp_parse_args($settings, $defaults);
?>

<div class="wrap crcm-settings">
    <h1><?php _e('Rental Manager Settings', 'custom-rental-manager'); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <a href="#company" class="nav-tab nav-tab-active"><?php _e('Company', 'custom-rental-manager'); ?></a>
        <a href="#booking" class="nav-tab"><?php _e('Booking', 'custom-rental-manager'); ?></a>
        <a href="#payment" class="nav-tab"><?php _e('Payment', 'custom-rental-manager'); ?></a>
        <a href="#email" class="nav-tab"><?php _e('Email', 'custom-rental-manager'); ?></a>
        <a href="#delivery" class="nav-tab"><?php _e('Home Delivery', 'custom-rental-manager'); ?></a>
        <a href="#advanced" class="nav-tab"><?php _e('Advanced', 'custom-rental-manager'); ?></a>
    </nav>
    
    <form method="post" action="">
        <?php wp_nonce_field('crcm_save_settings', 'crcm_settings_nonce'); ?>
        
        <!-- Company Settings -->
        <div id="company" class="crcm-tab-content">
            <h2><?php _e('Company Information', 'custom-rental-manager'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="company_name"><?php _e('Company Name', 'custom-rental-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="company_name" name="company_name" value="<?php echo esc_attr($settings['company_name']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="company_address"><?php _e('Address', 'custom-rental-manager'); ?></label>
                    </th>
                    <td>
                        <textarea id="company_address" name="company_address" rows="3" class="large-text"><?php echo esc_textarea($settings['company_address']); ?></textarea>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="company_phone"><?php _e('Phone', 'custom-rental-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="company_phone" name="company_phone" value="<?php echo esc_attr($settings['company_phone']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="company_email"><?php _e('Email', 'custom-rental-manager'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="company_email" name="company_email" value="<?php echo esc_attr($settings['company_email']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="currency_symbol"><?php _e('Currency Symbol', 'custom-rental-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="currency_symbol" name="currency_symbol" value="<?php echo esc_attr($settings['currency_symbol']); ?>" class="small-text" />
                        <select name="currency_position">
                            <option value="before" <?php selected($settings['currency_position'], 'before'); ?>><?php _e('Before amount', 'custom-rental-manager'); ?></option>
                            <option value="after" <?php selected($settings['currency_position'], 'after'); ?>><?php _e('After amount', 'custom-rental-manager'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Email Settings -->
        <div id="email" class="crcm-tab-content" style="display: none;">
            <h2><?php _e('Email Settings', 'custom-rental-manager'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="email_from_name"><?php _e('From Name', 'custom-rental-manager'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="email_from_name" name="email_from_name" value="<?php echo esc_attr($settings['email_from_name']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="email_from_email"><?php _e('From Email', 'custom-rental-manager'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="email_from_email" name="email_from_email" value="<?php echo esc_attr($settings['email_from_email']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Email Notifications', 'custom-rental-manager'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_booking_confirmation" value="1" <?php checked($settings['enable_booking_confirmation']); ?> />
                            <?php _e('Send booking confirmation emails', 'custom-rental-manager'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" name="enable_pickup_reminder" value="1" <?php checked($settings['enable_pickup_reminder']); ?> />
                            <?php _e('Send pickup reminder emails (24h before)', 'custom-rental-manager'); ?>
                        </label><br>
                        
                        <label>
                            <input type="checkbox" name="enable_admin_notifications" value="1" <?php checked($settings['enable_admin_notifications']); ?> />
                            <?php _e('Send admin notifications for new bookings', 'custom-rental-manager'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Advanced Settings -->
        <div id="advanced" class="crcm-tab-content" style="display: none;">
            <h2><?php _e('Advanced Settings', 'custom-rental-manager'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Branding', 'custom-rental-manager'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="show_totaliweb_credit" value="1" <?php checked($settings['show_totaliweb_credit']); ?> />
                            <?php _e('Show "Powered by Totaliweb" credit', 'custom-rental-manager'); ?>
                        </label>
                        <p class="description"><?php _e('Support our development by showing a small credit link', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="custom_css"><?php _e('Custom CSS', 'custom-rental-manager'); ?></label>
                    </th>
                    <td>
                        <textarea id="custom_css" name="custom_css" rows="10" class="large-text code"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                        <p class="description"><?php _e('Add custom CSS to style the frontend forms and components', 'custom-rental-manager'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button(__('Save Settings', 'custom-rental-manager')); ?>
    </form>
</div>

<style>
.crcm-settings .nav-tab-wrapper {
    margin-bottom: 20px;
}

.crcm-tab-content {
    background: white;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-top: none;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.crcm-tab-content h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-table th {
    width: 200px;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.crcm-tab-content').hide();
        $(target).show();
    });
});
</script>
