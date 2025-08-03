<?php
/**
 * Email Manager Class
 * 
 * Handles all email communications including booking confirmations,
 * reminders, status updates, and automated notifications.
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
        add_action('crcm_booking_created', array($this, 'send_booking_confirmation'));
        add_action('crcm_booking_status_changed', array($this, 'send_status_change_notification'), 10, 3);
        add_action('crcm_daily_reminder_check', array($this, 'send_pickup_reminders'));
        add_filter('wp_mail_from', array($this, 'set_from_email'));
        add_filter('wp_mail_from_name', array($this, 'set_from_name'));

        // Schedule daily reminder check
        if (!wp_next_scheduled('crcm_daily_reminder_check')) {
            wp_schedule_event(time(), 'daily', 'crcm_daily_reminder_check');
        }
    }

    /**
     * Send booking confirmation email
     */
    public function send_booking_confirmation($booking_id) {
        $booking = $this->get_booking_data($booking_id);

        if (!$booking || empty($booking['customer_data']['email'])) {
            return false;
        }

        $subject = sprintf(__('Booking Confirmation - %s', 'custom-rental-manager'), $booking['booking_number']);
        $message = $this->get_booking_confirmation_template($booking);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        $sent = wp_mail(
            $booking['customer_data']['email'],
            $subject,
            $message,
            $headers
        );

        if ($sent) {
            update_post_meta($booking_id, '_crcm_confirmation_email_sent', current_time('mysql'));
        }

        return $sent;
    }

    /**
     * Send status change notification
     */
    public function send_status_change_notification($booking_id, $new_status, $old_status) {
        $booking = $this->get_booking_data($booking_id);

        if (!$booking || empty($booking['customer_data']['email'])) {
            return false;
        }

        // Don't send notification for initial status set
        if (empty($old_status)) {
            return false;
        }

        $subject = sprintf(__('Booking Status Update - %s', 'custom-rental-manager'), $booking['booking_number']);
        $message = $this->get_status_change_template($booking, $new_status, $old_status);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        return wp_mail(
            $booking['customer_data']['email'],
            $subject,
            $message,
            $headers
        );
    }

    /**
     * Send pickup reminders (24 hours before)
     */
    public function send_pickup_reminders() {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $bookings = get_posts(array(
            'post_type' => 'crcm_booking',
            'post_status' => array('publish', 'private'),
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_crcm_booking_data',
                    'value' => $tomorrow,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_crcm_booking_status',
                    'value' => 'confirmed',
                    'compare' => '=',
                ),
            ),
        ));

        foreach ($bookings as $booking_post) {
            $booking_data = get_post_meta($booking_post->ID, '_crcm_booking_data', true);

            // Check if pickup date is tomorrow
            if ($booking_data && $booking_data['pickup_date'] === $tomorrow) {
                // Check if reminder already sent
                $reminder_sent = get_post_meta($booking_post->ID, '_crcm_pickup_reminder_sent', true);

                if (!$reminder_sent) {
                    $this->send_pickup_reminder($booking_post->ID);
                }
            }
        }
    }

    /**
     * Send pickup reminder email
     */
    public function send_pickup_reminder($booking_id) {
        $booking = $this->get_booking_data($booking_id);

        if (!$booking || empty($booking['customer_data']['email'])) {
            return false;
        }

        $subject = sprintf(__('Pickup Reminder - %s', 'custom-rental-manager'), $booking['booking_number']);
        $message = $this->get_pickup_reminder_template($booking);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        $sent = wp_mail(
            $booking['customer_data']['email'],
            $subject,
            $message,
            $headers
        );

        if ($sent) {
            update_post_meta($booking_id, '_crcm_pickup_reminder_sent', current_time('mysql'));
        }

        return $sent;
    }

    /**
     * Get booking confirmation email template
     */
    public function get_booking_confirmation_template($booking) {
        $vehicle = get_post($booking['booking_data']['vehicle_id']);
        $vehicle_name = $vehicle ? $vehicle->post_title : __('Unknown Vehicle', 'custom-rental-manager');

        $pickup_location = '';
        $return_location = '';

        if ($booking['booking_data']['pickup_location']) {
            $pickup_term = get_term($booking['booking_data']['pickup_location']);
            $pickup_location = $pickup_term ? $pickup_term->name : '';
        }

        if ($booking['booking_data']['return_location']) {
            $return_term = get_term($booking['booking_data']['return_location']);
            $return_location = $return_term ? $return_term->name : '';
        }

        $currency_symbol = '€';
        $company_name = 'Costabilerent';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html__('Booking Confirmation', 'custom-rental-manager'); ?></title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto;">
                <div style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                    <h1><?php echo esc_html($company_name); ?></h1>
                    <h2><?php echo esc_html__('Booking Confirmation', 'custom-rental-manager'); ?></h2>
                    <p><?php printf(__('Booking Number: %s', 'custom-rental-manager'), '<strong>' . esc_html($booking['booking_number']) . '</strong>'); ?></p>
                </div>

                <div style="background: #fff; padding: 30px; border: 1px solid #e2e8f0; border-top: none;">
                    <p><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($booking['customer_data']['first_name'])); ?></p>

                    <p><?php _e('Thank you for your booking! We have received your reservation and it is currently being processed.', 'custom-rental-manager'); ?></p>

                    <div style="background: #f8fafc; padding: 20px; border-radius: 6px; margin: 20px 0;">
                        <h3><?php _e('Booking Details', 'custom-rental-manager'); ?></h3>

                        <p><strong><?php _e('Vehicle:', 'custom-rental-manager'); ?></strong> <?php echo esc_html($vehicle_name); ?></p>
                        <p><strong><?php _e('Pickup:', 'custom-rental-manager'); ?></strong> <?php echo esc_html($booking['booking_data']['pickup_date']); ?> <?php echo esc_html($booking['booking_data']['pickup_time']); ?></p>
                        <p><strong><?php _e('Return:', 'custom-rental-manager'); ?></strong> <?php echo esc_html($booking['booking_data']['return_date']); ?> <?php echo esc_html($booking['booking_data']['return_time']); ?></p>

                        <?php if ($pickup_location): ?>
                        <p><strong><?php _e('Pickup Location:', 'custom-rental-manager'); ?></strong> <?php echo esc_html($pickup_location); ?></p>
                        <?php endif; ?>

                        <?php if ($return_location): ?>
                        <p><strong><?php _e('Return Location:', 'custom-rental-manager'); ?></strong> <?php echo esc_html($return_location); ?></p>
                        <?php endif; ?>

                        <p><strong><?php _e('Insurance:', 'custom-rental-manager'); ?></strong> <?php echo esc_html(ucfirst($booking['booking_data']['insurance_type'])); ?></p>

                        <?php if (!empty($booking['booking_data']['extras'])): ?>
                        <p><strong><?php _e('Extras:', 'custom-rental-manager'); ?></strong> <?php echo esc_html(implode(', ', $booking['booking_data']['extras'])); ?></p>
                        <?php endif; ?>

                        <p style="font-size: 18px; font-weight: bold; color: #2563eb; margin-top: 15px; padding-top: 15px; border-top: 2px solid #2563eb;">
                            <strong><?php _e('Total Amount:', 'custom-rental-manager'); ?></strong> <?php echo esc_html($currency_symbol . number_format($booking['payment_data']['total_cost'], 2)); ?>
                        </p>
                    </div>

                    <h3><?php _e('What happens next?', 'custom-rental-manager'); ?></h3>
                    <ol>
                        <li><?php _e('We will review your booking and confirm availability', 'custom-rental-manager'); ?></li>
                        <li><?php _e('You will receive a confirmation email within 24 hours', 'custom-rental-manager'); ?></li>
                        <li><?php _e('Payment instructions will be provided upon confirmation', 'custom-rental-manager'); ?></li>
                        <li><?php _e('We will send you pickup details 24 hours before your rental', 'custom-rental-manager'); ?></li>
                    </ol>

                    <p><?php _e('Thank you for choosing us for your transportation needs!', 'custom-rental-manager'); ?></p>
                </div>

                <div style="background: #f1f5f9; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #64748b;">
                    <p><strong><?php echo esc_html($company_name); ?></strong></p>
                    <p><?php _e('Ischia, Italy', 'custom-rental-manager'); ?></p>
                    <p><?php _e('Phone:', 'custom-rental-manager'); ?> +39 123 456 789</p>
                    <p><?php _e('Email:', 'custom-rental-manager'); ?> info@costabilerent.com</p>
                    <p style="margin-top: 20px; font-size: 12px; color: #94a3b8;">
                        <?php printf(__('Powered by %s', 'custom-rental-manager'), '<a href="https://totaliweb.com" style="color: #2563eb;">Totaliweb</a>'); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get pickup reminder email template
     */
    public function get_pickup_reminder_template($booking) {
        $vehicle = get_post($booking['booking_data']['vehicle_id']);
        $vehicle_name = $vehicle ? $vehicle->post_title : __('Unknown Vehicle', 'custom-rental-manager');

        $pickup_location = '';
        if ($booking['booking_data']['pickup_location']) {
            $pickup_term = get_term($booking['booking_data']['pickup_location']);
            $pickup_location = $pickup_term ? $pickup_term->name : '';
        }

        $company_name = 'Costabilerent';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html__('Pickup Reminder', 'custom-rental-manager'); ?></title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto;">
                <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                    <h1><?php echo esc_html($company_name); ?></h1>
                    <h2><?php echo esc_html__('Pickup Reminder', 'custom-rental-manager'); ?></h2>
                </div>

                <div style="background: #fff; padding: 30px; border: 1px solid #e2e8f0; border-top: none;">
                    <p><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($booking['customer_data']['first_name'])); ?></p>

                    <div style="background: #f0fdf4; border: 2px solid #059669; padding: 20px; border-radius: 6px; margin: 20px 0; text-align: center;">
                        <h3><?php _e('Your rental is tomorrow!', 'custom-rental-manager'); ?></h3>
                        <p><strong><?php printf(__('Booking Number: %s', 'custom-rental-manager'), $booking['booking_number']); ?></strong></p>
                        <p><strong><?php printf(__('Vehicle: %s', 'custom-rental-manager'), $vehicle_name); ?></strong></p>
                        <p><strong><?php printf(__('Pickup: %s at %s', 'custom-rental-manager'), $booking['booking_data']['pickup_date'], $booking['booking_data']['pickup_time']); ?></strong></p>
                        <?php if ($pickup_location): ?>
                        <p><strong><?php printf(__('Location: %s', 'custom-rental-manager'), $pickup_location); ?></strong></p>
                        <?php endif; ?>
                    </div>

                    <h3><?php _e('Pickup Checklist', 'custom-rental-manager'); ?></h3>
                    <ul>
                        <li><?php _e('Valid driving license', 'custom-rental-manager'); ?></li>
                        <li><?php _e('ID document (passport or national ID)', 'custom-rental-manager'); ?></li>
                        <li><?php _e('Credit card for security deposit', 'custom-rental-manager'); ?></li>
                    </ul>

                    <p><?php _e('We look forward to serving you tomorrow!', 'custom-rental-manager'); ?></p>
                </div>

                <div style="background: #f1f5f9; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #64748b;">
                    <p><strong><?php echo esc_html($company_name); ?></strong></p>
                    <p><?php _e('Ischia, Italy', 'custom-rental-manager'); ?></p>
                    <p style="margin-top: 20px; font-size: 12px; color: #94a3b8;">
                        <?php printf(__('Powered by %s', 'custom-rental-manager'), '<a href="https://totaliweb.com" style="color: #059669;">Totaliweb</a>'); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get status change email template
     */
    public function get_status_change_template($booking, $new_status, $old_status) {
        $vehicle = get_post($booking['booking_data']['vehicle_id']);
        $vehicle_name = $vehicle ? $vehicle->post_title : __('Unknown Vehicle', 'custom-rental-manager');

        $company_name = 'Costabilerent';

        $status_messages = array(
            'confirmed' => __('Your booking has been confirmed! We look forward to serving you.', 'custom-rental-manager'),
            'active' => __('Your rental is now active. Enjoy your ride!', 'custom-rental-manager'),
            'completed' => __('Thank you for your business! We hope you enjoyed your rental experience.', 'custom-rental-manager'),
            'cancelled' => __('Your booking has been cancelled. If you need assistance, please contact us.', 'custom-rental-manager'),
        );

        $message = isset($status_messages[$new_status]) ? $status_messages[$new_status] : sprintf(__('Your booking status has been updated to: %s', 'custom-rental-manager'), ucfirst($new_status));

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html__('Booking Status Update', 'custom-rental-manager'); ?></title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px;">
            <div style="max-width: 600px; margin: 0 auto;">
                <div style="background: #2563eb; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                    <h1><?php echo esc_html($company_name); ?></h1>
                    <h2><?php echo esc_html__('Booking Status Update', 'custom-rental-manager'); ?></h2>
                </div>

                <div style="background: #fff; padding: 30px; border: 1px solid #e2e8f0; border-top: none;">
                    <p><?php printf(__('Dear %s,', 'custom-rental-manager'), esc_html($booking['customer_data']['first_name'])); ?></p>

                    <div style="background: rgba(37, 99, 235, 0.1); border: 2px solid #2563eb; padding: 20px; border-radius: 6px; margin: 20px 0; text-align: center;">
                        <h3><?php echo esc_html($message); ?></h3>
                        <p><strong><?php printf(__('Booking Number: %s', 'custom-rental-manager'), $booking['booking_number']); ?></strong></p>
                        <p><strong><?php printf(__('Vehicle: %s', 'custom-rental-manager'), $vehicle_name); ?></strong></p>
                        <p><strong><?php printf(__('Status: %s', 'custom-rental-manager'), ucfirst($new_status)); ?></strong></p>
                    </div>

                    <p><?php _e('If you have any questions, please do not hesitate to contact us.', 'custom-rental-manager'); ?></p>
                </div>

                <div style="background: #f1f5f9; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; color: #64748b;">
                    <p><strong><?php echo esc_html($company_name); ?></strong></p>
                    <p><?php _e('Phone:', 'custom-rental-manager'); ?> +39 123 456 789</p>
                    <p style="margin-top: 20px; font-size: 12px; color: #94a3b8;">
                        <?php printf(__('Powered by %s', 'custom-rental-manager'), '<a href="https://totaliweb.com" style="color: #2563eb;">Totaliweb</a>'); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Set from email address
     */
    public function set_from_email($email) {
        return 'info@costabilerent.com';
    }

    /**
     * Set from name
     */
    public function set_from_name($name) {
        return 'Costabilerent';
    }

    /**
     * Get booking data for email
     */
    private function get_booking_data($booking_id) {
        $booking_manager = new CRCM_Booking_Manager();
        return $booking_manager->get_booking($booking_id);
    }

    /**
     * Send test email
     */
    public function send_test_email($to_email) {
        $subject = sprintf(__('Test Email from %s', 'custom-rental-manager'), 'Costabilerent');

        $message = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Test Email</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
                    <h1>Costabilerent</h1>
                    <h2>' . __('Email System Test', 'custom-rental-manager') . '</h2>
                </div>
                <div style="background: #fff; padding: 20px; border: 1px solid #e2e8f0; border-top: none;">
                    <p>' . __('This is a test email to verify that your email system is working correctly.', 'custom-rental-manager') . '</p>
                    <p>' . __('If you received this email, your email configuration is working properly!', 'custom-rental-manager') . '</p>
                    <p><strong>' . __('Timestamp:', 'custom-rental-manager') . '</strong> ' . current_time('mysql') . '</p>
                </div>
                <div style="background: #f1f5f9; padding: 15px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; color: #64748b;">
                    ' . sprintf(__('Powered by %s', 'custom-rental-manager'), '<a href="https://totaliweb.com" style="color: #2563eb;">Totaliweb</a>') . '
                </div>
            </div>
        </body>
        </html>';

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        return wp_mail($to_email, $subject, $message, $headers);
    }
}
