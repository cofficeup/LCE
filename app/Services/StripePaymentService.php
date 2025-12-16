<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Charge;
use App\Models\UserInfo;

class StripePaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret_key'));
    }

    /**
     * Create a Stripe customer
     */
    public function createCustomer(UserInfo $user): string
    {
        try {
            $customer = Customer::create([
                'email' => $user->email,
                'name' => $user->name,
                'phone' => $user->phone,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            // Save Stripe customer ID to user
            $user->stripe_customer_id = $customer->id;
            $user->save();

            return $customer->id;
        } catch (\Exception $e) {
            throw new \Exception('Failed to create Stripe customer: ' . $e->getMessage());
        }
    }

    /**
     * Create a payment intent
     */
    public function createPaymentIntent(float $amount, string $currency = 'usd', array $metadata = []): PaymentIntent
    {
        try {
            return PaymentIntent::create([
                'amount' => round($amount * 100), // Convert to cents
                'currency' => $currency,
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to create payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Charge a customer using saved payment method
     */
    public function chargeCustomer(
        string $customerId,
        float $amount,
        string $description,
        array $metadata = []
    ): Charge {
        try {
            return Charge::create([
                'customer' => $customerId,
                'amount' => round($amount * 100), // Convert to cents
                'currency' => 'usd',
                'description' => $description,
                'metadata' => $metadata,
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to charge customer: ' . $e->getMessage());
        }
    }

    /**
     * Process a direct payment with payment method
     */
    public function processPayment(
        float $amount,
        string $paymentMethodId,
        string $customerId = null,
        array $metadata = []
    ): PaymentIntent {
        try {
            $params = [
                'amount' => round($amount * 100), // Convert to cents
                'currency' => 'usd',
                'payment_method' => $paymentMethodId,
                'confirm' => true,
                'metadata' => $metadata,
                'return_url' => url('/payment/complete'),
            ];

            if ($customerId) {
                $params['customer'] = $customerId;
            }

            return PaymentIntent::create($params);
        } catch (\Exception $e) {
            throw new \Exception('Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a refund
     */
    public function createRefund(string $chargeId, float $amount = null): \Stripe\Refund
    {
        try {
            $params = ['charge' => $chargeId];

            if ($amount !== null) {
                $params['amount'] = round($amount * 100); // Convert to cents
            }

            return \Stripe\Refund::create($params);
        } catch (\Exception $e) {
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve payment intent
     */
    public function getPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (\Exception $e) {
            throw new \Exception('Failed to retrieve payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Get Stripe customer or create if doesn't exist
     */
    public function getOrCreateCustomer(UserInfo $user): string
    {
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        return $this->createCustomer($user);
    }
}
