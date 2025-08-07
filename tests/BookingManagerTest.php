<?php
use PHPUnit\Framework\TestCase;

class BookingManagerTest extends TestCase {
    protected $manager;

    protected function setUp(): void {
        $this->manager = new CRCM_Booking_Manager();
    }

    public function test_create_booking_invalid_vehicle_returns_wp_error() {
        $result = $this->manager->create_booking(array(
            'vehicle_id' => 0,
            'pickup_date' => '2024-01-01',
            'return_date' => '2024-01-02',
            'customer_data' => array(
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'email'      => 'john@example.com',
            ),
        ));
        $this->assertTrue(is_wp_error($result));
    }

    public function test_create_booking_invalid_dates_returns_wp_error() {
        $result = $this->manager->create_booking(array(
            'vehicle_id' => 1,
            'pickup_date' => '2024-01-05',
            'return_date' => '2024-01-01',
            'customer_data' => array(
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'email'      => 'john@example.com',
            ),
        ));
        $this->assertTrue(is_wp_error($result));
    }

    public function test_get_booking_invalid_id_returns_wp_error() {
        $result = $this->manager->get_booking(0);
        $this->assertTrue(is_wp_error($result));
    }
}

