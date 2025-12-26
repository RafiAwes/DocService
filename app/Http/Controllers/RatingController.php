<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string',
        ]);

        try {

            $alreadyExists = Rating::where('order_id', $request->order_id)
                ->where('user_id', Auth::id())
                ->first();
            if ($alreadyExists) {
                return response()->json(['message' => 'You have already submitted a rating for this order'], 400);
            }

            $rating = Rating::create([
                'user_id' => Auth::id(),
                'order_id' => $request->order_id,
                'rating' => $request->rating,
                'review' => $request->review,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to submit rating', 'error' => $th->getMessage()], 500);
        }

        return response()->json(['message' => 'Rating submitted successfully', 'data' => $rating], 201);
    }
}
