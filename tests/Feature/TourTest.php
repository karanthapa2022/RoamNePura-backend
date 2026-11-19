<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Models\Tour;
use App\Models\User;
use Tests\TestCase;

class TourTest extends TestCase
{
    use RefreshDatabase;

    // ─── PUBLIC TOUR LISTING ───────────────────────────────────────────────

    public function test_public_can_list_published_tours(): void
    {
        Tour::factory()->count(2)->create(['is_published' => true]);
        Tour::factory()->count(2)->create(['is_published' => false]);

        $response = $this->getJson('/api/tours');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    public function test_public_cannot_see_unpublished_tours(): void
    {
        Tour::factory()->create(['is_published' => false, 'slug' => 'secret-tour']);

        $response = $this->getJson('/api/tours/secret-tour');
        $response->assertStatus(404);
    }

    public function test_public_can_view_published_tour_by_slug(): void
    {
        Tour::factory()->create([
            'is_published' => true,
            'slug'         => 'everest-base-camp',
            'name'         => 'Everest Base Camp Trek',
        ]);

        $response = $this->getJson('/api/tours/everest-base-camp');

        $response->assertStatus(200)
                 ->assertJsonPath('name', 'Everest Base Camp Trek')
                 ->assertJsonPath('slug', 'everest-base-camp');
    }

    public function test_tour_detail_returns_404_for_missing_slug(): void
    {
        $response = $this->getJson('/api/tours/nonexistent-tour');
        $response->assertStatus(404);
    }

    // ─── ADMIN TOUR CRUD ───────────────────────────────────────────────────

    public function test_admin_can_create_tour(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson('/api/admin/tours', [
                             'name'        => 'New Trek',
                             'slug'        => 'new-trek',
                             'description' => 'A great trek.',
                             'duration'    => '7 Days',
                             'difficulty'  => 'Moderate',
                             'price'       => 1500,
                             'is_published'=> false,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('tour.slug', 'new-trek');

        $this->assertDatabaseHas('tours', ['slug' => 'new-trek']);
    }

    public function test_admin_cannot_create_tour_with_duplicate_slug(): void
    {
        Tour::factory()->create(['slug' => 'duplicate-slug']);

        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson('/api/admin/tours', [
                             'name'        => 'Another Tour',
                             'slug'        => 'duplicate-slug',
                             'description' => 'Desc',
                             'duration'    => '5 Days',
                             'difficulty'  => 'Easy',
                             'price'       => 500,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['slug']);
    }

    public function test_admin_can_toggle_tour_publish(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;
        $tour  = Tour::factory()->create(['is_published' => false]);

        $response = $this->withToken($token)
                         ->patchJson("/api/admin/tours/{$tour->id}/toggle-publish");

        $response->assertStatus(200);
        $this->assertDatabaseHas('tours', ['id' => $tour->id, 'is_published' => true]);
    }

    public function test_admin_can_delete_tour(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;
        $tour  = Tour::factory()->create();

        $response = $this->withToken($token)
                         ->deleteJson("/api/admin/tours/{$tour->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tours', ['id' => $tour->id]);
    }

    public function test_tour_requires_name_slug_description_duration_difficulty_price(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson('/api/admin/tours', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'slug', 'description', 'duration', 'difficulty', 'price']);
    }

    public function test_tour_price_must_be_non_negative(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->postJson('/api/admin/tours', [
                             'name'        => 'Cheap Tour',
                             'slug'        => 'cheap-tour',
                             'description' => 'Desc',
                             'duration'    => '1 Day',
                             'difficulty'  => 'Easy',
                             'price'       => -100,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['price']);
    }

    // ─── SECURITY ─────────────────────────────────────────────────────────

    public function test_guest_cannot_create_tour(): void
    {
        $response = $this->postJson('/api/admin/tours', [
            'name' => 'Hacked Tour',
            'slug' => 'hacked',
        ]);
        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_delete_tour(): void
    {
        $user  = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('test')->plainTextToken;
        $tour  = Tour::factory()->create();

        $response = $this->withToken($token)
                         ->deleteJson("/api/admin/tours/{$tour->id}");

        $response->assertStatus(403);
    }
}
