<?php

namespace Tests\Feature;

use App\Models\Guide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_guides(): void
    {
        // Create active and inactive guides
        Guide::factory()->create(['name' => 'Active Guide', 'is_active' => true, 'experience_years' => 5]);
        Guide::factory()->create(['name' => 'Inactive Guide', 'is_active' => false, 'experience_years' => 3]);

        $response = $this->getJson('/api/guides');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Active Guide'])
            ->assertJsonMissing(['name' => 'Inactive Guide']);
    }

    public function test_can_filter_guides_by_minimum_experience(): void
    {
        Guide::factory()->create(['name' => 'Experienced Guide', 'experience_years' => 10, 'is_active' => true]);
        Guide::factory()->create(['name' => 'Junior Guide', 'experience_years' => 2, 'is_active' => true]);
        Guide::factory()->create(['name' => 'Mid Guide', 'experience_years' => 5, 'is_active' => true]);

        $response = $this->getJson('/api/guides?min_experience=5');

        $response
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Experienced Guide'])
            ->assertJsonFragment(['name' => 'Mid Guide'])
            ->assertJsonMissing(['name' => 'Junior Guide']);
    }

    public function test_guides_are_ordered_by_experience_desc(): void
    {
        Guide::factory()->create(['experience_years' => 5, 'is_active' => true]);
        Guide::factory()->create(['experience_years' => 10, 'is_active' => true]);
        Guide::factory()->create(['experience_years' => 3, 'is_active' => true]);

        $response = $this->getJson('/api/guides');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(10, $data[0]['experience_years']);
        $this->assertEquals(5, $data[1]['experience_years']);
        $this->assertEquals(3, $data[2]['experience_years']);
    }
}
