<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    /**
     * List all reviews/ratings for the authenticated user.
     */
    public function reviewList()
    {
       
        $ratings = Rating::with('user:id,name,email,profile_pic')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'message' => 'Reviews retrieved successfully',
            'data' => $ratings,
        ], 200);
    }

    /**
     * Store a newly created rating in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|between:1,5',
            'review' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = Auth::id();

        $rating = Rating::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Rating submitted successfully',
            'data' => $rating->load('user', 'order'),
        ], 201);
    }

    
}
