<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'destination_id' => 'nullable|exists:destinations,id',
            'message' => 'required|string|max:5000',
        ]);

        $inquiry = Inquiry::create($validated);

        return response()->json([
            'message' => 'Your inquiry has been submitted successfully.',
            'inquiry' => $inquiry,
        ], 201);
    }

    public function adminIndex()
    {
        $inquiries = Inquiry::with('destination')
            ->latest()
            ->get();

        return response()->json($inquiries);
    }
}
