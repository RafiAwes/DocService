<?php

namespace App\Services;

use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }

    /**
     * Create a Payment Intent.
     * This tells Stripe we are expecting a payment.
     */
    public function createPaymentIntent($amountInCents, $metadata = [])
    {
        return PaymentIntent::create([
            'amount' => $amountInCents,
            'currency' => 'usd',
            'metadata' => $metadata, // Attaches Order ID to the payment at Stripe
            'automatic_payment_methods' => [
                'enabled' => true, // Allows Card, Apple Pay, Google Pay automatically
            ],
        ]);
    }
}
