<?php

namespace App\Http\Controllers\Api;

use App\Models\Message;
use App\Mail\MessageOnEmail;
// use Illuminate\Mail\Message;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class MessageController extends Controller
{
    public function sendMessage(Request $request)
    {
        // 1. Validate
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        try {
            // 2. Save to Database
            $message = Message::create([
                'name'    => $request->name,
                'email'   => $request->email,
                'message' => $request->message,
            ]);

            // 3. Send Email to Admin
            // Change 'admin@yoursite.com' to your actual email
            Mail::to('rafiaweshan4897@gmail.com')->send(new MessageOnEmail($message));

            return response()->json([
                'status'  => true,
                'message' => 'Message sent successfully!',
                'data'    => $message
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to send message',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get All Messages (Admin Only)
     * GET /api/admin/messages
     */
    public function index()
    {
        $messages = Message::orderBy('created_at', 'desc')->paginate(10);
        return response()->json(['status' => true, 'data' => $messages]);
    }
}
