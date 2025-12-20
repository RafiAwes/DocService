<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Order;
use App\Model\Service;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\StripePaymentService;
use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    protected $payementService;

    public function __construct(StripePaymentService $payementService)
    {
        $this->payementService = $payementService;
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'is_south_africa' => 'required|boolean',
            'items'           => 'required|array|min:1',
            'items.*.service_id'   => 'required|exists:services,id',
            'items.*.quantity'     => 'required|integer|min:1',
            // delivery_ids should be an array of integers (IDs)
            'items.*.delivery_ids' => 'nullable|array', 
            'items.*.delivery_ids.*' => 'exists:delivery_options,id',
        ]);

        $user = Auth::user();
        $inputItems = $request->input('items');

        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $orderItemsData = [];

            foreach ($inputItems as $item) {
                $service    = Service::findOrFail($item['service_id']);
                $quantity   = $item['quantity'];
                $price      = $service->price;
                $subtotal   = $price * $quantity;
                $totalAmount += $subtotal;

                // Prepare data for later insertion
                $orderItemsData[] = [
                    'service_obj'   => $service, // Keep reference for later
                    'quantity'      => $quantity,
                    'price'         => $price,
                    'subtotal'      => $subtotal,
                    'delivery_ids'  => $item['delivery_ids'] ?? [],
                ];
            }
            // Create Stripe Payment Intent    
            $paymentIntent = $this->payementService->createPaymentIntent($totalAmount, 'usd', [
                'user_id'=> $user->id,
                'email'  => $user->email,
                'is_south_africa' => $request->is_south_africa? 'true' : 'false',
            ]);

            //Create the order
            $order = Order::create([
                'user_id'           => $user->id,
                'total_amount'      => $totalAmount,
                'is_south_africa'   => $request->is_south_africa,
                'stripe_payment_id' => $paymentIntent->id,
                'status'            => 'pending',
            ]);
            //Create Items and Attach Delivery Options
            foreach ($orderItemsData as $data) {
                $orderItem = OrderItem::create([
                    'order_id'   => $order->id,
                    'service_id' => $data['service_obj']->id,
                    'quantity'   => $data['quantity'],
                    'price'      => $data['price'],
                    'subtotal'   => $data['subtotal'],
                ]);

                // Attach the delivery IDs to this specific item
                if (!empty($data['delivery_ids'])) {
                    $orderItem->deliveryOptions()->attach($data['delivery_ids']);
                }
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order_id'      => $order->id,
                    'total_amount'  => $totalAmount,
                    'client_secret' => $paymentIntent->client_secret, // Required by frontend Stripe.js
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order placement failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to place order. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
