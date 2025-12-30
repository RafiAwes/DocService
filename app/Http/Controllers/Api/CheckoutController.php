<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answers;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\NewOrderPlaced;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Stripe\PaymentIntent;
use Stripe\Stripe;

// Optional if you still use the service, but we are using direct Stripe calls here for simplicity as requested.

class CheckoutController extends Controller
{
    public function __construct()
    {
        // Set Stripe API Key globally for this controller
        Stripe::setApiKey(config('services.stripe.secret') ?? env('STRIPE_SECRET_KEY'));
    }

    // payment intent

    public function paymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => round($request->amount * 100), // amount in cents
                'currency' => 'usd',
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Payment Intent created successfully',
                'data' => $paymentIntent,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create Payment Intent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * STEP 1: Initiate Checkout
     * Validates items, calculates total securely, creates pending order, returns Client Secret.
     */
    public function paymentSuccess(Request $request)
    {

        // 1️⃣ Validate
        $request->validate([
            'amount' => 'required',
            'payment_intent_id' => 'required|string',
            'is_south_africa' => 'required|boolean',

            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.delivery_ids' => 'nullable|array',

            'items.*.answers' => 'nullable|array',
            'items.*.answers.*.question_id' => 'required|exists:questionaries,id',
            'items.*.answers.*.value' => 'nullable',
        ]);
       

        $user = Auth::user();
        $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

        try {
            return DB::transaction(function () use ($request, $user, $paymentIntent) {

                // 2️⃣ Create Order
                $order = Order::create([
                    'user_id' => $user->id,
                    'orderid' => Order::generateOrderId(),
                    'total_amount' => $request->amount,
                    'is_south_africa' => $request->is_south_africa,
                    'stripe_payment_id' => $paymentIntent->id,
                    'status' => 'pending',
                ]);

                $orderItemsByService = [];

                // 3️⃣ Items + Answers
                foreach ($request->items as $itemIndex => $itemData) {
                    $service = Service::findOrFail($itemData['service_id']);

                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'service_id' => $service->id,
                        'quantity' => $itemData['quantity'],
                        'price' => $service->price,
                        'subtotal' => $service->price * $itemData['quantity'],
                    ]);

                    $orderItemsByService[$service->id] = $orderItem;

                    // Delivery mapping
                    if (! empty($itemData['delivery_ids'])) {
                        $orderItem->deliveryOptions()->attach($itemData['delivery_ids']);
                    }
                    // Answers for THIS item
                    if (! empty($itemData['answers'])) {
                        foreach ($itemData['answers'] as $aIndex => $answer) {
                            $storedValue = $answer['value'] ?? null;

                            // FILE?
                            $fileKey = "items.{$itemIndex}.answers.{$aIndex}.value";


                            if ($request->hasFile($fileKey)) {
                                $file = $request->file($fileKey);
                                $storedValue = $file->store('documents/orders', 'public');
                            }

                            Answers::create([
                                'user_id' => $user->id,
                                'order_id' => $order->id,
                                'order_item_id' => $orderItem->id,
                                'questionary_id' => $answer['question_id'],
                                'value' => $storedValue,
                            ]);
                        }
                    }

                }

                // 4️⃣ Transaction record
                Transaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $request->amount,
                    'status' => 'initiated',
                ]);

                // 5️⃣ Notify admins ans user
               
                // Notify the user who placed the order
                $user = Auth::user();
                Notification::send($user, new NewOrderPlaced($order));

                // Notify all admins
                $admins = User::where('role', 'admin')->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new NewOrderPlaced($order));
                }
                return response()->json([
                    'status' => true,
                    'message' => 'Order placed successfully',
                    'data' => [
                        'order' => $order,
                    ],
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Order Error: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to place order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
