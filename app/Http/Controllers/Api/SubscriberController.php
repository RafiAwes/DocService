<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $subscribers = Subscriber::latest('id')->paginate($perPage);
        return response()->json([
            'success' => true,
            'data' => $subscribers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:subscribers,email',
        ]);

        $subscriber = Subscriber::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subscriber added successfully',
            'data' => $subscriber,
        ], 201);
    }
}
