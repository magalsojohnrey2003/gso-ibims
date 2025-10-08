<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NotificationController extends Controller
{
    /**
     * Show the notifications page (View all).
     * This returns a blade view that uses the same JS to fetch notifications list.
     */
    public function index(Request $request)
    {
        // Blade will use JS to load notifications, so this is minimal.
        return view('notifications.index');
    }

    /**
     * Return JSON list of recent notifications for the authenticated user.
     * This is used by AJAX (notifications.js).
     */
    public function list(Request $request)
    {
        $user = $request->user();

        // Return latest 50 (or adjust)
        $notifs = $user->notifications()->latest()->take(50)->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->toDateTimeString(),
            ];
        });

        return response()->json($notifs);
    }

    /**
     * Mark one notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        foreach ($user->unreadNotifications as $n) {
            $n->markAsRead();
        }

        return response()->json(['message' => 'All marked as read']);
    }
}
