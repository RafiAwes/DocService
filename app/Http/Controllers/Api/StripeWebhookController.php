<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Order;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // 1. Get the payload and signature
        $payload = @file_get_contents('php://input');
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            // 2. Verify the event came from Stripe (Security)
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid Signature'], 400);
        }

        // 3. Handle the event type
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentSuccess($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentFailure($paymentIntent);
                break;
            
            // Add other cases like 'charge.refunded' if needed
        }

        return response()->json(['status' => 'success']);
    }

    protected function handlePaymentSuccess($paymentIntent)
    {
        // Find order by the Stripe ID we saved during Checkout
        $order = Order::where('stripe_payment_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update(['status' => 'paid']);
            Log::info("Order #{$order->id} marked as paid via Stripe Webhook.");
        }
    }

    protected function handlePaymentFailure($paymentIntent)
    {
        $order = Order::where('stripe_payment_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update(['status' => 'failed']);
            Log::warning("Order #{$order->id} payment failed.");
        }
    }
}