<?php

namespace App\Services;

use App\Models\UserCredit;
use App\Models\UserInfo;
use App\Models\UserInvoice;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Create welcome credit for new user
     */
    public function createWelcomeCredit(UserInfo $user): UserCredit
    {
        $amount = config('lce.welcome_credit', 20.00);

        return UserCredit::create([
            'user_id' => $user->id,
            'type' => 'welcome',
            'amount' => $amount,
            'remaining_amount' => $amount,
            'description' => 'Welcome credit for new customer',
            'expires_at' => null, // Never expires
        ]);
    }

    /**
     * Add manual credit (admin function)
     */
    public function addManualCredit(UserInfo $user, float $amount, string $description): UserCredit
    {
        return UserCredit::create([
            'user_id' => $user->id,
            'type' => 'manual',
            'amount' => $amount,
            'remaining_amount' => $amount,
            'description' => $description,
            'expires_at' => null,
        ]);
    }

    /**
     * Add refund credit
     */
    public function addRefundCredit(UserInfo $user, float $amount, string $description): UserCredit
    {
        return UserCredit::create([
            'user_id' => $user->id,
            'type' => 'refund',
            'amount' => $amount,
            'remaining_amount' => $amount,
            'description' => $description,
            'expires_at' => null,
        ]);
    }

    /**
     * Add promotional credit
     */
    public function addPromoCredit(UserInfo $user, float $amount, string $description, ?\DateTime $expiresAt = null): UserCredit
    {
        return UserCredit::create([
            'user_id' => $user->id,
            'type' => 'promo',
            'amount' => $amount,
            'remaining_amount' => $amount,
            'description' => $description,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Get user's available credit balance
     */
    public function getAvailableBalance(UserInfo $user): float
    {
        return (float) $user->credits()
            ->available()
            ->sum('remaining_amount');
    }

    /**
     * Apply credits to an invoice (oldest first)
     * Returns the amount of credits applied
     */
    public function applyCreditsToInvoice(UserInvoice $invoice): float
    {
        $user = $invoice->user;
        $invoiceTotal = (float) $invoice->total;

        if ($invoiceTotal <= 0) {
            return 0;
        }

        $credits = $user->credits()
            ->available()
            ->orderBy('created_at', 'asc')
            ->get();

        $totalApplied = 0;
        $remainingAmount = $invoiceTotal;

        DB::transaction(function () use ($credits, &$totalApplied, &$remainingAmount) {
            foreach ($credits as $credit) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $creditAmount = (float) $credit->remaining_amount;
                $amountToUse = min($creditAmount, $remainingAmount);

                // Use the credit
                $credit->use($amountToUse);

                $totalApplied += $amountToUse;
                $remainingAmount -= $amountToUse;
            }
        });

        // Update invoice
        $invoice->credits_applied = $totalApplied;
        $invoice->save();

        return $totalApplied;
    }

    /**
     * Get credit history for a user
     */
    public function getCreditHistory(UserInfo $user)
    {
        return $user->credits()
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
