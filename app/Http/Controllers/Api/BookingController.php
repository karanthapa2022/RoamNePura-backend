<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

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
    public function updateStatus(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled'],
        ]);

        $booking->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Booking status updated successfully.',
            'booking' => $booking->fresh(),
        ]);
    }
}
