<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPickup;
use App\Services\PPOPricingService;
use App\Services\BillingService;
use App\Services\SchedulingService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function __construct(
        protected PPOPricingService $pricingService,
        protected BillingService $billingService,
        protected SchedulingService $schedulingService
    ) {}

    /**
     * Create a new PPO order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'weight_lbs' => 'required|numeric|min:0',
            'dc_items' => 'nullable|array',
            'dc_items.*.type' => 'required|string',
            'dc_items.*.quantity' => 'required|integer|min:1',
            'hd_items' => 'nullable|array',
            'hd_items.*.type' => 'required|string',
            'hd_items.*.quantity' => 'required|integer|min:1',
            'pickup_type' => 'required|in:asap,future',
            'pickup_date' => 'required_if:pickup_type,future|nullable|date',
            'pickup_zone' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // Calculate pricing
        $pricing = $this->pricingService->calculatePPOPrice(
            $validated['weight_lbs'],
            $validated['dc_items'] ?? [],
            $validated['hd_items'] ?? []
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

        // Record transaction (in production, integrate with payment gateway)
        $finalAmount = $invoice->getFinalAmount();
        if ($finalAmount > 0) {
            $this->billingService->recordTransaction($invoice, $finalAmount, 'card');
        }

        return response()->json([
            'success' => true,
            'message' => 'PPO order created successfully',
            'data' => [
                'pickup' => $pickup->load('invoice'),
                'invoice' => $invoice->load('lineItems'),
                'pricing_breakdown' => $pricing,
            ],
        ], 201);
    }

    /**
     * List user orders
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $pickups = UserPickup::where('user_id', $user->id)
            ->with(['invoice.lineItems'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $pickups,
        ]);
    }

    /**
     * Get order details
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $pickup = UserPickup::where('user_id', $user->id)
            ->where('id', $id)
            ->with(['invoice.lineItems', 'invoice.transactions'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $pickup,
        ]);
    }
}
