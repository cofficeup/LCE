<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\SubscriptionService;
use App\Services\BillingService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected BillingService $billingService
    ) {}

    /**
     * Create a new subscription
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:lce_subscription_plans,id',
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // Check if user already has active subscription
        if ($user->activeSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active subscription',
            ], 400);
        }

        // Create subscription
        $subscription = $this->subscriptionService->createSubscription($user, $plan);

        // Create subscription billing invoice
        // In production, charge the subscription fee here

        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully',
            'data' => [
                'subscription' => $subscription->load('plan'),
            ],
        ], 201);
    }

    /**
     * List user subscriptions
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $subscriptions = UserSubscription::where('user_id', $user->id)
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    /**
     * Get subscription details
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('id', $id)
            ->with(['plan', 'bagUsage'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'subscription' => $subscription,
                'available_bags' => $subscription->getAvailableBags(),
            ],
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request, int $id)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'immediate' => 'nullable|boolean',
        ]);

        $immediate = $validated['immediate'] ?? true;

        $result = $this->subscriptionService->cancelSubscription($subscription, $immediate);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
            'data' => [
                'subscription' => $result['subscription'],
                'refund_amount' => $result['refund_amount'],
            ],
        ]);
    }

    /**
     * Pause subscription
     */
    public function pause(Request $request, int $id)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $this->subscriptionService->pauseSubscription($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Subscription paused successfully',
            'data' => [
                'subscription' => $subscription->fresh(),
            ],
        ]);
    }

    /**
     * Resume subscription
     */
    public function resume(Request $request, int $id)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $this->subscriptionService->resumeSubscription($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Subscription resumed successfully',
            'data' => [
                'subscription' => $subscription->fresh(),
            ],
        ]);
    }

    /**
     * Upgrade subscription
     */
    public function upgrade(Request $request, int $id)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'new_plan_id' => 'required|exists:lce_subscription_plans,id',
        ]);

        $newPlan = SubscriptionPlan::findOrFail($validated['new_plan_id']);

        $subscription = $this->subscriptionService->upgradeSubscription($subscription, $newPlan);

        return response()->json([
            'success' => true,
            'message' => 'Subscription upgraded successfully',
            'data' => [
                'subscription' => $subscription->load('plan'),
            ],
        ]);
    }

    /**
     * Downgrade subscription
     */
    public function downgrade(Request $request, int $id)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'new_plan_id' => 'required|exists:lce_subscription_plans,id',
        ]);

        $newPlan = SubscriptionPlan::findOrFail($validated['new_plan_id']);

        $subscription = $this->subscriptionService->downgradeSubscription($subscription, $newPlan);

        return response()->json([
            'success' => true,
            'message' => 'Subscription downgraded successfully',
            'data' => [
                'subscription' => $subscription->load('plan'),
            ],
        ]);
    }
}
