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

        // Now returns 409 Conflict instead of 422 due to Service layer
        $response
            ->assertStatus(409)
            ->assertJson([
                'message' => 'The selected guide is not available on this date.'
            ])
            ->assertJsonPath('errors.date.0', 'The selected guide is not available on this date.');
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

    public function test_unique_constraint_prevents_double_booking(): void
    {
        $guide = Guide::factory()->create(['is_active' => true]);
        $date = now()->addDays(5)->format('Y-m-d');

        // Create first booking via API
        $bookingData = [
            'tour_name' => 'First Tour',
            'hunter_name' => 'John Doe',
            'guide_id' => $guide->id,
            'date' => $date,
            'participants_count' => 5,
        ];

        $response1 = $this->postJson('/api/bookings', $bookingData);
        $response1->assertStatus(201);

        // Verify first booking was created
        $this->assertDatabaseHas('hunting_bookings', [
            'guide_id' => $guide->id,
            'tour_name' => 'First Tour'
        ]);

        // Try to create second booking for same guide and date
        $bookingData['tour_name'] = 'Second Tour';
        $bookingData['hunter_name'] = 'Jane Smith';

        $response2 = $this->postJson('/api/bookings', $bookingData);

        // Should fail with conflict
        $response2->assertStatus(409);

        // Verify second booking was NOT created
        $this->assertDatabaseMissing('hunting_bookings', [
            'guide_id' => $guide->id,
            'tour_name' => 'Second Tour'
        ]);
    }

    public function test_transaction_rollback_on_concurrent_bookings(): void
    {
        $guide = Guide::factory()->create(['is_active' => true]);
        $date = now()->addDays(5)->format('Y-m-d');

        $bookingData = [
            'tour_name' => 'Concurrent Tour',
            'hunter_name' => 'Test User',
            'guide_id' => $guide->id,
            'date' => $date,
            'participants_count' => 5,
        ];

        // First booking
        $response1 = $this->postJson('/api/bookings', $bookingData);
        $response1->assertStatus(201);

        // Verify first booking was created
        $this->assertDatabaseHas('hunting_bookings', [
            'guide_id' => $guide->id,
            'hunter_name' => 'Test User'
        ]);

        // Second concurrent attempt
        $bookingData['hunter_name'] = 'Another User';
        $response2 = $this->postJson('/api/bookings', $bookingData);

        // Should be rejected
        $this->assertContains($response2->status(), [409, 422]);

        // Verify second booking was rejected
        $this->assertDatabaseMissing('hunting_bookings', [
            'guide_id' => $guide->id,
            'hunter_name' => 'Another User'
        ]);
    }

    public function test_returns_proper_error_format(): void
    {
        $guide = Guide::factory()->create(['is_active' => false]);

        $bookingData = [
            'tour_name' => 'Test Tour',
            'hunter_name' => 'John Doe',
            'guide_id' => $guide->id,
            'date' => now()->addDays(5)->format('Y-m-d'),
            'participants_count' => 5,
        ];

        $response = $this->postJson('/api/bookings', $bookingData);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['guide_id']
            ]);
    }
}
