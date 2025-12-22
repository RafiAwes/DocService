<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /**
     * STEP 1: Initiate Checkout
     * Validates items, calculates total securely, creates pending order, returns Client Secret.
     */
    public function initiateCheckout(Request $request)
    {
        $request->validate([
            'is_south_africa' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.delivery_ids' => 'nullable|array',
            'items.*.delivery_ids.*' => 'exists:delivery_details,id', // Validate delivery IDs exist
        ]);

        $user = Auth::user();

        try {
            return DB::transaction(function () use ($request, $user) {

                // 1. Calculate Total (Server Side Security)
                $totalAmount = 0;
                $orderItemsData = [];

                foreach ($request->items as $item) {
                    $service = Service::findOrFail($item['service_id']);

                    // Use price from DB, ignore frontend price
                    $subtotal = $service->price * $item['quantity'];
                    $totalAmount += $subtotal;

                    $orderItemsData[] = [
                        'service' => $service,
                        'quantity' => $item['quantity'],
                        'subtotal' => $subtotal,
                        'delivery_ids' => $item['delivery_ids'] ?? [],
                    ];
                }

                // 2. Create Stripe Intent (The "Handshake")
                $paymentIntent = PaymentIntent::create([
                    'amount' => round($totalAmount * 100), // Convert to cents
                    'currency' => 'usd',
                    'metadata' => [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ],
                    'payment_method'=> 'pm_card_visa',
                    'confirm'=> true,
                    'return_url' => 'https://checkout.stripe.dev/success',
                    'automatic_payment_methods' => ['enabled' => true, 'allow_redirects'=> 'never'],
                ]);

                // 3. Create Pending Order in DB
                $order = Order::create([
                    'user_id' => $user->id,
                    'total_amount' => $totalAmount,
                    'is_south_africa' => $request->is_south_africa,
                    'stripe_payment_id' => $paymentIntent->id, // Store this to verify later
                    'status' => 'pending',
                ]);

                // 4. Save Order Items & Delivery Options
                foreach ($orderItemsData as $data) {
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'service_id' => $data['service']->id,
                        'quantity' => $data['quantity'],
                        'price' => $data['service']->price,
                        'subtotal' => $data['subtotal'],
                    ]);

                    // Attach delivery options (Many-to-Many)
                    if (! empty($data['delivery_ids'])) {
                        // Ensure your OrderItem model has 'deliveryOptions()' method (not 'deliveryOptionss')
                        $orderItem->deliveryOptions()->attach($data['delivery_ids']);
                    }
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Checkout initiated successfully',
                    'data' => [
                        'order_id' => $order->id,
                        'total_amount' => $totalAmount,
                        'client_secret' => $paymentIntent->client_secret, // Frontend needs this for Stripe.js
                        'stripe_payment_id' => $paymentIntent->id, // Useful for your testing
                    ],
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Checkout initiation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * STEP 2: Confirm Payment
     * Frontend calls this after Stripe processing is done to update DB status.
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            // 1. Find the local order safely
            $order = Order::where('stripe_payment_id', $request->payment_intent_id)->first();

            if (! $order) {
                return response()->json([
                    'status' => false,
                    'message' => 'Order not found. Invalid Payment Intent ID.',
                ], 404);
            }

            if ($order->status === 'paid') {
                return response()->json([
                    'status' => true,
                    'message' => 'Order is already marked as paid.',
                ]);
            }

            // 2. SECURITY CHECK: Retrieve status from Stripe
            $intent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($intent->status === 'succeeded') {
                // 3. Mark as Paid
                $order->update(['status' => 'paid']);

                return response()->json([
                    'status' => true,
                    'message' => 'Payment verified and order confirmed.',
                    'data' => $order,
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Payment not successful yet.',
                    'stripe_status' => $intent->status,
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Confirmation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
