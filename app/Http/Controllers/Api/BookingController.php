<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'tour' => 'required|string|max:255',
            'travel_date' => 'required|date',
            'travelers' => 'required|integer|min:1',
            'message' => 'nullable|string',
        ]);

        $booking = Booking::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'tour' => $request->tour,
            'travel_date' => $request->travel_date,
            'travelers' => $request->travelers,
            'message' => $request->message,
        ]);
        return response()->json([
            'message'=>'Booking submitted successfully!',
            'booking'=> $booking,
        ], 201);
    }
}