<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Inquiry;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    // ─── PUBLIC INQUIRY SUBMISSION ─────────────────────────────────────────

    public function test_public_can_submit_inquiry(): void
    {
        $response = $this->postJson('/api/inquiries', [
            'name'    => 'Ram Bahadur',
            'email'   => 'ram@example.com',
            'message' => 'I want to know more about Everest trek.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inquiries', ['email' => 'ram@example.com']);
    }

    public function test_inquiry_requires_name_email_message(): void
    {
        $response = $this->postJson('/api/inquiries', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'message']);
    }

    public function test_inquiry_rejects_invalid_email(): void
    {
        $response = $this->postJson('/api/inquiries', [
            'name'    => 'Test',
            'email'   => 'bad-email',
            'message' => 'Hello',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_inquiry_message_cannot_be_empty_string(): void
    {
        $response = $this->postJson('/api/inquiries', [
            'name'    => 'Test User',
            'email'   => 'test@example.com',
            'message' => '',
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['message']);
    }

    // ─── ADMIN INQUIRY MANAGEMENT ──────────────────────────────────────────

    public function test_admin_can_list_inquiries(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $token = $admin->createToken('test')->plainTextToken;
        Inquiry::factory()->count(5)->create();

        $response = $this->withToken($token)->getJson('/api/admin/inquiries');
        $response->assertStatus(200);
    }

    public function test_admin_can_update_inquiry_status(): void
    {
        $admin   = User::factory()->create(['is_admin' => true]);
        $token   = $admin->createToken('test')->plainTextToken;
        $inquiry = Inquiry::factory()->create(['status' => 'new']);

        $response = $this->withToken($token)
                         ->patchJson("/api/admin/inquiries/{$inquiry->id}/status", [
                             'status' => 'read',
                         ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inquiries', ['id' => $inquiry->id, 'status' => 'read']);
    }

    public function test_admin_cannot_set_invalid_inquiry_status(): void
    {
        $admin   = User::factory()->create(['is_admin' => true]);
        $token   = $admin->createToken('test')->plainTextToken;
        $inquiry = Inquiry::factory()->create();

        $response = $this->withToken($token)
                         ->patchJson("/api/admin/inquiries/{$inquiry->id}/status", [
                             'status' => 'invalid_status',
                         ]);

        $response->assertStatus(422);
    }

    // ─── SECURITY ─────────────────────────────────────────────────────────

    public function test_guest_cannot_list_inquiries(): void
    {
        $response = $this->getJson('/api/admin/inquiries');
        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_list_inquiries(): void
    {
        $user  = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/admin/inquiries');
        $response->assertStatus(403);
    }

    // ─── SECURITY: SQL Injection attempt ──────────────────────────────────

    public function test_inquiry_handles_sql_injection_in_name(): void
    {
        $this->postJson('/api/inquiries', [
            'name'    => "'; DROP TABLE inquiries; --",
            'email'   => 'hack@example.com',
            'message' => 'Trying injection',
        ]);

        $count = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='inquiries'");
        $this->assertNotEmpty($count, 'inquiries table was dropped — SQL injection vulnerability detected!');
    }
}
