<?php

namespace App\Services;

use App\Models\UserInfo;
use App\Models\UserInvoice;
use App\Models\UserInvoiceLine;
use App\Models\UserTransaction;
use App\Models\UserPickup;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(
        protected CreditService $creditService,
        protected StripePaymentService $stripeService
    ) {}

    /**
     * Create invoice for a pickup/order
     * 
     * @param UserInfo $user
     * @param UserPickup $pickup
     * @param array $lineItems Array of line items
     * @return UserInvoice
     */
    public function createInvoice(UserInfo $user, UserPickup $pickup, array $lineItems): UserInvoice
    {
        return DB::transaction(function () use ($user, $pickup, $lineItems) {
            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // Create invoice
            $invoice = UserInvoice::create([
                'user_id' => $user->id,
                'pickup_id' => $pickup->id,
                'invoice_number' => $invoiceNumber,
                'subtotal' => 0,
                'credits_applied' => 0,
                'total' => 0,
                'status' => 'pending',
                'order_type' => $pickup->order_type,
            ]);

            // Add line items and calculate total
            $subtotal = 0;
            foreach ($lineItems as $item) {
                $lineTotal = $this->addLineItem($invoice, $item);
                $subtotal += $lineTotal;
            }

            // Update invoice totals
            $invoice->subtotal = $subtotal;
            $invoice->total = $subtotal;
            $invoice->save();

            // Apply credits automatically
            $this->creditService->applyCreditsToInvoice($invoice);

            return $invoice->fresh();
        });
    }

    /**
     * Add line item to invoice
     * 
     * @param UserInvoice $invoice
     * @param array $itemData ['type' => string, 'description' => string, 'quantity' => float, 'unit_price' => float]
     * @return float Line total
     */
    public function addLineItem(UserInvoice $invoice, array $itemData): float
    {
        $quantity = $itemData['quantity'] ?? 1;
        $unitPrice = $itemData['unit_price'] ?? 0;
        $total = $quantity * $unitPrice;

        UserInvoiceLine::create([
            'invoice_id' => $invoice->id,
            'line_type' => $itemData['type'],
            'description' => $itemData['description'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $total,
        ]);

        return $total;
    }

    /**
     * Create invoice for PPO order
     */
    public function createPPOInvoice(UserInfo $user, UserPickup $pickup, array $pricingData): UserInvoice
    {
        $lineItems = [];

        // Wash & Fold
        if ($pricingData['wash_fold']['charge'] > 0) {
            $lineItems[] = [
                'type' => 'WF',
                'description' => "Wash & Fold ({$pricingData['wash_fold']['weight_lbs']} lbs @ $" . $pricingData['wash_fold']['rate_per_lb'] . "/lb)",
                'quantity' => $pricingData['wash_fold']['weight_lbs'],
                'unit_price' => $pricingData['wash_fold']['rate_per_lb'],
            ];
        }

        // Dry Cleaning
        if (!empty($pricingData['dry_cleaning']['items'])) {
            foreach ($pricingData['dry_cleaning']['items'] as $item) {
                $lineItems[] = [
                    'type' => 'DC',
                    'description' => "Dry Cleaning - {$item['type']}",
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['price'] ?? 0,
                ];
            }
        }

        // Heavy Duty
        if (!empty($pricingData['heavy_duty']['items'])) {
            foreach ($pricingData['heavy_duty']['items'] as $item) {
                $lineItems[] = [
                    'type' => 'HD',
                    'description' => "Heavy Duty - {$item['type']}",
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['price'] ?? 0,
                ];
            }
        }

        // Pickup & Delivery Fee
        $lineItems[] = [
            'type' => 'FEE_PND',
            'description' => 'Pickup & Delivery Fee',
            'quantity' => 1,
            'unit_price' => $pricingData['fees']['pickup_delivery'],
        ];

        // Service Fee
        $lineItems[] = [
            'type' => 'FEE_SERVICE',
            'description' => 'Service Fee',
            'quantity' => 1,
            'unit_price' => $pricingData['fees']['service'],
        ];

        return $this->createInvoice($user, $pickup, $lineItems);
    }

    /**
     * Create invoice for subscription order
     */
    public function createSubscriptionInvoice(UserInfo $user, UserPickup $pickup, array $usageData): UserInvoice
    {
        $lineItems = [];

        // Extra bags charge
        if ($usageData['extra_bags'] > 0 && $usageData['extra_bag_charge'] > 0) {
            $lineItems[] = [
                'type' => 'SUBSCRIPTION_BAG',
                'description' => "Extra Bags ({$usageData['extra_bags']} bags)",
                'quantity' => $usageData['extra_bags'],
                'unit_price' => $usageData['extra_bag_charge'] / $usageData['extra_bags'],
            ];
        }

        // Overweight charge
        if ($usageData['overweight_lbs'] > 0 && $usageData['overweight_charge'] > 0) {
            $lineItems[] = [
                'type' => 'SUB_OVERWEIGHT_LBS',
                'description' => "Overweight ({$usageData['overweight_lbs']} lbs)",
                'quantity' => $usageData['overweight_lbs'],
                'unit_price' => $usageData['overweight_charge'] / $usageData['overweight_lbs'],
            ];
        }

        // If no charges, create a zero-total invoice
        if (empty($lineItems)) {
            $lineItems[] = [
                'type' => 'SUBSCRIPTION_BAG',
                'description' => 'Subscription order (no additional charges)',
                'quantity' => 0,
                'unit_price' => 0,
            ];
        }

        return $this->createInvoice($user, $pickup, $lineItems);
    }

    /**
     * Record payment transaction with Stripe
     */
    public function recordTransaction(
        UserInvoice $invoice,
        float $amount,
        ?string $paymentMethodId = null,
        string $paymentMethod = 'card'
    ): UserTransaction {
        return DB::transaction(function () use ($invoice, $amount, $paymentMethod, $paymentMethodId) {
            // Only process payment if amount > 0
            if ($amount > 0 && $paymentMethodId) {
                try {
                    // Get or create Stripe customer
                    $customerId = $this->stripeService->getOrCreateCustomer($invoice->user);

                    // Process payment with Stripe
                    $paymentIntent = $this->stripeService->processPayment(
                        $amount,
                        $paymentMethodId,
                        $customerId,
                        [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'user_id' => $invoice->user_id,
                        ]
                    );

                    // Record transaction
                    $transaction = UserTransaction::create([
                        'user_id' => $invoice->user_id,
                        'invoice_id' => $invoice->id,
                        'transaction_type' => 'charge',
                        'amount' => $amount,
                        'payment_method' => $paymentMethod,
                        'status' => $paymentIntent->status === 'succeeded' ? 'completed' : 'pending',
                        'transaction_id' => $paymentIntent->id,
                        'notes' => 'Stripe Payment Intent',
                    ]);

                    // Update invoice status
                    if ($paymentIntent->status === 'succeeded') {
                        $invoice->status = 'paid';
                        $invoice->save();
                    }

                    return $transaction;
                } catch (\Exception $e) {
                    // Log the error and create failed transaction
                    $transaction = UserTransaction::create([
                        'user_id' => $invoice->user_id,
                        'invoice_id' => $invoice->id,
                        'transaction_type' => 'charge',
                        'amount' => $amount,
                        'payment_method' => $paymentMethod,
                        'status' => 'failed',
                        'notes' => 'Payment failed: ' . $e->getMessage(),
                    ]);

                    throw new \Exception('Payment processing failed: ' . $e->getMessage());
                }
            } else {
                // No payment needed (fully covered by credits)
                $transaction = UserTransaction::create([
                    'user_id' => $invoice->user_id,
                    'invoice_id' => $invoice->id,
                    'transaction_type' => 'credit',
                    'amount' => 0,
                    'payment_method' => 'credit',
                    'status' => 'completed',
                    'notes' => 'Fully covered by credits',
                ]);

                $invoice->status = 'paid';
                $invoice->save();

                return $transaction;
            }
        });
    }

    /**
     * Process refund with Stripe
     */
    public function processRefund(UserInvoice $invoice, float $amount, string $reason): UserTransaction
    {
        return DB::transaction(function () use ($invoice, $amount, $reason) {
            // Find the original charge transaction
            $chargeTransaction = $invoice->transactions()
                ->where('transaction_type', 'charge')
                ->where('status', 'completed')
                ->first();

            if ($chargeTransaction && $chargeTransaction->transaction_id) {
                try {
                    // Process Stripe refund
                    $refund = $this->stripeService->createRefund(
                        $chargeTransaction->transaction_id,
                        $amount
                    );

                    $transaction = UserTransaction::create([
                        'user_id' => $invoice->user_id,
                        'invoice_id' => $invoice->id,
                        'transaction_type' => 'refund',
                        'amount' => $amount,
                        'payment_method' => 'refund',
                        'status' => 'completed',
                        'transaction_id' => $refund->id,
                        'notes' => $reason,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Stripe refund failed: ' . $e->getMessage());
                }
            } else {
                // Manual refund (no Stripe transaction)
                $transaction = UserTransaction::create([
                    'user_id' => $invoice->user_id,
                    'invoice_id' => $invoice->id,
                    'transaction_type' => 'refund',
                    'amount' => $amount,
                    'payment_method' => 'manual',
                    'status' => 'completed',
                    'notes' => $reason,
                ]);
            }

            // Update invoice status
            $invoice->status = 'refunded';
            $invoice->save();

            return $transaction;
        });
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));
        return "INV-{$date}-{$random}";
    }
}
