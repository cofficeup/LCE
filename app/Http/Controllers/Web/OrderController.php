<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PPOPricingService;
use App\Services\BillingService;
use App\Services\SchedulingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected PPOPricingService $pricingService,
        protected BillingService $billingService,
        protected SchedulingService $schedulingService
    ) {}

    /**
     * Show PPO order creation form
     */
    public function create()
    {
        return view('orders.create');
    }

    /**
     * Create PPO order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'weight_lbs' => 'required|numeric|min:0',
            'pickup_type' => 'required|in:asap,future',
            'pickup_date' => 'required_if:pickup_type,future|nullable|date',
            'pickup_zone' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // Calculate pricing
        $pricing = $this->pricingService->calculatePPOPrice(
            $validated['weight_lbs'],
            [],
            []
        );

        // Schedule pickup
        if ($validated['pickup_type'] === 'asap') {
            $pickup = $this->schedulingService->scheduleASAP(
                $user,
                $validated['pickup_zone'],
                'ppo',
                [
                    'weight_lbs' => $validated['weight_lbs'],
                    'notes' => $validated['notes'] ?? null,
                ]
            );
        } else {
            $pickup = $this->schedulingService->scheduleFuture(
                $user,
                Carbon::parse($validated['pickup_date']),
                $validated['pickup_zone'],
                'ppo',
                [
                    'weight_lbs' => $validated['weight_lbs'],
                    'notes' => $validated['notes'] ?? null,
                ]
            );
        }

        // Create invoice
        $invoice = $this->billingService->createPPOInvoice($user, $pickup, $pricing);

        // Redirect to payment page
        return redirect()->route('payment.checkout', ['invoice_id' => $invoice->id])
            ->with('success', 'Order created! Please complete payment.');
    }

    /**
     * List user orders
     */
    public function index(Request $request)
    {
        $orders = $request->user()
            ->pickups()
            ->with(['invoice'])
            ->latest()
            ->paginate(10);

        return view('orders.index', ['orders' => $orders]);
    }

    /**
     * Show order details
     */
    public function show(Request $request, $id)
    {
        $order = $request->user()
            ->pickups()
            ->with(['invoice', 'invoice.lineItems'])
            ->findOrFail($id);

        return view('orders.show', ['order' => $order]);
    }
}
