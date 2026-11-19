<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingStatusUpdated;
use App\Notifications\AdminActivityNotification;
use App\Models\User;
use App\Models\Booking;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    // ─── UNIT / FUNCTIONAL: Store Booking ──────────────────────────────────

    public function test_public_can_create_booking_with_valid_data(): void
    {
        Notification::fake();

        $payload = [
            'name'        => 'Karan Thapa',
            'email'       => 'karan@example.com',
            'phone'       => '+977-9800000000',
            'tour'        => 'Everest Base Camp',
            'travel_date' => now()->addDays(30)->toDateString(),
            'travelers'   => 2,
            'message'     => 'Looking forward to the trek!',
        ];

        $response = $this->postJson('/api/bookings', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure(['message', 'booking'])
                 ->assertJsonPath('booking.name', 'Karan Thapa')
                 ->assertJsonPath('booking.status', 'pending');

        $this->assertDatabaseHas('bookings', [
            'email' => 'karan@example.com',
            'tour'  => 'Everest Base Camp',
        ]);
    }

    public function test_booking_requires_all_required_fields(): void
    {
        $response = $this->postJson('/api/bookings', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'phone', 'tour', 'travel_date', 'travelers']);
    }

    public function test_booking_rejects_invalid_email(): void
    {
        $response = $this->postJson('/api/bookings', [
            'name'        => 'Test User',
            'email'       => 'not-an-email',
            'phone'       => '1234567890',
            'tour'        => 'Annapurna Circuit',
            'travel_date' => now()->addDays(10)->toDateString(),
            'travelers'   => 1,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_booking_rejects_zero_travelers(): void
    {
        $response = $this->postJson('/api/bookings', [
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'phone'       => '1234567890',
            'tour'        => 'Annapurna Circuit',
            'travel_date' => now()->addDays(10)->toDateString(),
            'travelers'   => 0,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['travelers']);
    }

    public function test_booking_rejects_negative_travelers(): void
    {
        $response = $this->postJson('/api/bookings', [
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'phone'       => '1234567890',
            'tour'        => 'Annapurna Circuit',
            'travel_date' => now()->addDays(10)->toDateString(),
            'travelers'   => -3,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['travelers']);
    }

    public function test_booking_rejects_invalid_date(): void
    {
        $response = $this->postJson('/api/bookings', [
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'phone'       => '1234567890',
            'tour'        => 'Annapurna Circuit',
            'travel_date' => 'not-a-date',
            'travelers'   => 1,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['travel_date']);
    }

    public function test_booking_message_is_optional(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/bookings', [
            'name'        => 'No Message User',
            'email'       => 'nomsg@example.com',
            'phone'       => '1234567890',
            'tour'        => 'Chitwan Safari',
            'travel_date' => now()->addDays(5)->toDateString(),
            'travelers'   => 1,
        ]);

        $response->assertStatus(201);
    }

    // ─── INTEGRATION: Admin Booking Management ─────────────────────────────

    public function test_admin_can_list_bookings(): void
    {
        $admin  = User::factory()->create(['is_admin' => true]);
        $token  = $admin->createToken('test')->plainTextToken;
        Booking::factory()->count(3)->create();

        $response = $this->withToken($token)
                         ->getJson('/api/admin/bookings');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_guest_cannot_access_admin_bookings(): void
    {
        $response = $this->getJson('/api/admin/bookings');
        $response->assertStatus(401);
    }

    public function test_admin_can_update_booking_status_to_confirmed(): void
    {
        $admin   = User::factory()->create(['is_admin' => true]);
        $token   = $admin->createToken('test')->plainTextToken;
        $booking = Booking::factory()->create(['status' => 'pending']);

        $response = $this->withToken($token)
                         ->patchJson("/api/admin/bookings/{$booking->id}/status", [
                             'status' => 'confirmed',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('booking.status', 'confirmed');

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_admin_can_update_booking_status_to_cancelled(): void
    {
        $admin   = User::factory()->create(['is_admin' => true]);
        $token   = $admin->createToken('test')->plainTextToken;
        $booking = Booking::factory()->create(['status' => 'pending']);

        $response = $this->withToken($token)
                         ->patchJson("/api/admin/bookings/{$booking->id}/status", [
                             'status' => 'cancelled',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('booking.status', 'cancelled');
    }

    public function test_admin_cannot_set_invalid_booking_status(): void
    {
        $admin   = User::factory()->create(['is_admin' => true]);
        $token   = $admin->createToken('test')->plainTextToken;
        $booking = Booking::factory()->create(['status' => 'pending']);

        $response = $this->withToken($token)
                         ->patchJson("/api/admin/bookings/{$booking->id}/status", [
                             'status' => 'hacked',
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);
    }

    // ─── SECURITY: Unauthorized Access ────────────────────────────────────

    public function test_non_admin_user_cannot_access_admin_bookings(): void
    {
        $user  = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
                         ->getJson('/api/admin/bookings');

        $response->assertStatus(403);
    }

public function test_new_booking_sends_database_notification_to_admins(): void
{
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $nonAdmin = User::factory()->create(['is_admin' => false]);

    $response = $this->postJson('/api/bookings', [
        'name' => 'Test Customer',
        'email' => 'customer@example.com',
        'phone' => '9800000000',
        'tour' => 'Everest Base Camp',
        'travel_date' => now()->addDays(30)->toDateString(),
        'travelers' => 2,
    ]);

    $response->assertStatus(201);

    Notification::assertSentTo(
        $admin,
        AdminActivityNotification::class
    );

    Notification::assertNotSentTo(
        $nonAdmin,
        AdminActivityNotification::class
    );
}

public function test_confirming_booking_sends_confirmation_email(): void
{
    Mail::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $booking = Booking::factory()->create(['status' => 'pending']);

    $response = $this->withToken($token)->patchJson(
        "/api/admin/bookings/{$booking->id}/status",
        ['status' => 'confirmed']
    );

    $response->assertStatus(200);

    Mail::assertSent(BookingStatusUpdated::class, function ($mail) use ($booking) {
        return $mail->hasTo($booking->email)
            && $mail->booking->status === 'confirmed';
    });
}

public function test_cancelling_booking_sends_cancellation_email(): void
{
    Mail::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $booking = Booking::factory()->create(['status' => 'pending']);

    $response = $this->withToken($token)->patchJson(
        "/api/admin/bookings/{$booking->id}/status",
        ['status' => 'cancelled']
    );

    $response->assertStatus(200);

    Mail::assertSent(BookingStatusUpdated::class, function ($mail) use ($booking) {
        return $mail->hasTo($booking->email)
            && $mail->booking->status === 'cancelled';
    });
}

public function test_unchanged_booking_status_does_not_send_email(): void
{
    Mail::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $booking = Booking::factory()->create(['status' => 'confirmed']);

    $response = $this->withToken($token)->patchJson(
        "/api/admin/bookings/{$booking->id}/status",
        ['status' => 'confirmed']
    );

    $response->assertStatus(200);

    Mail::assertNothingSent();
}

public function test_invalid_booking_status_does_not_send_email(): void
{
    Mail::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $booking = Booking::factory()->create(['status' => 'pending']);

    $response = $this->withToken($token)->patchJson(
        "/api/admin/bookings/{$booking->id}/status",
        ['status' => 'invalid']
    );

    $response->assertStatus(422);

    Mail::assertNothingSent();
}
}
