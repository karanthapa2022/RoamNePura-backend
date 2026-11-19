<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use App\Models\Destination;
use App\Models\User;
use Tests\TestCase;

class DestinationTest extends TestCase
{
    use RefreshDatabase;

    // ─── PUBLIC ────────────────────────────────────────────────────────────

    public function test_public_can_list_published_destinations(): void
    {
        Destination::factory()->count(3)->create(['is_published' => true]);
        Destination::factory()->count(2)->create(['is_published' => false]);

        $response = $this->getJson('/api/destinations');
        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_public_can_view_destination_by_slug(): void
    {
        Destination::factory()->create([
            'is_published' => true,
            'slug'         => 'kathmandu',
            'name'         => 'Kathmandu',
        ]);

        $response = $this->getJson('/api/destinations/kathmandu');
        $response->assertStatus(200)
                 ->assertJsonPath('name', 'Kathmandu');
    }

    public function test_public_gets_404_for_unpublished_destination(): void
    {
        Destination::factory()->create([
            'is_published' => false,
            'slug'         => 'hidden-place',
        ]);

        $response = $this->getJson('/api/destinations/hidden-place');
        $response->assertStatus(404);
    }

    // ─── ADMIN CRUD ────────────────────────────────────────────────────────

    public function test_admin_can_create_destination(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/admin/destinations', [
            'name'        => 'Pokhara',
            'slug'        => 'pokhara',
            'description' => 'Beautiful lakeside city.',
            'location'    => 'Gandaki Province',
            'is_published'=> false,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('destinations', ['slug' => 'pokhara']);
    }

    public function test_destination_slug_must_be_unique(): void
    {
        Destination::factory()->create(['slug' => 'pokhara']);
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/admin/destinations', [
            'name'        => 'Pokhara 2',
            'slug'        => 'pokhara',
            'description' => 'Duplicate slug attempt',
            'location'    => 'Nepal',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['slug']);
    }

    public function test_admin_can_toggle_destination_publish(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;
        $dest  = Destination::factory()->create(['is_published' => false]);

        $response = $this->withToken($token)
                         ->patchJson("/api/admin/destinations/{$dest->id}/toggle-publish");

        $response->assertStatus(200);
        $this->assertDatabaseHas('destinations', ['id' => $dest->id, 'is_published' => true]);
    }

    public function test_admin_can_delete_destination(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;
        $dest  = Destination::factory()->create();

        $response = $this->withToken($token)->deleteJson("/api/admin/destinations/{$dest->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('destinations', ['id' => $dest->id]);
    }

    // ─── SECURITY ─────────────────────────────────────────────────────────

    public function test_guest_cannot_create_destination(): void
    {
        $response = $this->postJson('/api/admin/destinations', ['name' => 'Test']);
        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_delete_destination(): void
    {
        $user  = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('test')->plainTextToken;
        $dest  = Destination::factory()->create();

        $response = $this->withToken($token)->deleteJson("/api/admin/destinations/{$dest->id}");
        $response->assertStatus(403);
    }

    // ─── COORDINATE VALIDATION ─────────────────────────────────────────────

    public function test_destination_accepts_valid_coordinates(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/admin/destinations', [
            'name'        => 'Everest Region',
            'slug'        => 'everest-region',
            'description' => 'High altitude.',
            'location'    => 'Khumbu',
            'latitude'    => 27.9881,
            'longitude'   => 86.9250,
            'is_published'=> false,
        ]);

        $response->assertStatus(201);
    }
}
