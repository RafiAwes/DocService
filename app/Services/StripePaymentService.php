<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Exception;

class StripePaymentService
{
    public function __construct()
    {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
        }

        public function createPaymentIntent($amount, $currency = 'usd', $metadata = [])
        {
            try {
                $paymentIntent = PaymentIntent::create([
                    'amount' => $amount,
                    'currency' => $currency,
                    'metadata' => $metadata,
                    'automatic_payment_methods' => [
                        'enabled' => true,
                    ],
                ]);

                return $paymentIntent;
            } catch (Exception $e) {
                throw new Exception('Error creating payment intent: ' . $e->getMessage());
            }
        }
    }