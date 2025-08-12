<?php
use PHPUnit\Framework\TestCase;

class BookingNumberTest extends TestCase {
    protected function setUp(): void {
        global $wpdb;
        $wpdb = new class() {
            public $postmeta = 'wp_postmeta';
            public $data     = array();
            public function prepare( $query, $value ) {
                return $value;
            }
            public function get_var( $value ) {
                foreach ( $this->data as $meta ) {
                    if ( $meta === $value ) {
                        return 1;
                    }
                }
                return null;
            }
        };
    }

    public function test_collision_generates_unique_number() {
        global $wpdb;
        $wpdb->data[] = 'CBR000';

        $generator = static function () {
            static $calls = 0;
            $calls++;
            return ( 1 === $calls ) ? 'CBR000' : 'CBR001';
        };

        $number = crcm_get_next_booking_number( $generator );
        $this->assertSame( 'CBR001', $number );
    }
}
