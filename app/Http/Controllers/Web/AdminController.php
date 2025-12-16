<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\UserInfo;
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
     * Admin dashboard
     */
    public function index()
    {
        $stats = [
            'total_customers' => UserInfo::where('role', 'customer')->count(),
            'active_subscriptions' => \App\Models\UserSubscription::where('status', 'active')->count(),
            'total_orders_today' => \App\Models\UserPickup::whereDate('created_at', today())->count(),
        ];

        return view('admin.dashboard', $stats);
    }

    /**
     * Search customers
     */
    public function customers(Request $request)
    {
        $query = UserInfo::query()->where('role', 'customer');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->withCount('subscriptions', 'pickups')
            ->paginate(20);

        return view('admin.customers', ['customers' => $customers]);
    }

    /**
     * Show customer details
     */
    public function showCustomer($id)
    {
        $customer = UserInfo::where('id', $id)
            ->with(['subscriptions.plan', 'credits', 'pickups', 'invoices'])
            ->firstOrFail();

        return view('admin.customer-details', [
            'customer' => $customer,
            'availableCredit' => $customer->getAvailableCredit(),
        ]);
    }

    /**
     * Add manual credit
     */
    public function addCredit(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $customer = UserInfo::findOrFail($id);

        $this->creditService->addManualCredit(
            $customer,
            $validated['amount'],
            $validated['description']
        );

        return back()->with('success', 'Credit added successfully');
    }

    /**
     * Cancel customer subscription
     */
    public function cancelSubscription($id)
    {
        $subscription = \App\Models\UserSubscription::findOrFail($id);

        $this->subscriptionService->cancelSubscription($subscription, true);

        return back()->with('success', 'Subscription cancelled successfully');
    }
}
