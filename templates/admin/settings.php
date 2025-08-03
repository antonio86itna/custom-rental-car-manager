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

$settings = crcm()->get_settings();

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'crcm_settings')) {
    $new_settings = array();

    // General settings
    $new_settings['company_name'] = sanitize_text_field($_POST['company_name']);
    $new_settings['currency'] = sanitize_text_field($_POST['currency']);
    $new_settings['currency_symbol'] = sanitize_text_field($_POST['currency_symbol']);

    // Booking settings
    $new_settings['free_cancellation_days'] = max(0, intval($_POST['free_cancellation_days']));
    $new_settings['late_return_extra_day'] = isset($_POST['late_return_extra_day']);
    $new_settings['late_return_time'] = sanitize_text_field($_POST['late_return_time']);

    // Payment settings
    $new_settings['stripe_publishable_key'] = sanitize_text_field($_POST['stripe_publishable_key']);
    $new_settings['stripe_secret_key'] = sanitize_text_field($_POST['stripe_secret_key']);

    // Email settings
    $new_settings['email_from_name'] = sanitize_text_field($_POST['email_from_name']);
    $new_settings['email_from_email'] = sanitize_email($_POST['email_from_email']);

    // Locations
    $locations = array();
    if (isset($_POST['locations']) && is_array($_POST['locations'])) {
        foreach ($_POST['locations'] as $location) {
            if (!empty($location['name'])) {
                $locations[] = array(
                    'name' => sanitize_text_field($location['name']),
                    'address' => sanitize_textarea_field($location['address']),
                );
            }
        }
    }
    $new_settings['locations'] = $locations;

    // Other settings
    $new_settings['show_totaliweb_credit'] = isset($_POST['show_totaliweb_credit']);

    update_option('crcm_settings', $new_settings);

    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'custom-rental-manager') . '</p></div>';

    // Refresh settings
    $settings = $new_settings;
}
?>

<div class="wrap crcm-settings">
    <h1><?php _e('Rental Manager Settings', 'custom-rental-manager'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('crcm_settings'); ?>

        <div class="crcm-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'custom-rental-manager'); ?></a>
                <a href="#booking" class="nav-tab"><?php _e('Booking', 'custom-rental-manager'); ?></a>
                <a href="#payment" class="nav-tab"><?php _e('Payment', 'custom-rental-manager'); ?></a>
                <a href="#email" class="nav-tab"><?php _e('Email', 'custom-rental-manager'); ?></a>
                <a href="#locations" class="nav-tab"><?php _e('Locations', 'custom-rental-manager'); ?></a>
            </nav>

            <!-- General Settings -->
            <div id="general" class="tab-content active">
                <h2><?php _e('General Settings', 'custom-rental-manager'); ?></h2>

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
                            <label for="currency"><?php _e('Currency', 'custom-rental-manager'); ?></label>
                        </th>
                        <td>
                            <select id="currency" name="currency">
                                <option value="EUR" <?php selected($settings['currency'], 'EUR'); ?>>EUR (€)</option>
                                <option value="USD" <?php selected($settings['currency'], 'USD'); ?>>USD ($)</option>
                                <option value="GBP" <?php selected($settings['currency'], 'GBP'); ?>>GBP (£)</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="currency_symbol"><?php _e('Currency Symbol', 'custom-rental-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="currency_symbol" name="currency_symbol" value="<?php echo esc_attr($settings['currency_symbol']); ?>" class="small-text" />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Branding', 'custom-rental-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_totaliweb_credit" value="1" <?php checked($settings['show_totaliweb_credit'], true); ?> />
                                <?php printf(__('Show "Powered by %s" credit', 'custom-rental-manager'), '<a href="' . CRCM_BRAND_URL . '" target="_blank">Totaliweb</a>'); ?>
                            </label>
                            <p class="description"><?php _e('Support the plugin development by showing a small credit link.', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Booking Settings -->
            <div id="booking" class="tab-content">
                <h2><?php _e('Booking Settings', 'custom-rental-manager'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="free_cancellation_days"><?php _e('Free Cancellation Period', 'custom-rental-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="free_cancellation_days" name="free_cancellation_days" value="<?php echo esc_attr($settings['free_cancellation_days']); ?>" min="0" max="30" class="small-text" />
                            <?php _e('days before pickup', 'custom-rental-manager'); ?>
                            <p class="description"><?php _e('Customers can cancel their booking for free within this period.', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Late Return Policy', 'custom-rental-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="late_return_extra_day" value="1" <?php checked($settings['late_return_extra_day'], true); ?> />
                                <?php _e('Charge extra day for late returns', 'custom-rental-manager'); ?>
                            </label>
                            <p class="description"><?php _e('Automatically charge an extra day if return time is after the specified hour.', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="late_return_time"><?php _e('Late Return Cutoff Time', 'custom-rental-manager'); ?></label>
                        </th>
                        <td>
                            <input type="time" id="late_return_time" name="late_return_time" value="<?php echo esc_attr($settings['late_return_time']); ?>" />
                            <p class="description"><?php _e('Returns after this time will incur an extra day charge.', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Payment Settings -->
            <div id="payment" class="tab-content">
                <h2><?php _e('Payment Settings', 'custom-rental-manager'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="stripe_publishable_key"><?php _e('Stripe Publishable Key', 'custom-rental-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="stripe_publishable_key" name="stripe_publishable_key" value="<?php echo esc_attr($settings['stripe_publishable_key']); ?>" class="regular-text code" />
                            <p class="description"><?php _e('Your Stripe publishable key (pk_...)', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="stripe_secret_key"><?php _e('Stripe Secret Key', 'custom-rental-manager'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="stripe_secret_key" name="stripe_secret_key" value="<?php echo esc_attr($settings['stripe_secret_key']); ?>" class="regular-text code" />
                            <p class="description"><?php _e('Your Stripe secret key (sk_...)', 'custom-rental-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Email Settings -->
            <div id="email" class="tab-content">
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
                </table>
            </div>

            <!-- Locations -->
            <div id="locations" class="tab-content">
                <h2><?php _e('Pickup/Return Locations', 'custom-rental-manager'); ?></h2>

                <div id="locations-container">
                    <?php if (empty($settings['locations'])): ?>
                        <div class="location-item">
                            <table class="form-table">
                                <tr>
                                    <th><label><?php _e('Location Name', 'custom-rental-manager'); ?></label></th>
                                    <td><input type="text" name="locations[0][name]" class="regular-text" /></td>
                                </tr>
                                <tr>
                                    <th><label><?php _e('Address', 'custom-rental-manager'); ?></label></th>
                                    <td>
                                        <textarea name="locations[0][address]" rows="3" class="large-text"></textarea>
                                        <button type="button" class="button remove-location"><?php _e('Remove', 'custom-rental-manager'); ?></button>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    <?php else: ?>
                        <?php foreach ($settings['locations'] as $index => $location): ?>
                            <div class="location-item">
                                <table class="form-table">
                                    <tr>
                                        <th><label><?php _e('Location Name', 'custom-rental-manager'); ?></label></th>
                                        <td><input type="text" name="locations[<?php echo $index; ?>][name]" value="<?php echo esc_attr($location['name']); ?>" class="regular-text" /></td>
                                    </tr>
                                    <tr>
                                        <th><label><?php _e('Address', 'custom-rental-manager'); ?></label></th>
                                        <td>
                                            <textarea name="locations[<?php echo $index; ?>][address]" rows="3" class="large-text"><?php echo esc_textarea($location['address']); ?></textarea>
                                            <button type="button" class="button remove-location"><?php _e('Remove', 'custom-rental-manager'); ?></button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="button" id="add-location" class="button"><?php _e('Add Location', 'custom-rental-manager'); ?></button>
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="<?php _e('Save Settings', 'custom-rental-manager'); ?>" />
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').click(function(e) {
        e.preventDefault();

        var target = $(this).attr('href');

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });

    // Add location
    $('#add-location').click(function() {
        var container = $('#locations-container');
        var index = container.find('.location-item').length;

        var html = '<div class="location-item">' +
            '<table class="form-table">' +
            '<tr>' +
            '<th><label><?php _e('Location Name', 'custom-rental-manager'); ?></label></th>' +
            '<td><input type="text" name="locations[' + index + '][name]" class="regular-text" /></td>' +
            '</tr>' +
            '<tr>' +
            '<th><label><?php _e('Address', 'custom-rental-manager'); ?></label></th>' +
            '<td>' +
            '<textarea name="locations[' + index + '][address]" rows="3" class="large-text"></textarea>' +
            '<button type="button" class="button remove-location"><?php _e('Remove', 'custom-rental-manager'); ?></button>' +
            '</td>' +
            '</tr>' +
            '</table>' +
            '</div>';

        container.append(html);
    });

    // Remove location
    $(document).on('click', '.remove-location', function() {
        $(this).closest('.location-item').remove();
    });
});
</script>

<style>
.crcm-settings-tabs {
    margin-top: 20px;
}

.tab-content {
    display: none;
    background: #fff;
    border: 1px solid #e1e1e1;
    border-top: none;
    padding: 20px;
}

.tab-content.active {
    display: block;
}

.location-item {
    border: 1px solid #e1e1e1;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 15px;
    background: #f8f9fa;
}

.location-item:last-child {
    margin-bottom: 0;
}

.remove-location {
    margin-left: 10px;
    color: #dc3545;
    border-color: #dc3545;
}

.remove-location:hover {
    background: #dc3545;
    color: #fff;
}

#add-location {
    margin-top: 10px;
}
</style>
