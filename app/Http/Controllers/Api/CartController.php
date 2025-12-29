<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answers;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Questionaries;
// Models
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    /**
     * 1. GET /api/cart
     * View the user's cart with all details including service relations
     */
    public function index()
    {
        $user = Auth::user();

        // Fetch Cart with deeply nested relationships
        $cart = Cart::with([
            // Sort the 'items' relationship Descending by ID
            'items' => function ($query) {
                $query->orderBy('id', 'desc');
            },
            // Load nested relationships for those sorted items
            'items.service.category',
            'items.service.includedServices',
            'items.service.processingTimes',
            'items.service.deliveryDetails',
            'items.service.questionaries',
            'items.service.requiredDocuments',
            'items.answers.questionary',
        ])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc') // This gets the latest Cart
            ->first();

        if (! $cart) {
            return response()->json([
                'status' => true,
                'message' => 'Cart is empty',
                'data' => null,
            ]);
        }

        // Format items with complete details
        $formattedItems = $cart->items->map(function ($item) {
            return [
                'id' => $item->id,
                'cart_id' => $item->cart_id,
                'quantity' => $item->quantity,
                'delivery_details_ids' => $item->delivery_details_ids,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,

                // Complete Service Details
                'service' => [
                    'id' => $item->service->id,
                    'title' => $item->service->title,
                    'subtitle' => $item->service->subtitle,
                    'type' => $item->service->type,
                    'order_type' => $item->service->order_type,
                    'price' => $item->service->price,
                    'description' => $item->service->description,

                    // Category
                    'category' => $item->service->category ? [
                        'id' => $item->service->category->id,
                        'name' => $item->service->category->name ?? null,
                        'image' => $item->service->category->image ?? null,
                    ] : null,

                    // Included Services
                    'included_services' => $item->service->includedServices->map(function ($inc) {
                        return [
                            'id' => $inc->id,
                            'service_type' => $inc->service_type,
                            'included_details' => $inc->included_details,
                            'price' => $inc->price,
                        ];
                    }),

                    // Processing Times
                    'processing_times' => $item->service->processingTimes->map(function ($pt) {
                        return [
                            'id' => $pt->id,
                            'time' => $pt->time,
                            'details' => $pt->details,
                        ];
                    }),

                    // Delivery Details
                    'delivery_details' => $item->service->deliveryDetails->map(function ($dd) {
                        return [
                            'id' => $dd->id,
                            'delivery_type' => $dd->delivery_type,
                            'details' => $dd->details,
                            'price' => $dd->price,
                        ];
                    }),

                    // Questions/Questionaries
                    'questionaries' => $item->service->questionaries->map(function ($q) {
                        return [
                            'id' => $q->id,
                            'name' => $q->name,
                            'type' => $q->type,
                            'options' => $q->options,
                        ];
                    }),

                    // Required Documents
                    'required_documents' => $item->service->requiredDocuments->map(function ($rd) {
                        return [
                            'id' => $rd->id,
                            'title' => $rd->title,
                        ];
                    }),
                ],

                // User's Answers for this cart item
                'answers' => $item->answers->map(function ($ans) {
                    return [
                        'id' => $ans->id,
                        'questionary_id' => $ans->questionary_id,
                        'value' => $ans->value, // Auto-converted to URL if file
                        'file_url' => $ans->file_url,
                        'questionary' => $ans->questionary ? [
                            'id' => $ans->questionary->id,
                            'name' => $ans->questionary->name,
                            'type' => $ans->questionary->type,
                            'options' => $ans->questionary->options,
                        ] : null,
                    ];
                }),

                // Item subtotal
                'subtotal' => $item->quantity * $item->service->price,
            ];
        });

        // Calculate Grand Total
        $grandTotal = $formattedItems->sum('subtotal');

        return response()->json([
            'status' => true,
            'message' => 'Cart retrieved successfully',
            'data' => [
                'cart_id' => $cart->id,
                'user_id' => $cart->user_id,
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
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'quantity' => 'nullable|integer|min:1',
            'delivery_details_ids' => 'nullable|array',

            // Answers Validation
            'answers' => 'nullable|array',
            // Support both question_id and questionary_id, require at least one
            'answers.*.question_id' => 'required_without:answers.*.questionary_id|exists:questionaries,id',
            'answers.*.questionary_id' => 'required_without:answers.*.question_id|exists:questionaries,id',
            'answers.*.value' => 'nullable',
        ]);

        $user = Auth::user();

        try {
            return DB::transaction(function () use ($request, $user) {

                // A. Get or Create the User's Cart
                $cart = Cart::firstOrCreate(['user_id' => $user->id]);

                // B. Create the Cart Item
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'service_id' => $request->service_id,
                    'quantity' => $request->quantity,
                    'delivery_details_ids' => $request->delivery_details_ids ?? [],
                ]);

                // C. Process Answers (Using your standard logic)
                if ($request->has('answers')) {
                    foreach ($request->answers as $index => $answerData) {
                        // Accept either question_id or questionary_id
                        $questionId = $answerData['questionary_id'] ?? $answerData['question_id'] ?? null;
                        if (! $questionId) {
                            continue;
                        }

                        $question = Questionaries::find($questionId);
                        // dd($request->all(), $questionId, $question);
                        // Security: Check if question belongs to this service
                        if ($question && $question->service_id == $request->service_id) {

                            $storedValue = null;
                            // return response()->json(['question' => $question, 'index' => $index, 'request' => $request->all()]);
                            // Handle File Upload
                            if (strtolower($question->type) === 'file') {
                                // Since we are adding ONE item, the key is simple: answers[0][value]
                                $fileKey = "answers.{$index}.value";
                                if ($request->hasFile($fileKey)) {
                                    $file = $request->file($fileKey);
                                    $storedValue = $file->store('documents/cart_uploads', 'public');
                                }
                            } else {
                                $storedValue = $answerData['value'];
                            }
                            // Save to Main Answers Table
                            $answers = Answers::create([
                                'user_id' => $user->id,
                                'cart_id' => $cart->id,
                                'cart_item_id' => $cartItem->id,
                                'questionary_id' => $question->id,
                                'value' => $storedValue,
                            ]);

                            // dd($answers);
                        }
                    }
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Item added to cart',
                    'data' => $cartItem->load('answers.questionary'),
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Cart Add Error: '.$e->getMessage());

            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
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
     * Remove a specific item, its answers, and delete uploaded files
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

        try {
            DB::transaction(function () use ($cartItem) {
                // 1. Get all answers for this cart item
                $answers = Answers::where('cart_item_id', $cartItem->id)->get();

                // 2. Delete associated files from storage
                foreach ($answers as $answer) {
                    if ($answer->value && is_string($answer->value)) {
                        // Check if value is a file path (starts with documents/ or answers/)
                        if (str_starts_with($answer->value, 'documents/') || str_starts_with($answer->value, 'answers/')) {
                            // Delete the file from public disk
                            if (Storage::disk('public')->exists($answer->value)) {
                                Storage::disk('public')->delete($answer->value);
                                Log::info("Deleted file: {$answer->value}");
                            }
                        }
                    }
                }

                // 3. Delete all answers for this cart item
                Answers::where('cart_item_id', $cartItem->id)->delete();

                // 4. Delete the cart item itself
                $cartItem->delete();
            });

            return response()->json([
                'status' => true,
                'message' => 'Item removed from cart along with uploaded files',
            ]);

        } catch (\Exception $e) {
            Log::error('Cart item deletion error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to remove item from cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 5. DELETE /api/cart/clear
     * Empty the entire cart and delete all uploaded files
     */
    public function clearCart()
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json([
                'status' => true,
                'message' => 'Cart is already empty',
            ]);
        }

        try {
            DB::transaction(function () use ($cart) {
                // 1. Get all cart items
                $cartItems = $cart->items;

                // 2. For each item, get answers and delete files
                foreach ($cartItems as $cartItem) {
                    $answers = Answers::where('cart_item_id', $cartItem->id)->get();

                    foreach ($answers as $answer) {
                        if ($answer->value && is_string($answer->value)) {
                            // Check if value is a file path
                            if (str_starts_with($answer->value, 'documents/') || str_starts_with($answer->value, 'answers/')) {
                                // Delete the file from public disk
                                if (Storage::disk('public')->exists($answer->value)) {
                                    Storage::disk('public')->delete($answer->value);
                                    Log::info("Deleted file: {$answer->value}");
                                }
                            }
                        }
                    }

                    // Delete answers for this cart item
                    Answers::where('cart_item_id', $cartItem->id)->delete();
                }

                // 3. Delete all cart items
                $cart->items()->delete();

                // Optional: Delete the cart itself
                // $cart->delete();
            });

            return response()->json([
                'status' => true,
                'message' => 'Cart cleared successfully along with all uploaded files',
            ]);

        } catch (\Exception $e) {
            Log::error('Cart clear error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to clear cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
