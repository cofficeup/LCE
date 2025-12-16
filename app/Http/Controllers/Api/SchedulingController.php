<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SchedulingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SchedulingController extends Controller
{
    public function __construct(
        protected SchedulingService $schedulingService
    ) {}

    /**
     * Schedule ASAP pickup
     */
    public function asap(Request $request)
    {
        $validated = $request->validate([
            'pickup_zone' => 'required|string',
            'order_type' => 'required|in:ppo,subscription',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $pickup = $this->schedulingService->scheduleASAP(
            $user,
            $validated['pickup_zone'],
            $validated['order_type'],
            ['notes' => $validated['notes'] ?? null]
        );

        return response()->json([
            'success' => true,
            'message' => 'ASAP pickup scheduled',
            'data' => $pickup,
        ], 201);
    }

    /**
     * Schedule future pickup
     */
    public function future(Request $request)
    {
        $validated = $request->validate([
            'pickup_date' => 'required|date|after:today',
            'pickup_zone' => 'required|string',
            'order_type' => 'required|in:ppo,subscription',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $pickup = $this->schedulingService->scheduleFuture(
            $user,
            Carbon::parse($validated['pickup_date']),
            $validated['pickup_zone'],
            $validated['order_type'],
            ['notes' => $validated['notes'] ?? null]
        );

        return response()->json([
            'success' => true,
            'message' => 'Future pickup scheduled',
            'data' => $pickup,
        ], 201);
    }

    /**
     * Schedule recurring pickup
     */
    public function recurring(Request $request)
    {
        $validated = $request->validate([
            'frequency' => 'required|in:weekly,biweekly',
            'pickup_zone' => 'required|string',
            'order_type' => 'required|in:ppo,subscription',
            'occurrences' => 'nullable|integer|min:1|max:52',
        ]);

        $user = $request->user();

        $pickups = $this->schedulingService->scheduleRecurring(
            $user,
            $validated['frequency'],
            $validated['pickup_zone'],
            $validated['order_type'],
            $validated['occurrences'] ?? 4
        );

        return response()->json([
            'success' => true,
            'message' => 'Recurring pickups scheduled',
            'data' => [
                'pickups' => $pickups,
                'count' => count($pickups),
            ],
        ], 201);
    }

    /**
     * Get available pickup dates
     */
    public function availableDates(Request $request)
    {
        $validated = $request->validate([
            'pickup_zone' => 'required|string',
            'days_ahead' => 'nullable|integer|min:1|max:30',
        ]);

        $daysAhead = $validated['days_ahead'] ?? 14;
        $availableDates = [];

        $currentDate = now();
        for ($i = 0; $i < $daysAhead; $i++) {
            $date = $currentDate->copy()->addDays($i);
            if ($this->schedulingService->isValidPickupDate($date)) {
                $availableDates[] = $date->toDateString();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'available_dates' => $availableDates,
                'cutoff_time' => config('lce.cutoff_time'),
            ],
        ]);
    }
}
