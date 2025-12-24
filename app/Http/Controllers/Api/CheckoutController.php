<?php

namespace App\Http\Controllers\Api;

use Stripe\Stripe;
use App\Models\User;
use App\Models\Order;
use App\Models\Answers;
use App\Models\Service;
use App\Models\OrderItem;
use Stripe\PaymentIntent;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Notifications\NewOrderPlaced;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;

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
        // 1. Validation (Added rules for Answer fields & Files)
        $request->validate([
            'amount' => 'required',
            'payment_intent_id' => 'required|string',
            // Order Basics
            'is_south_africa' => 'required|boolean',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.delivery_ids' => 'nullable|array',
            'items.*.delivery_ids.*' => 'exists:delivery_details,id',

            // Answer Fields
            'age' => 'nullable|integer',
            'about_yourself' => 'nullable|string',
            'birth_certificate' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120', // Max 5MB
            'nid_card' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
        ]);

        $user = Auth::user();
        // Retrieve PaymentIntent object from Stripe using the provided ID
        $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

        try {
            return DB::transaction(function () use ($request, $user, $paymentIntent) {

                // CALCULATE TOTAL & PREPARE ITEMS

                $orderItemsData = [];
                $allDeliveryIds = []; // To store in Answers table if needed

                foreach ($request->items as $item) {
                    $service = Service::findOrFail($item['service_id']);

                    // Calculate subtotal for this item
                    $subtotal = $service->price * $item['quantity'];

                    // Collect delivery IDs for the Answer JSON column
                    if (! empty($item['delivery_ids'])) {
                        $allDeliveryIds = array_merge($allDeliveryIds, $item['delivery_ids']);
                    }

                    $orderItemsData[] = [
                        'service'       => $service,
                        'quantity'      => $item['quantity'],
                        'subtotal'      => $subtotal,
                        'delivery_ids'  => $item['delivery_ids'] ?? [],
                    ];
                }

                // CREATE ORDER 
                $order = Order::create([
                    'user_id'               => $user->id,
                    'slug'                  => Order::generateSlug(),
                    'total_amount'          => $request->amount,
                    'is_south_africa'       => $request->is_south_africa,
                    'stripe_payment_id'     => $paymentIntent->id,
                    'status'                => 'pending', // Will be 'paid' if confirm=true above
                ]);

                // --- D. SAVE ORDER ITEMS ---
                foreach ($orderItemsData as $data) {
                    $orderItem = OrderItem::create([
                        'order_id'      => $order->id,
                        'service_id'    => $data['service']->id,
                        'quantity'      => $data['quantity'],
                        'price'         => $data['service']->price,
                        'subtotal'      => $data['subtotal'],
                    ]);

                    if (! empty($data['delivery_ids'])) {
                        $orderItem->deliveryOptions()->attach($data['delivery_ids']);
                    }
                }

                // --- E. HANDLE FILE UPLOADS & CREATE ANSWER ---

                // Define where to save documents
                $docPath = public_path('documents/orders');
                if (! File::exists($docPath)) {
                    File::makeDirectory($docPath, 0755, true);
                }

                $birthCertPath = null;
                $nidPath = null;

                // Upload Birth Certificate
                if ($request->hasFile('birth_certificate')) {
                    $file = $request->file('birth_certificate');
                    $filename = time().'_birth_'.Str::random(8).'.'.$file->getClientOriginalExtension();
                    $file->move($docPath, $filename);
                    $birthCertPath = 'documents/orders/'.$filename;
                }

                // Upload NID Card
                if ($request->hasFile('nid_card')) {
                    $file = $request->file('nid_card');
                    $filename = time().'_nid_'.Str::random(8).'.'.$file->getClientOriginalExtension();
                    $file->move($docPath, $filename);
                    $nidPath = 'documents/orders/'.$filename;
                }

                // Create the Answer Record linked to this Order
                $answer = Answers::create([
                    'order_id'              => $order->id,
                    'delivery_details_ids'  => array_unique($allDeliveryIds), // Storing all unique delivery IDs involved
                    'south_african'         => $request->is_south_africa,
                    'age'                   => $request->age,
                    'about_yourself'        => $request->about_yourself,
                    'birth_certificate'     => $birthCertPath,
                    'nid_card'              => $nidPath,
                ]);

                $transaction = Transaction::create([
                    'user_id'           => $user->id,
                    'order_id'          => $order->id,
                    'payment_intent_id' => $paymentIntent->id,
                    'amount'            => $request->amount,
                    // 'status'            => 'initiated',
                ]);

                $users = User::where('role', 'user')->get();
                Notification::send($users, new NewOrderPlaced($order));

                return response()->json([
                    'status'    => true,
                    'message'   => 'Checkout initiated and answers saved',
                    'data'      => [
                                    'order_id' => $order->id,
                                    'total_amount' => $request->amount,
                                    'client_secret' => $paymentIntent->client_secret,
                                    'stripe_payment_id' => $paymentIntent->id,
                                    'answer_id' => $answer->id,
                                ],
                    ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'status'    => false,
                'message'   => 'Checkout initiation failed',
                'error'     => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * STEP 2: Confirm Payment
     * Frontend calls this after Stripe processing is done to update DB status.
     */
    // public function confirmPayment(Request $request)
    // {
    //     $request->validate([
    //         'payment_intent_id' => 'required|string',
    //     ]);

    //     try {
    //         // 1. Find the local order safely
    //         $order = Order::where('stripe_payment_id', $request->payment_intent_id)->first();

    //         if (! $order) {
    //             return response()->json([
    //                 'status'    => false,
    //                 'message'   => 'Order not found. Invalid Payment Intent ID.',
    //             ], 404);
    //         }

    //         if ($order->status === 'paid') {
    //             return response()->json([
    //                 'status'    => true,
    //                 'message'   => 'Order is already marked as paid.',
    //             ]);
    //         }

    //         // 2. SECURITY CHECK: Retrieve status from Stripe
    //         $intent = PaymentIntent::retrieve($request->payment_intent_id);

    //         if ($intent->status === 'succeeded') {
    //             // 3. Mark as Paid
    //             $order->update(['status' => 'paid']);

    //             return response()->json([
    //                 'status'    => true,
    //                 'message'   => 'Payment verified and order confirmed.',
    //                 'data'      => $order,
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'status'        => false,
    //                 'message'       => 'Payment not successful yet.',
    //                 'stripe_status' => $intent->status,
    //             ], 400);
    //         }

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'       => false,
    //             'message'      => 'Confirmation failed',
    //             'error'        => $e->getMessage(),
    //         ], 500);
    //     }
    // }
}
