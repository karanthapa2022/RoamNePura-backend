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
}
