<?php

namespace App\Http\Controllers\Api;

use App\Models\OrderItem;
use App\Models\Service;
use App\Mopdels\Cart;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartController extends Controller
{

    public function addToCart(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $service = Service::findOrFail($request->service_id);

        $cartItem = Cart::updateOrCreate(
            [
                'service_id' => $service->id,
            ],
            [
                'quantity' => \DB::raw('quantity + ' . $request->quantity),
                'price' => $service->price,
            ]
        );

        return response()->json([
            'message' => 'Service added to cart successfully',
            'cart_item' => $cartItem,
        ], 201);
    }
    
}
