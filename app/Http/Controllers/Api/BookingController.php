<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\AdminActivityNotification;
use App\Mail\BookingStatusUpdated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    // Create a public booking.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'tour' => ['required', 'string', 'max:255'],
            'travel_date' => ['required', 'date'],
            'travelers' => ['required', 'integer', 'min:1'],
            'message' => ['nullable', 'string'],
        ]);

        // Status defaults to pending in the database.
        $booking = Booking::create($validated);
        User::where('is_admin', true)->each(function ($admin) use ($booking) {
    $admin->notify(new AdminActivityNotification([
        'type' => 'booking',
        'title' => 'New Booking',
        'message' => $booking->name . ' submitted a booking for ' . $booking->tour,
        'booking_id' => $booking->id,
        'url' => '/admin/bookings',
    ]));
});

        return response()->json([
            'message' => 'Booking submitted successfully.',
            'booking' => $booking,
        ], 201);
    }

    // List bookings for the admin panel.
    public function adminIndex()
    {
        $bookings = Booking::latest()->get();

        return response()->json($bookings);
    }

    // Update booking status from the admin panel.

// Update booking status from the admin panel.
public function updateStatus(Request $request, Booking $booking)
{
    $validated = $request->validate([
        'status' => ['required', 'in:pending,confirmed,cancelled'],
    ]);

    $oldStatus = $booking->status;
    $newStatus = $validated['status'];

    // Update the booking status.
    $booking->update([
        'status' => $newStatus,
    ]);

    // Send an email only when changing to confirmed or cancelled.
    if (
        $oldStatus !== $newStatus &&
        in_array($newStatus, ['confirmed', 'cancelled'])
    ) {
        try {
            Mail::to($booking->email)
                ->send(new BookingStatusUpdated($booking->fresh()));
        } catch (\Throwable $e) {
            Log::error('Booking status email failed.', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    return response()->json([
        'message' => 'Booking status updated successfully.',
        'booking' => $booking->fresh(),
    ]);
}

}
