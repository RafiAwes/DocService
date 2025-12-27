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
        // 1. Validation
        $request->validate([
            'amount' => 'required',
            'payment_intent_id' => 'required|string',
            'is_south_africa' => 'required|boolean',

            // Items
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.delivery_ids' => 'nullable|array',

            // Dynamic Answers
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'required|exists:questionaries,id',
            'answers.*.value' => 'nullable',
        ]);

        $user = Auth::user();
        $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

        try {
            return DB::transaction(function () use ($request, $user, $paymentIntent) {

                // --- A. CREATE ORDER ---
                $order = Order::create([
                    'user_id' => $user->id,
                    'orderid' => Order::generateOrderId(),
                    'total_amount' => $request->amount,
                    'is_south_africa' => $request->is_south_africa,
                    'stripe_payment_id' => $paymentIntent->id,
                    'status' => 'pending',
                ]);

                // --- B. PREPARE QUESTIONS DATA ---
                $questionsData = collect();
                if ($request->has('answers')) {
                    $qIds = array_column($request->answers, 'question_id');
                    $questionsData = Questionaries::whereIn('id', $qIds)->get()->keyBy('id');
                }

                // --- C. LOOP THROUGH ITEMS (SERVICES) ---
                foreach ($request->items as $itemData) {

                    $service = Service::findOrFail($itemData['service_id']);

                    // 1. Create Order Item
                    $subtotal = $service->price * $itemData['quantity'];
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'service_id' => $service->id,
                        'quantity' => $itemData['quantity'],
                        'price' => $service->price,
                        'subtotal' => $subtotal,
                    ]);

                    if (! empty($itemData['delivery_ids'])) {
                        $orderItem->deliveryOptions()->attach($itemData['delivery_ids']);
                    }

                    // 2. Create Service Quote (Container for Answers)
                    // Ensure 'order_id' is in $fillable in ServiceQuote model
                    $serviceQuote = ServiceQuote::create([
                        'order_id' => $order->id,
                        'service_id' => $service->id,
                        'delivery_details_ids' => $itemData['delivery_ids'] ?? [],
                    ]);

                    // 3. Filter & Save Answers for THIS Service
                    if ($request->has('answers')) {
                        foreach ($request->answers as $index => $answerData) {
                            $qId = $answerData['question_id'];

                            if ($questionsData->has($qId)) {
                                $questionObj = $questionsData[$qId];

                                // LOGIC CHECK: Does this question belong to the current Service?
                                if ($questionObj->service_id == $service->id) {

                                    // Handle File Upload vs Text
                                    $storedValue = null;

                                    if ($questionObj->type === 'file') {
                                        if ($request->hasFile("answers.{$index}.value")) {
                                            $file = $request->file("answers.{$index}.value");
                                            $storedValue = $file->store('documents/orders_dynamic', 'public');
                                        }
                                    } else {
                                        $storedValue = $answerData['value'];
                                    }

                                    // Create Answer Record
                                    Answers::create([
                                        'service_quote_id' => $serviceQuote->id,
                                        'questionary_id' => $qId,
                                        'value' => $storedValue,
                                    ]);

                                    // Log::info("Saved Answer for QID: {$qId}"); // Uncomment to debug
                                } else {
                                    // Log::warning("Mismatch: QID {$qId} belongs to Service {$questionObj->service_id}, but current loop is Service {$service->id}");
                                }
                            }
                        }
                    }
                } // End Item Loop

                // --- D. CREATE TRANSACTION ---
                Transaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntent->id,
                    'amount' => $request->amount,
                    'status' => 'initiated',
                ]);

                // --- E. SEND NOTIFICATIONS ---
                $admins = User::where('role', 'admin')->get();
                if ($admins->count() > 0) {
                    Notification::send($admins, new NewOrderPlaced($order));
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Order placed successfully',
                    'data' => [
                        'order_id' => $order->id,
                        'stripe_id' => $paymentIntent->id,
                    ],
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Order Failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Order processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
