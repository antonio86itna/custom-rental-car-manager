<?php

/**
 * Locale Manager Class
 *
 * Handles switching the WordPress locale based on user preference.
 *
 * @package CustomRentalCarManager
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Locale manager responsible for setting locale from user meta.
 */
class CRCM_Locale_Manager
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_filter('locale', array( $this, 'set_user_locale' ));
    }

    /**
     * Filter the site locale using the user's preferred language.
     *
     * @param string $locale Default WordPress locale.
     * @return string Adjusted locale.
     */
    public function set_user_locale($locale)
    {
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            $preferred = get_user_meta(get_current_user_id(), 'crcm_preferred_language', true);
            if ('it' === $preferred) {
                return 'it_IT';
            }
            if ('en' === $preferred) {
                return 'en_US';
            }
        }
        return $locale;
    }
}
