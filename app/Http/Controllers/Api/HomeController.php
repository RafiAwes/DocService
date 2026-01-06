<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get testimonials with ratings and reviews
     */
    public function testimonials()
    {
        $testimonials = Rating::with(['user:id,name,email', 'order:id,status'])
            ->whereNotNull('review')
            ->where('rating', '>=', 4)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $testimonials,
        ]);
    }
}
