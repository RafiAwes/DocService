<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $perpage = $request->query('per_page', 10);
        // Get all notifications (read and unread)
        // unreadNotifications() gives only new ones
        $notifications = $user->notifications()->paginate($perpage);

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

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();

            return response()->json([
                'status'    => true,
                'message'   => 'Notification marked as read',
            ]);
        } else {
            return response()->json([
                'status'    => false,
                'message'   => 'Notification not found',
            ], 404);
        }
    }
    /**
     * Get unread notifications
     * GET /api/notifications/unread
     */
    public function unread()
    {
        $user = Auth::user();

        $notifications = $user->unreadNotifications;

        return response()->json([
            'status'    => true,
            'message'   => 'Unread notifications fetched successfully',
            'data'      => $notifications,
        ]);
    }

}
