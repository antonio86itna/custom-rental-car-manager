<?php
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $errors = array();

        public function __construct($code = '', $message = '') {
            if ($code) {
                $this->errors[$code][] = $message;
            }
        }

        public function get_error_message($code = '') {
            if ($code && isset($this->errors[$code][0])) {
                return $this->errors[$code][0];
            }

            $messages = array();
            foreach ($this->errors as $error) {
                $messages[] = $error[0];
            }
            return implode(', ', $messages);
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('__')) {
    function __($text, $domain = null) {
        return $text;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return filter_var($str, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return trim($str);
    }
}

if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('add_action')) {
    function add_action() {}
}

if (!function_exists('add_filter')) {
    function add_filter() {}
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value)
    {
        return $value;
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook) {
        return false;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook) {
        return true;
    }
}

