<?php

namespace App\Services;

use App\Models\UserInfo;
use App\Models\UserPickup;
use App\Models\PickupZone;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    /**
     * Schedule ASAP pickup (next available date)
     */
    public function scheduleASAP(
        UserInfo $user,
        string $pickupZone,
        string $orderType = 'ppo',
        array $additionalData = []
    ): UserPickup {
        $pickupDate = $this->getNextAvailablePickupDate($pickupZone);
        $deliveryDate = $this->calculateDeliveryDate($pickupDate);

        return $this->createPickup($user, $pickupDate, $deliveryDate, $pickupZone, $orderType, $additionalData);
    }

    /**
     * Schedule future pickup
     */
    public function scheduleFuture(
        UserInfo $user,
        Carbon $requestedDate,
        string $pickupZone,
        string $orderType = 'ppo',
        array $additionalData = []
    ): UserPickup {
        // Validate requested date
        if (!$this->isValidPickupDate($requestedDate)) {
            throw new \Exception('Requested pickup date is not available (weekend, holiday, or past cutoff)');
        }

        $deliveryDate = $this->calculateDeliveryDate($requestedDate);

        return $this->createPickup($user, $requestedDate, $deliveryDate, $pickupZone, $orderType, $additionalData);
    }

    /**
     * Schedule recurring pickup
     */
    public function scheduleRecurring(
        UserInfo $user,
        string $frequency, // 'weekly', 'biweekly'
        string $pickupZone,
        string $orderType = 'ppo',
        int $occurrences = 4
    ): array {
        $pickups = [];
        $currentDate = $this->getNextAvailablePickupDate($pickupZone);

        for ($i = 0; $i < $occurrences; $i++) {
            $deliveryDate = $this->calculateDeliveryDate($currentDate);

            $pickup = $this->createPickup($user, $currentDate, $deliveryDate, $pickupZone, $orderType, [
                'is_recurring' => true,
                'recurring_frequency' => $frequency,
            ]);

            $pickups[] = $pickup;

            // Calculate next occurrence
            $currentDate = $frequency === 'weekly'
                ? $currentDate->copy()->addWeek()
                : $currentDate->copy()->addWeeks(2);

            // Adjust if next date falls on weekend/holiday
            while (!$this->isValidPickupDate($currentDate)) {
                $currentDate->addDay();
            }
        }

        return $pickups;
    }

    /**
     * Get next available pickup date
     */
    public function getNextAvailablePickupDate(string $zone): Carbon
    {
        $cutoffTime = config('lce.cutoff_time', '14:00');
        $currentTime = now();

        // If before cutoff, try today
        $candidateDate = $currentTime->format('H:i') < $cutoffTime
            ? $currentTime->copy()
            : $currentTime->copy()->addDay();

        // Find next valid pickup date
        $maxAttempts = 30; // Prevent infinite loop
        $attempts = 0;

        while (!$this->isValidPickupDate($candidateDate) && $attempts < $maxAttempts) {
            $candidateDate->addDay();
            $attempts++;
        }

        return $candidateDate;
    }

    /**
     * Check if date is valid for pickup
     * - Must be Monday-Friday
     * - Must not be a holiday
     * - Must be in the future (or today before cutoff)
     */
    public function isValidPickupDate(Carbon $date): bool
    {
        // Check if weekend
        if ($date->isWeekend()) {
            return false;
        }

        // Check if holiday
        if (Holiday::isHoliday($date)) {
            return false;
        }

        // Check if in the past
        if ($date->isPast() && !$date->isToday()) {
            return false;
        }

        // If today, check cutoff time
        if ($date->isToday()) {
            $cutoffTime = config('lce.cutoff_time', '14:00');
            if (now()->format('H:i') >= $cutoffTime) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate delivery date (typically 2-3 business days after pickup)
     */
    protected function calculateDeliveryDate(Carbon $pickupDate): Carbon
    {
        $deliveryDate = $pickupDate->copy()->addDays(2);

        // Skip weekends and holidays
        while (!$this->isValidPickupDate($deliveryDate)) {
            $deliveryDate->addDay();
        }

        return $deliveryDate;
    }

    /**
     * Create pickup record
     */
    protected function createPickup(
        UserInfo $user,
        Carbon $pickupDate,
        Carbon $deliveryDate,
        string $pickupZone,
        string $orderType,
        array $additionalData = []
    ): UserPickup {
        return UserPickup::create(array_merge([
            'user_id' => $user->id,
            'pickup_date' => $pickupDate,
            'delivery_date' => $deliveryDate,
            'status' => 'scheduled',
            'pickup_zone' => $pickupZone,
            'order_type' => $orderType,
            'is_recurring' => false,
        ], $additionalData));
    }

    /**
     * Validate pickup zone exists
     */
    public function validateZone(string $zone): bool
    {
        return PickupZone::where('zone_code', $zone)
            ->where('is_active', true)
            ->exists();
    }
}
