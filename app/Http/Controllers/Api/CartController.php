<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};
use App\Http\Controllers\Controller;
use App\Models\{Answers, Cart, CartItem, DeliveryDetails, Questionaries, RequiredDocuments};

class CartController extends Controller
{
    /**
     * 1. GET /api/cart
     * View the user's cart with all details including service relations
     */
    public function index()
    {
        $user = Auth::user();

        $cart = Cart::with([
            'items.service.category',
            'items.service.deliveryDetails',
            'items.answers.questionary',
        ])->where('user_id', $user->id)->first();

        if (! $cart) {
            return response()->json([
                'status' => true,
                'message' => 'Cart is empty',
                'data' => [
                    'cart_id' => null,
                    'user_id' => $user->id,
                    'total_items' => 0,
                    'grand_total' => 0,
                    'items' => [],
                    'created_at' => null,
                    'updated_at' => null,
                ],
            ]);
        }

        $formattedItems = $cart->items->map(function ($item) {
            // $service = $item->service;
            $selectedDeliveryDetails = collect();

            if (! empty($item->delivery_details_ids)) {
                $selectedDeliveryDetails = DeliveryDetails::whereIn('id', $item->delivery_details_ids)
                    ->get()
                    ->map(function ($dd) {
                        return [
                            'id' => $dd->id,
                            'delivery_type' => $dd->delivery_type,
                            'details' => $dd->details,
                            'price' => $dd->price,
                        ];
                    });
            }

            // dd($service->title);

            return [
                'id' => $item->id,
                'cart_id' => $item->cart_id,
                'service_id' => $item->service_id,
                'total_price' => $item->total_price,
                'quantity' => $item->quantity,
                'delivery_details_ids' => $item->delivery_details_ids,
                'delivery_details' => $selectedDeliveryDetails,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,

                'service' => [
                    'id' => $item->service->id,
                    'title' => $item->service->title,
                    'subtitle' => $item->service->subtitle,
                    'type' => $item->service->type,
                    'order_type' => $item->service->order_type,
                    'price' => $item->service->price,
                    'description' => $item->service->description,

                    'category' => $item->service->category ? [
                        'id' => $item->service->category->id,
                        'name' => $item->service->category->name ?? null,
                        'image' => $item->service->category->image ?? null,
                    ] : null,
                ],

                'subtotal' => $item->quantity * $item->service->price,
            ];
        });

        $grandTotal = $formattedItems->sum('subtotal');

        return response()->json([
            'status' => true,
            'message' => 'Cart retrieved successfully',
            'data' => [
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
                'total_price' => $cart->total_price,
                'total_items' => $formattedItems->count(),
                'grand_total' => $grandTotal,
                'items' => $formattedItems,
                'created_at' => $cart->created_at,
                'updated_at' => $cart->updated_at,
            ],
        ]);
    }

    /**
     * 2. POST /api/cart/add
     * Add a service + answers to the cart
     */
    public function addToCart(Request $request)
    {
        // 1. Validation (Removed 'answers' rules since we aren't storing them)
        $request->validate([
            'total_price' => 'required|numeric|min:0',
            'service_id' => 'required|exists:services,id',
            'quantity' => 'nullable|integer|min:1',
            'delivery_details_ids' => 'nullable|array',
        ]);

        $user = Auth::user();

        try {
            return DB::transaction(function () use ($request, $user) {

                // A. Get or Create the User's Cart
                $cart = Cart::firstOrCreate(['user_id' => $user->id]);

                // B. Create the Cart Item
                // We strictly add the item details here.
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'service_id' => $request->service_id,
                    'total_price' => $request->total_price,
                    'quantity' => $request->quantity ?? 0, // Default to 1 if null
                    'delivery_details_ids' => $request->delivery_details_ids ?? [],
                ]);

                // dd($cartItem);

                // C. (REMOVED) Logic to store in 'answers' table has been deleted.

                return response()->json([
                    'status' => true,
                    'message' => 'Item added to cart successfully',
                    'data' => $cartItem, 
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Cart Add Error: '.$e->getMessage());

            return response()->json([
                'status' => false, 
                'error' => 'Unable to add item to cart.' // Generic error for user, real one in logs
            ], 500);
        }
    }

    /**
     * 3. PUT /api/cart/update/{itemId}
     * Update Quantity of an existing item
     */
    public function updateItem(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        // Find item ensuring it belongs to the logged-in user's cart
        $cartItem = CartItem::where('id', $itemId)
            ->whereHas('cart', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->first();

        if (! $cartItem) {
            return response()->json(['status' => false, 'message' => 'Item not found'], 404);
        }

        $cartItem->update([
            'quantity' => $request->quantity,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cart updated successfully',
            'data' => $cartItem,
        ]);
    }

    /**
     * 4. DELETE /api/cart/remove/{itemId}
     * Remove a specific item (and its answers)
     */
    public function removeItem($itemId)
    {
        $user = Auth::user();

        $cartItem = CartItem::where('id', $itemId)
            ->whereHas('cart', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->first();

        if (! $cartItem) {
            return response()->json(['status' => false, 'message' => 'Item not found'], 404);
        }

        // Because we set onDelete('cascade') in migrations,
        // deleting this item AUTOMATICALLY deletes the rows in 'answers' table.
        $cartItem->delete();

        return response()->json([
            'status' => true,
            'message' => 'Item removed from cart',
        ]);
    }

    /**
     * 5. DELETE /api/cart/clear
     * Empty the entire cart
     */
    public function clearCart()
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart) {
            // Delete all items (triggers cascade for answers)
            $cart->items()->delete();

            // Optional: You can keep the empty cart shell or delete it too
            // $cart->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Cart cleared successfully',
        ]);
    }

    public function getCartRequirements(Request $request)
    {
        $user = Auth::user();

        // 1. Fetch Cart with deeply nested service requirements
        // We strictly select only the relationships needed for the forms.
        $cart = Cart::with([
            'items.service.questionaries',
            'items.service.requiredDocuments'
        ])
        ->where('user_id', $user->id)
        ->first();

        // Handle empty cart
        if (! $cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Cart is empty',
                'data' => [],
            ]);
        }

        // 2. Map the logic into a variable (Clean Code)
        // We map over cart items so if you have 3 of the same service, you get 3 forms.
        $itemsPendingInfo = $cart->items->map(function ($item) {
            // Ensure we have fresh service relations for this item
            $service = $item->service->loadMissing(['questionaries', 'requiredDocuments']);

            $requiredDocs = $service->requiredDocuments;
            // Fallback: in case eager load failed, fetch by service_id
            if ($requiredDocs === null || $requiredDocs->isEmpty()) {
                $requiredDocs = RequiredDocuments::where('service_id', $item->service_id)->get();
            }

            return [
                'cart_item_id' => $item->id,
                'service_id' => $item->service_id,
                'service_name' => $service->title,
                
                // The Questions
                'questions' => $service->questionaries->map(function ($q) {
                    return [
                        'id' => $q->id,
                        'name' => $q->name,
                        'type' => $q->type,
                        'options' => $q->options,
                        'is_required' => (bool) $q->required,
                    ];
                }),

                // The Required Documents
                'required_documents' => $requiredDocs->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        // 'description' => $doc->description ?? null,
                    ];
                }),
            ];
        });

        // 3. Return the final response
        return response()->json([
            'status' => true,
            'message' => 'Cart requirements retrieved successfully',
            'data' => [
                'cart_id' => $cart->id,
                'items_pending_info' => $itemsPendingInfo,
            ],
        ]);
    }
}
