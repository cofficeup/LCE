<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\UserInfo;
use App\Models\UserSubscription;
use App\Models\SubscriptionBagUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Create a new subscription
     */
    public function createSubscription(UserInfo $user, SubscriptionPlan $plan, ?Carbon $startDate = null): UserSubscription
    {
        $startDate = $startDate ?? now();

        // Calculate next billing date
        $nextBillingDate = $plan->billing_cycle === 'yearly'
            ? $startDate->copy()->addYear()
            : $startDate->copy()->addMonth();

        return DB::transaction(function () use ($user, $plan, $startDate, $nextBillingDate) {
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'start_date' => $startDate,
                'next_billing_date' => $nextBillingDate,
                'banked_bags' => 0,
            ]);

            // Create initial invoice (handled by BillingService separately)

            return $subscription;
        });
    }

    /**
     * Cancel subscription
     * 
     * @param bool $immediate If true, cancel immediately. Otherwise, cancel at end of billing period
     * @return array ['subscription' => UserSubscription, 'refund_amount' => float]
     */
    public function cancelSubscription(UserSubscription $subscription, bool $immediate = true): array
    {
        $refundAmount = 0;

        if ($immediate) {
            $refundAmount = $this->calculateRefund($subscription);

            DB::transaction(function () use ($subscription, $refundAmount) {
                $subscription->status = 'cancelled';
                $subscription->cancelled_at = now();
                $subscription->save();

                // Add refund credit if applicable
                if ($refundAmount > 0) {
                    $this->creditService->addRefundCredit(
                        $subscription->user,
                        $refundAmount,
                        "Refund for cancelled subscription #{$subscription->id}"
                    );
                }
            });
        } else {
            // Cancel at end of billing period
            $subscription->status = 'cancelled';
            $subscription->cancelled_at = $subscription->next_billing_date;
            $subscription->save();
        }

        return [
            'subscription' => $subscription,
            'refund_amount' => $refundAmount,
        ];
    }

    /**
     * Calculate refund amount
     * Rules:
     * - < 5 days from start: full refund
     * - >= 5 days from start: refund minus $100 penalty (annual only)
     * - Monthly: no refund after 5 days
     */
    public function calculateRefund(UserSubscription $subscription): float
    {
        $daysSinceStart = $subscription->getDaysSinceStart();
        $graceDays = config('lce.refund_grace_days', 5);
        $plan = $subscription->plan;

        // Within grace period: full refund
        if ($daysSinceStart < $graceDays) {
            return (float) $plan->price;
        }

        // After grace period
        if ($plan->billing_cycle === 'yearly') {
            $penalty = config('lce.refund_penalty_annual', 100.00);
            $refund = (float) $plan->price - $penalty;
            return max(0, $refund);
        }

        // Monthly subscriptions: no refund after grace period
        return 0;
    }

    /**
     * Pause subscription
     */
    public function pauseSubscription(UserSubscription $subscription): void
    {
        if ($subscription->status !== 'active') {
            throw new \Exception('Can only pause active subscriptions');
        }

        $subscription->status = 'paused';
        $subscription->paused_at = now();
        $subscription->save();
    }

    /**
     * Resume subscription
     */
    public function resumeSubscription(UserSubscription $subscription): void
    {
        if ($subscription->status !== 'paused') {
            throw new \Exception('Can only resume paused subscriptions');
        }

        $daysPaused = now()->diffInDays($subscription->paused_at);

        DB::transaction(function () use ($subscription, $daysPaused) {
            // Extend billing date by paused days
            $subscription->next_billing_date = $subscription->next_billing_date->addDays($daysPaused);
            $subscription->status = 'active';
            $subscription->paused_at = null;
            $subscription->save();
        });
    }

    /**
     * Upgrade subscription to a higher plan
     */
    public function upgradeSubscription(UserSubscription $subscription, SubscriptionPlan $newPlan): UserSubscription
    {
        if ($newPlan->bags_per_month <= $subscription->plan->bags_per_month) {
            throw new \Exception('New plan must have more bags than current plan');
        }

        DB::transaction(function () use ($subscription, $newPlan) {
            // Calculate prorated credit/charge (simplified: switch immediately)
            $subscription->plan_id = $newPlan->id;
            $subscription->save();
        });

        return $subscription->fresh();
    }

    /**
     * Downgrade subscription to a lower plan
     */
    public function downgradeSubscription(UserSubscription $subscription, SubscriptionPlan $newPlan): UserSubscription
    {
        if ($newPlan->bags_per_month >= $subscription->plan->bags_per_month) {
            throw new \Exception('New plan must have fewer bags than current plan');
        }

        // Downgrade takes effect at next billing cycle
        DB::transaction(function () use ($subscription, $newPlan) {
            // In production, you might store this as a pending change
            // For simplicity, we'll apply it immediately
            $subscription->plan_id = $newPlan->id;
            $subscription->save();
        });

        return $subscription->fresh();
    }

    /**
     * Renew subscription (called by scheduler)
     */
    public function renewSubscription(UserSubscription $subscription): void
    {
        if ($subscription->status !== 'active') {
            return;
        }

        $plan = $subscription->plan;

        DB::transaction(function () use ($subscription, $plan) {
            // Update next billing date
            $subscription->next_billing_date = $plan->billing_cycle === 'yearly'
                ? $subscription->next_billing_date->addYear()
                : $subscription->next_billing_date->addMonth();

            // Reset or bank unused bags (bank unused bags)
            $subscription->banked_bags += $plan->bags_per_month;

            $subscription->save();

            // Create renewal invoice (handled by BillingService)
        });
    }

    /**
     * Process bag usage for a subscription pickup
     * 
     * @param UserSubscription $subscription
     * @param int $totalBags Total bags in this pickup
     * @param float $totalWeight Total weight in lbs
     * @return array Breakdown with charges
     */
    public function processBagUsage(UserSubscription $subscription, int $totalBags, float $totalWeight): array
    {
        $plan = $subscription->plan;
        $bagWeightLimit = config('lce.bag_weight_lbs', 20.5);

        // Calculate available bags (banked + monthly quota)
        $availableBags = $subscription->getAvailableBags();

        // Determine bags used from quota vs extra
        $bagsFromQuota = min($totalBags, $availableBags);
        $extraBags = max(0, $totalBags - $availableBags);

        // Calculate expected weight vs actual weight
        $expectedWeight = $totalBags * $bagWeightLimit;
        $overweightLbs = max(0, $totalWeight - $expectedWeight);

        // Calculate charges
        $extraBagCharge = $extraBags * (float) $plan->bag_overage_rate;
        $overweightCharge = $overweightLbs * (float) $plan->price_per_lb_overage;

        $totalCharge = $extraBagCharge + $overweightCharge;

        // Update subscription banked bags
        $subscription->banked_bags = max(0, $subscription->banked_bags - $bagsFromQuota);
        $subscription->save();

        return [
            'bags_from_quota' => $bagsFromQuota,
            'extra_bags' => $extraBags,
            'overweight_lbs' => $overweightLbs,
            'extra_bag_charge' => $extraBagCharge,
            'overweight_charge' => $overweightCharge,
            'total_charge' => $totalCharge,
            'remaining_banked_bags' => $subscription->banked_bags,
        ];
    }

    /**
     * Record bag usage
     */
    public function recordBagUsage(UserSubscription $subscription, int $pickupId, array $usageData): SubscriptionBagUsage
    {
        return SubscriptionBagUsage::create([
            'subscription_id' => $subscription->id,
            'pickup_id' => $pickupId,
            'bags_used' => $usageData['bags_from_quota'],
            'extra_bags' => $usageData['extra_bags'],
            'overweight_lbs' => $usageData['overweight_lbs'],
        ]);
    }
}
