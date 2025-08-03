<?php
/**
 * Email Manager Class
 * 
 * Handles all email communications including booking confirmations,
 * status updates, reminders, and automated notifications.
 * 
 * @package CustomRentalCarManager
 * @author Totaliweb
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class CRCM_Email_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('crcm_booking_status_changed', array($this, 'send_status_change_email'), 10, 3);
        add_action('crcm_booking_created', array($this, 'send_booking_confirmation_email'));
        add_action('crcm_send_booking_reminder', array($this, 'send_booking_reminder_email'));
    }

    /**
     * Send booking confirmation email
     */
    public function send_booking_confirmation_email($booking_id) {
        $booking_manager = new CRCM_Booking_Manager();
        $booking = $booking_manager->get_booking($booking_id);

        if (!$booking) {
            return false;
        }

        $customer_email = $booking['customer_data']['email'];
        $customer_name = $booking['customer_data']['first_name'] . ' ' . $booking['customer_data']['last_name'];

        $subject = sprintf(__('Booking Confirmation - %s', 'custom-rental-manager'), $booking['booking_number']);

        $message = $this->get_email_template('booking-confirmation', array(
            'booking' => $booking,
            'customer_name' => $customer_name,
        ));

        // Send to customer
        $sent_customer = $this->send_email($customer_email, $subject, $message);

        // Send copy to admin
        $admin_email = crcm()->get_setting('email_from_email', get_option('admin_email'));
        $admin_subject = sprintf(__('[New Booking] %s - %s', 'custom-rental-manager'), $booking['booking_number'], $customer_name);
        $sent_admin = $this->send_email($admin_email, $admin_subject, $message);

        return $sent_customer && $sent_admin;
    }

    /**
     * Send booking status change email
     */
    public function send_status_change_email($booking_id, $new_status, $old_status) {
        $booking_manager = new CRCM_Booking_Manager();
        $booking = $booking_manager->get_booking($booking_id);

        if (!$booking) {
            return false;
        }

        $customer_email = $booking['customer_data']['email'];
        $customer_name = $booking['customer_data']['first_name'] . ' ' . $booking['customer_data']['last_name'];

        $status_labels = array(
            'pending' => __('Pending', 'custom-rental-manager'),
            'confirmed' => __('Confirmed', 'custom-rental-manager'),
            'active' => __('Active', 'custom-rental-manager'),
            'completed' => __('Completed', 'custom-rental-manager'),
            'cancelled' => __('Cancelled', 'custom-rental-manager'),
            'refunded' => __('Refunded', 'custom-rental-manager'),
        );

        $subject = sprintf(__('Booking Status Update - %s', 'custom-rental-manager'), $booking['booking_number']);

        $message = $this->get_email_template('status-change', array(
            'booking' => $booking,
            'customer_name' => $customer_name,
            'old_status' => $status_labels[$old_status] ?? $old_status,
            'new_status' => $status_labels[$new_status] ?? $new_status,
        ));

        return $this->send_email($customer_email, $subject, $message);
    }

    /**
     * Send booking reminder email
     */
    public function send_booking_reminder_email($booking_id) {
        $booking_manager = new CRCM_Booking_Manager();
        $booking = $booking_manager->get_booking($booking_id);

        if (!$booking) {
            return false;
        }

        $customer_email = $booking['customer_data']['email'];
        $customer_name = $booking['customer_data']['first_name'] . ' ' . $booking['customer_data']['last_name'];

        $subject = sprintf(__('Pickup Reminder - %s', 'custom-rental-manager'), $booking['booking_number']);

        $message = $this->get_email_template('pickup-reminder', array(
            'booking' => $booking,
            'customer_name' => $customer_name,
        ));

        return $this->send_email($customer_email, $subject, $message);
    }

    /**
     * Send email using WordPress mail function
     */
    public function send_email($to, $subject, $message, $headers = array()) {
        $from_name = crcm()->get_setting('email_from_name', get_bloginfo('name'));
        $from_email = crcm()->get_setting('email_from_email', get_option('admin_email'));

        $default_headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        );

        $headers = array_merge($default_headers, $headers);

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Get email template
     */
    public function get_email_template($template, $vars = array()) {
        $templates = array(
            'booking-confirmation' => $this->get_booking_confirmation_template($vars),
            'status-change' => $this->get_status_change_template($vars),
            'pickup-reminder' => $this->get_pickup_reminder_template($vars),
        );

        return $templates[$template] ?? '';
    }

    /**
     * Booking confirmation email template
     */
    private function get_booking_confirmation_template($vars) {
        $booking = $vars['booking'];
        $customer_name = $vars['customer_name'];
        $company_name = crcm()->get_setting('company_name', 'Costabilerent');
        $currency_symbol = crcm()->get_setting('currency_symbol', '€');

        $vehicle = get_post($booking['booking_data']['vehicle_id']);
        $vehicle_name = $vehicle ? $vehicle->post_title : __('Vehicle', 'custom-rental-manager');

        $pickup_date = date_i18n('F j, Y', strtotime($booking['booking_data']['pickup_date']));
        $return_date = date_i18n('F j, Y', strtotime($booking['booking_data']['return_date']));

        $pickup_location = '';
        $return_location = '';

        if ($booking['booking_data']['home_delivery']) {
            $pickup_location = __('Home Delivery', 'custom-rental-manager');
            $return_location = __('Home Pickup', 'custom-rental-manager');
        } else {
            $pickup_term = get_term($booking['booking_data']['pickup_location']);
            $return_term = get_term($booking['booking_data']['return_location']);
            $pickup_location = $pickup_term ? $pickup_term->name : '';
            $return_location = $return_term ? $return_term->name : '';
        }

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Booking Confirmation', 'custom-rental-manager'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .booking-details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
                .detail-label { font-weight: bold; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .btn { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
                .totaliweb-credit { font-size: 11px; color: #999; margin-top: 10px; }
                .totaliweb-credit a { color: #667eea; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php echo esc_html($company_name); ?></h1>
                    <p><?php _e('Booking Confirmation', 'custom-rental-manager'); ?></p>
                </div>

                <div class="content">
                    <h2><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($customer_name)); ?></h2>

                    <p><?php printf(__('Thank you for your booking! Your reservation has been confirmed with booking number <strong>%s</strong>.', 'custom-rental-manager'), $booking['booking_number']); ?></p>

                    <div class="booking-details">
                        <h3><?php _e('Booking Details', 'custom-rental-manager'); ?></h3>

                        <div class="detail-row">
                            <span class="detail-label"><?php _e('Vehicle:', 'custom-rental-manager'); ?></span>
                            <span><?php echo esc_html($vehicle_name); ?></span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label"><?php _e('Pickup Date:', 'custom-rental-manager'); ?></span>
                            <span><?php echo esc_html($pickup_date); ?> <?php echo esc_html($booking['booking_data']['pickup_time']); ?></span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label"><?php _e('Return Date:', 'custom-rental-manager'); ?></span>
                            <span><?php echo esc_html($return_date); ?> <?php echo esc_html($booking['booking_data']['return_time']); ?></span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label"><?php _e('Pickup Location:', 'custom-rental-manager'); ?></span>
                            <span><?php echo esc_html($pickup_location); ?></span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label"><?php _e('Return Location:', 'custom-rental-manager'); ?></span>
                            <span><?php echo esc_html($return_location); ?></span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label"><?php _e('Total Cost:', 'custom-rental-manager'); ?></span>
                            <span><strong><?php echo $currency_symbol . number_format($booking['payment_data']['total_cost'], 2); ?></strong></span>
                        </div>
                    </div>

                    <p><?php _e('We will contact you 24 hours before your pickup date to confirm the details and provide you with specific pickup instructions.', 'custom-rental-manager'); ?></p>

                    <p><?php _e('If you have any questions or need to make changes to your booking, please contact us as soon as possible.', 'custom-rental-manager'); ?></p>

                    <p><?php _e('We look forward to serving you!', 'custom-rental-manager'); ?></p>

                    <p><?php printf(__('Best regards,<br>The %s Team', 'custom-rental-manager'), esc_html($company_name)); ?></p>
                </div>

                <div class="footer">
                    <p><?php echo esc_html($company_name); ?> - <?php _e('Your trusted car rental in Ischia', 'custom-rental-manager'); ?></p>
                    <?php if (crcm()->get_setting('show_totaliweb_credit', true)): ?>
                    <div class="totaliweb-credit">
                        <?php _e('Powered by', 'custom-rental-manager'); ?> <a href="<?php echo CRCM_BRAND_URL; ?>" target="_blank">Totaliweb</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Status change email template
     */
    private function get_status_change_template($vars) {
        $booking = $vars['booking'];
        $customer_name = $vars['customer_name'];
        $old_status = $vars['old_status'];
        $new_status = $vars['new_status'];
        $company_name = crcm()->get_setting('company_name', 'Costabilerent');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Booking Status Update', 'custom-rental-manager'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .status-change { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .totaliweb-credit { font-size: 11px; color: #999; margin-top: 10px; }
                .totaliweb-credit a { color: #667eea; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php echo esc_html($company_name); ?></h1>
                    <p><?php _e('Booking Status Update', 'custom-rental-manager'); ?></p>
                </div>

                <div class="content">
                    <h2><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($customer_name)); ?></h2>

                    <p><?php printf(__('We wanted to inform you that the status of your booking %s has been updated.', 'custom-rental-manager'), '<strong>' . $booking['booking_number'] . '</strong>'); ?></p>

                    <div class="status-change">
                        <p><?php printf(__('Status changed from <strong>%s</strong> to <strong>%s</strong>', 'custom-rental-manager'), esc_html($old_status), esc_html($new_status)); ?></p>
                    </div>

                    <p><?php _e('If you have any questions about this status change, please don't hesitate to contact us.', 'custom-rental-manager'); ?></p>

                    <p><?php printf(__('Best regards,<br>The %s Team', 'custom-rental-manager'), esc_html($company_name)); ?></p>
                </div>

                <div class="footer">
                    <p><?php echo esc_html($company_name); ?> - <?php _e('Your trusted car rental in Ischia', 'custom-rental-manager'); ?></p>
                    <?php if (crcm()->get_setting('show_totaliweb_credit', true)): ?>
                    <div class="totaliweb-credit">
                        <?php _e('Powered by', 'custom-rental-manager'); ?> <a href="<?php echo CRCM_BRAND_URL; ?>" target="_blank">Totaliweb</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Pickup reminder email template
     */
    private function get_pickup_reminder_template($vars) {
        $booking = $vars['booking'];
        $customer_name = $vars['customer_name'];
        $company_name = crcm()->get_setting('company_name', 'Costabilerent');

        $vehicle = get_post($booking['booking_data']['vehicle_id']);
        $vehicle_name = $vehicle ? $vehicle->post_title : __('Vehicle', 'custom-rental-manager');

        $pickup_date = date_i18n('F j, Y', strtotime($booking['booking_data']['pickup_date']));

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Pickup Reminder', 'custom-rental-manager'); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .reminder-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .totaliweb-credit { font-size: 11px; color: #999; margin-top: 10px; }
                .totaliweb-credit a { color: #667eea; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php echo esc_html($company_name); ?></h1>
                    <p><?php _e('Pickup Reminder', 'custom-rental-manager'); ?></p>
                </div>

                <div class="content">
                    <h2><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($customer_name)); ?></h2>

                    <div class="reminder-box">
                        <h3><?php _e('Your rental pickup is tomorrow!', 'custom-rental-manager'); ?></h3>
                        <p><?php printf(__('Don't forget about your %s pickup on %s.', 'custom-rental-manager'), '<strong>' . esc_html($vehicle_name) . '</strong>', '<strong>' . esc_html($pickup_date) . '</strong>'); ?></p>
                    </div>

                    <h3><?php _e('What to bring:', 'custom-rental-manager'); ?></h3>
                    <ul>
                        <li><?php _e('Valid driver's license', 'custom-rental-manager'); ?></li>
                        <li><?php _e('Credit card for security deposit', 'custom-rental-manager'); ?></li>
                        <li><?php _e('Booking confirmation (this email)', 'custom-rental-manager'); ?></li>
                    </ul>

                    <p><?php _e('If you need to make any changes or have questions, please contact us as soon as possible.', 'custom-rental-manager'); ?></p>

                    <p><?php _e('We look forward to seeing you tomorrow!', 'custom-rental-manager'); ?></p>

                    <p><?php printf(__('Best regards,<br>The %s Team', 'custom-rental-manager'), esc_html($company_name)); ?></p>
                </div>

                <div class="footer">
                    <p><?php echo esc_html($company_name); ?> - <?php _e('Your trusted car rental in Ischia', 'custom-rental-manager'); ?></p>
                    <?php if (crcm()->get_setting('show_totaliweb_credit', true)): ?>
                    <div class="totaliweb-credit">
                        <?php _e('Powered by', 'custom-rental-manager'); ?> <a href="<?php echo CRCM_BRAND_URL; ?>" target="_blank">Totaliweb</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
