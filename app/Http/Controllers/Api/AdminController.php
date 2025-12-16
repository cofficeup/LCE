<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserInfo;
use App\Models\UserSubscription;
use App\Services\CreditService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        protected CreditService $creditService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Search customers
     */
    public function searchCustomers(Request $request)
    {
        $query = UserInfo::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->where('role', 'customer')
            ->withCount('subscriptions', 'pickups', 'invoices')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * Get customer details
     */
    public function getCustomer(int $id)
    {
        $customer = UserInfo::where('id', $id)
            ->with(['subscriptions.plan', 'credits'])
            ->withCount('pickups', 'invoices')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer,
                'available_credit' => $customer->getAvailableCredit(),
                'active_subscription' => $customer->activeSubscription(),
            ],
        ]);
    }

    /**
     * Get customer subscriptions
     */
    public function getCustomerSubscriptions(int $id)
    {
        $subscriptions = UserSubscription::where('user_id', $id)
            ->with(['plan', 'bagUsage'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    /**
     * Add manual credit to customer
     */
    public function addCredit(Request $request, int $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $customer = UserInfo::findOrFail($id);

        $credit = $this->creditService->addManualCredit(
            $customer,
            $validated['amount'],
            $validated['description']
        );

        return response()->json([
            'success' => true,
            'message' => 'Credit added successfully',
            'data' => [
                'credit' => $credit,
                'new_balance' => $customer->getAvailableCredit(),
            ],
        ], 201);
    }

    /**
     * Admin cancel subscription
     */
    public function cancelSubscription(Request $request, int $subscriptionId)
    {
        $subscription = UserSubscription::findOrFail($subscriptionId);

        $validated = $request->validate([
            'immediate' => 'nullable|boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        $immediate = $validated['immediate'] ?? true;

        $result = $this->subscriptionService->cancelSubscription($subscription, $immediate);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
            'data' => [
                'subscription' => $result['subscription'],
                'refund_amount' => $result['refund_amount'],
                'reason' => $validated['reason'] ?? null,
            ],
        ]);
    }

    /**
     * Get all subscription plans
     */
    public function getPlans(Request $request)
    {
        $plans = \App\Models\SubscriptionPlan::active()
            ->orderBy('billing_cycle')
            ->orderBy('bags_per_month')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }
}
