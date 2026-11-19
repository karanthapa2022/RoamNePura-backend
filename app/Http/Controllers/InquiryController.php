<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\AdminActivityNotification;

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
        User::where('is_admin', true)->each(function ($admin) use ($inquiry) {
    $admin->notify(new AdminActivityNotification([
        'type' => 'inquiry',
        'title' => 'New Inquiry',
        'message' => $inquiry->name . ' submitted a new inquiry.',
        'inquiry_id' => $inquiry->id,
        'url' => '/admin/inquiries',
    ]));
});

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


public function updateStatus(Request $request, Inquiry $inquiry)
{
    $validated = $request->validate([
        'status' => 'required|in:new,read,replied',
    ]);

    $inquiry->update([
        'status' => $validated['status'],
    ]);

    // Mark matching inquiry notifications as read.
    if (in_array($validated['status'], ['read', 'replied'], true)) {
        $admins = User::where('is_admin', true)->get();

        foreach ($admins as $admin) {
            $admin->unreadNotifications()
                ->where('data->type', 'inquiry')
                ->where('data->inquiry_id', $inquiry->id)
                ->update([
                    'read_at' => now(),
                ]);
        }
    }

    return response()->json([
        'message' => 'Inquiry status updated successfully.',
        'inquiry' => $inquiry->load('destination'),
    ]);
}
}
