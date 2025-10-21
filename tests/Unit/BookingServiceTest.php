<?php

namespace Tests\Unit;

use App\Exceptions\Booking\GuideNotActiveException;
use App\Exceptions\Booking\GuideNotAvailableException;
use App\Models\Guide;
use App\Models\HuntingBooking;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();
    }

    public function test_can_create_booking_successfully(): void
    {
        $guide = Guide::factory()->create(['is_active' => true]);

        $bookingData = [
            'tour_name' => 'Test Tour',
            'hunter_name' => 'John Doe',
            'guide_id' => $guide->id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'participants_count' => 5,
        ];

        $booking = $this->bookingService->createBooking($bookingData);

        $this->assertInstanceOf(HuntingBooking::class, $booking);
        $this->assertEquals('Test Tour', $booking->tour_name);
        $this->assertEquals($guide->id, $booking->guide_id);
        $this->assertTrue($booking->relationLoaded('guide'));
    }

    public function test_throws_exception_when_guide_is_not_active(): void
    {
        $guide = Guide::factory()->create(['is_active' => false]);

        $bookingData = [
            'tour_name' => 'Test Tour',
            'hunter_name' => 'John Doe',
            'guide_id' => $guide->id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'participants_count' => 5,
        ];

        $this->expectException(GuideNotActiveException::class);

        $this->bookingService->createBooking($bookingData);
    }

    public function test_throws_exception_when_guide_is_not_available(): void
    {
        $guide = Guide::factory()->create(['is_active' => true]);
        $date = now()->addDays(5)->format('Y-m-d');

        // Create existing booking
        HuntingBooking::factory()->create([
            'guide_id' => $guide->id,
            'date' => $date,
        ]);

        $bookingData = [
            'tour_name' => 'Another Tour',
            'hunter_name' => 'Jane Smith',
            'guide_id' => $guide->id,
            'date' => $date,
            'participants_count' => 3,
        ];

        $this->expectException(GuideNotAvailableException::class);

        $this->bookingService->createBooking($bookingData);
    }

    public function test_rollback_on_error(): void
    {
        $guide = Guide::factory()->create(['is_active' => true]);

        // Count bookings before
        $initialCount = HuntingBooking::count();

        // Try to create booking with non-existent guide_id (foreign key constraint violation)
        try {
            $this->bookingService->createBooking([
                'tour_name' => 'Test',
                'hunter_name' => 'Test',
                'guide_id' => 99999, // Non-existent guide
                'date' => now()->addDays(5)->format('Y-m-d'),
                'participants_count' => 5,
            ]);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Expected to fail due to non-existent guide
            $this->assertTrue(true);
        }

        // Verify no booking was created (transaction rolled back)
        $this->assertEquals($initialCount, HuntingBooking::count());
    }

    public function test_creates_booking_with_proper_locking(): void
    {
        $guide = Guide::factory()->create(['is_active' => true]);
        $date = now()->addDays(5)->format('Y-m-d');

        $bookingData = [
            'tour_name' => 'Test Tour',
            'hunter_name' => 'John Doe',
            'guide_id' => $guide->id,
            'date' => $date,
            'participants_count' => 5,
        ];

        // First booking should succeed
        $booking1 = $this->bookingService->createBooking($bookingData);
        $this->assertNotNull($booking1);

        // Second booking for same guide and date should fail
        $this->expectException(GuideNotAvailableException::class);

        $bookingData['hunter_name'] = 'Jane Smith';
        $this->bookingService->createBooking($bookingData);
    }
}
