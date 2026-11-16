<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    // Get paginated notifications and unread count.

public function index(Request $request)
{
    $validated = $request->validate([
        'type' => ['nullable', 'in:booking,inquiry'],
        'status' => ['nullable', 'in:read,unread'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);

    $user = $request->user();

    $query = $user->notifications()->latest();

    // Filter by notification type.
    if (!empty($validated['type'])) {
        $query->where('data->type', $validated['type']);
    }

    // Filter by read/unread status.
    if (($validated['status'] ?? null) === 'read') {
        $query->whereNotNull('read_at');
    } elseif (($validated['status'] ?? null) === 'unread') {
        $query->whereNull('read_at');
    }

    $notifications = $query->paginate(10);

    return response()->json([
        'notifications' => $notifications->items(),

        'pagination' => [
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'from' => $notifications->firstItem(),
            'to' => $notifications->lastItem(),
        ],

        // Always show the total unread count, regardless of active filters.
        'unread_count' => $user->unreadNotifications()->count(),
    ]);
}


    // Mark one notification as read.
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
            'unread_count' => $request->user()
                ->unreadNotifications()
                ->count(),
        ]);
    }

    // Mark all notifications as read.
    public function markAllAsRead(Request $request)
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read.',
            'unread_count' => 0,
        ]);
    }
}
