<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get all notifications (read and unread)
        // unreadNotifications() gives only new ones
        $notifications = $user->notifications;

        return response()->json([
            'status'    => true,
            'message'   => 'Notifications fetched successfully',
            'data'      => $notifications,
        ]);
    }

    /**
     * Mark all as Read
     * POST /api/notifications/read-all
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json([
            'status'    => true,
            'message'   => 'All notifications marked as read',
        ]);
    }
}
