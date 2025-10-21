<?php

namespace Tests\Feature;

use App\Models\Guide;
use App\Models\HuntingBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HuntingBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_booking_with_valid_data(): void
    {
        $guide = Guide::factory()->create(['is_active' => true]);

        $bookingData = [
            'tour_name' => 'Deer Hunting Tour',
            'hunter_name' => 'John Doe',
            'guide_id' => $guide->id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'participants_count' => 5,
        ];

        $response = $this->postJson('/api/bookings', $bookingData);

        $response
            ->assertStatus(201)
            ->assertJsonFragment([
                'tour_name' => 'Deer Hunting Tour',
                'hunter_name' => 'John Doe',
                'participants_count' => 5,
            ]);

        $this->assertDatabaseHas('hunting_bookings', [
            'tour_name' => 'Deer Hunting Tour',
            'hunter_name' => 'John Doe',
        ]);
    }

    public function test_cannot_create_booking_with_more_than_10_participants(): void
    {
        $guide = Guide::factory()->create(['is_active' => true]);

        $bookingData = [
            'tour_name' => 'Deer Hunting Tour',
            'hunter_name' => 'John Doe',
            'guide_id' => $guide->id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'participants_count' => 15,
        ];

        $response = $this->postJson('/api/bookings', $bookingData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['participants_count']);
    }

    public function test_cannot_book_guide_on_same_date_twice(): void
    {
        $guide = Guide::factory()->create(['is_active' => true]);
        $date = now()->addDays(5)->format('Y-m-d');

        // Create first booking
        HuntingBooking::factory()->create([
            'guide_id' => $guide->id,
            'date' => $date,
        ]);

        // Attempt to create second booking for same guide on same date
        $bookingData = [
            'tour_name' => 'Another Tour',
            'hunter_name' => 'Jane Smith',
            'guide_id' => $guide->id,
            'date' => $date,
            'participants_count' => 3,
        ];

        $response = $this->postJson('/api/bookings', $bookingData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_cannot_book_inactive_guide(): void
    {
        $guide = Guide::factory()->create(['is_active' => false]);

        $bookingData = [
            'tour_name' => 'Deer Hunting Tour',
            'hunter_name' => 'John Doe',
            'guide_id' => $guide->id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'participants_count' => 5,
        ];

        $response = $this->postJson('/api/bookings', $bookingData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guide_id']);
    }

    public function test_cannot_book_in_the_past(): void
    {
        $guide = Guide::factory()->create(['is_active' => true]);

        $bookingData = [
            'tour_name' => 'Deer Hunting Tour',
            'hunter_name' => 'John Doe',
            'guide_id' => $guide->id,
            'date' => now()->subDays(1)->format('Y-m-d'),
            'participants_count' => 5,
        ];

        $response = $this->postJson('/api/bookings', $bookingData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_booking_requires_all_fields(): void
    {
        $response = $this->postJson('/api/bookings', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'tour_name',
                'hunter_name',
                'guide_id',
                'date',
                'participants_count',
            ]);
    }
}
