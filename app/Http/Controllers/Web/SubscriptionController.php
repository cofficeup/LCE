<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
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
     * Show subscription creation form
     */
    public function create()
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('billing_cycle')
            ->orderBy('bags_per_month')
            ->get();

        return view('subscriptions.create', ['plans' => $plans]);
    }

    /**
     * Create subscription
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
            return back()->with('error', 'You already have an active subscription');
        }

        // Create subscription and invoice
        $subscription = $this->subscriptionService->createSubscription($user, $plan);

        // Get the invoice for this subscription
        $invoice = $subscription->invoices()->latest()->first();

        if (!$invoice) {
            // Fallback: create invoice if not created by service
            $invoice = $this->billingService->createSubscriptionInvoice(
                $user,
                null, // No pickup yet
                ['extra_bags' => 0, 'extra_bag_charge' => 0, 'overweight_lbs' => 0, 'overweight_charge' => 0]
            );

            // Add subscription charge as line item
            $this->billingService->addLineItem($invoice, [
                'type' => 'SUBSCRIPTION',
                'description' => $plan->name . ' Subscription',
                'quantity' => 1,
                'unit_price' => $plan->price,
            ]);

            $invoice->subtotal = $plan->price;
            $invoice->total = $plan->price;
            $invoice->save();
        }

        // Redirect to payment page
        return redirect()->route('payment.checkout', ['invoice_id' => $invoice->id])
            ->with('success', 'Subscription created! Please complete payment to activate.');
    }

    /**
     * Show subscription details
     */
    public function show($id)
    {
        $user = auth()->user();

        $subscription = $user->subscriptions()
            ->where('id', $id)
            ->with(['plan', 'bagUsage'])
            ->firstOrFail();

        return view('subscriptions.show', [
            'subscription' => $subscription,
            'availableBags' => $subscription->getAvailableBags(),
        ]);
    }

    /**
     * Show cancellation confirmation page
     */
    public function confirmCancel($id)
    {
        $user = auth()->user();

        $subscription = $user->subscriptions()
            ->where('id', $id)
            ->with('plan')
            ->firstOrFail();

        if ($subscription->status !== 'active') {
            return redirect('/dashboard')->with('error', 'This subscription cannot be cancelled.');
        }

        return view('subscriptions.confirm-cancel', [
            'subscription' => $subscription
        ]);
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request, $id)
    {
        \Log::info('Subscription cancellation POST received', [
            'subscription_id' => $id,
            'user_id' => auth()->id(),
            'method' => $request->method(),
            'url' => $request->fullUrl()
        ]);

        try {
            $user = $request->user();

            $subscription = $user->subscriptions()
                ->where('id', $id)
                ->firstOrFail();

            \Log::info('Subscription found for cancellation', [
                'status' => $subscription->status,
                'plan' => $subscription->plan->name,
            ]);

            // Check if already cancelled
            if ($subscription->status === 'cancelled') {
                \Log::warning('Attempted to cancel already cancelled subscription', ['subscription_id' => $id]);
                return redirect('/dashboard')->with('error', 'This subscription is already cancelled.');
            }

            $result = $this->subscriptionService->cancelSubscription($subscription, true);

            \Log::info('Subscription cancelled successfully', [
                'subscription_id' => $id,
                'refund_amount' => $result['refund_amount']
            ]);

            $message = 'Subscription cancelled successfully!';
            if ($result['refund_amount'] > 0) {
                $message .= ' Refund of $' . number_format($result['refund_amount'], 2) . ' has been added to your account credits.';
            } else {
                $message .= ' No refund applicable.';
            }

            return redirect('/dashboard')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Subscription cancellation error', [
                'subscription_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return redirect()->back()->with('error', 'Failed to cancel: ' . $e->getMessage());
        }
    }

    /**
     * Show plan change form
     */
    public function edit(Request $request, $id)
    {
        $subscription = $request->user()->subscriptions()->findOrFail($id);
        $plans = SubscriptionPlan::active()
            ->where('id', '!=', $subscription->plan_id)
            ->orderBy('billing_cycle')
            ->orderBy('bags_per_month')
            ->get();

        return view('subscriptions.edit', [
            'subscription' => $subscription,
            'plans' => $plans
        ]);
    }

    /**
     * Update subscription (Switch Plan)
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:lce_subscription_plans,id',
        ]);

        $subscription = $request->user()->subscriptions()->findOrFail($id);
        $newPlan = SubscriptionPlan::findOrFail($validated['plan_id']);

        $result = $this->subscriptionService->switchPlan($subscription, $newPlan);

        $message = "Plan changed to {$newPlan->name}.";
        if ($result['difference'] > 0) {
            $message .= " A charge of $" . number_format($result['difference'], 2) . " will be applied.";
        } elseif ($result['difference'] < 0) {
            $message .= " A credit of $" . number_format(abs($result['difference']), 2) . " has been applied to your account.";
        }

        return redirect()->route('subscriptions.show', $subscription->id)->with('success', $message);
    }

    /**
     * Pause subscription
     */
    public function pause(Request $request, $id)
    {
        $subscription = $request->user()->subscriptions()->findOrFail($id);

        try {
            $this->subscriptionService->pauseSubscription($subscription);
            return back()->with('success', 'Subscription paused successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Resume subscription
     */
    public function resume(Request $request, $id)
    {
        $subscription = $request->user()->subscriptions()->findOrFail($id);

        try {
            $this->subscriptionService->resumeSubscription($subscription);
            return back()->with('success', 'Subscription resumed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
