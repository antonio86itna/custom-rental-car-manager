<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../inc/class-locale-manager.php';

// Stub WordPress functions used by CRCM_Locale_Manager.
function is_user_logged_in()
{
    return ! empty($GLOBALS['__test_logged_in']);
}

function get_current_user_id()
{
    return $GLOBALS['__test_user_id'] ?? 0;
}

function get_user_meta($user_id, $key, $single = true)
{
    return $GLOBALS['__test_user_meta'][ $user_id ][ $key ] ?? '';
}

function update_user_meta($user_id, $key, $value)
{
    $GLOBALS['__test_user_meta'][ $user_id ][ $key ] = $value;
}

class LocaleManagerTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['__test_user_meta'] = array();
        $GLOBALS['__test_logged_in'] = true;
        $GLOBALS['__test_user_id']   = 1;
    }

    public function testSetUserLocaleUsesPreference()
    {
        update_user_meta(1, 'crcm_preferred_language', 'it');
        $manager = new CRCM_Locale_Manager();
        $this->assertSame('it_IT', $manager->set_user_locale('en_US'));

        update_user_meta(1, 'crcm_preferred_language', 'en');
        $this->assertSame('en_US', $manager->set_user_locale('it_IT'));
    }

    public function testSetUserLocaleFallbacksToDefault()
    {
        $manager = new CRCM_Locale_Manager();
        $this->assertSame('en_US', $manager->set_user_locale('en_US'));
    }
}
