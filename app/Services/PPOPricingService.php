<?php

namespace App\Services;

use App\Models\Price;
use App\Models\PriceList;

class PPOPricingService
{
    /**
     * Calculate PPO (Pay-Per-Order) pricing
     * 
     * @param float $weightLbs Total weight in pounds
     * @param array $dcItems Dry cleaning items (not implemented in this version)
     * @param array $hdItems Heavy duty items (not implemented in this version)
     * @return array Pricing breakdown
     */
    public function calculatePPOPrice(float $weightLbs, array $dcItems = [], array $hdItems = []): array
    {
        // Get base rate per lb from config/DB
        $ratePerLb = $this->getBaseRate();
        $minimum = config('lce.ppo_minimum', 30.00);
        $pickupFee = config('lce.pickup_fee', 9.99);
        $serviceFee = config('lce.service_fee', 5.00);

        // Calculate wash & fold
        $washFoldSubtotal = $weightLbs * $ratePerLb;

        // Apply minimum
        $washFoldCharge = max($washFoldSubtotal, $minimum);

        // Calculate DC/HD (using price lists if available)
        $dcCharge = $this->calculateDCItems($dcItems);
        $hdCharge = $this->calculateHDItems($hdItems);

        // Calculate subtotal
        $subtotal = $washFoldCharge + $dcCharge + $hdCharge;

        // Add fees
        $total = $subtotal + $pickupFee + $serviceFee;

        return [
            'wash_fold' => [
                'weight_lbs' => $weightLbs,
                'rate_per_lb' => $ratePerLb,
                'subtotal' => $washFoldSubtotal,
                'minimum_applied' => $washFoldSubtotal < $minimum,
                'charge' => $washFoldCharge,
            ],
            'dry_cleaning' => [
                'items' => $dcItems,
                'charge' => $dcCharge,
            ],
            'heavy_duty' => [
                'items' => $hdItems,
                'charge' => $hdCharge,
            ],
            'fees' => [
                'pickup_delivery' => $pickupFee,
                'service' => $serviceFee,
            ],
            'subtotal' => $subtotal,
            'total' => $total,
        ];
    }

    /**
     * Get base rate per lb (from DB or config)
     */
    public function getBaseRate(): float
    {
        // Try to get from database first
        $price = Price::where('service_type', 'wash_fold')
            ->where('is_active', true)
            ->first();

        if ($price) {
            return (float) $price->price_per_lb;
        }

        // Fallback to config
        return (float) config('lce.ppo_rate_per_lb', 2.99);
    }

    /**
     * Calculate DC items from price list
     */
    protected function calculateDCItems(array $items): float
    {
        if (empty($items)) {
            return 0;
        }

        $total = 0;
        foreach ($items as $item) {
            $itemType = $item['type'] ?? '';
            $quantity = $item['quantity'] ?? 1;

            // Look up price in price list
            $priceList = PriceList::where('item_type', $itemType)
                ->where('service_category', 'DC')
                ->first();

            if ($priceList) {
                $total += (float) $priceList->price * $quantity;
            }
        }

        return $total;
    }

    /**
     * Calculate HD items from price list
     */
    protected function calculateHDItems(array $items): float
    {
        if (empty($items)) {
            return 0;
        }

        $total = 0;
        foreach ($items as $item) {
            $itemType = $item['type'] ?? '';
            $quantity = $item['quantity'] ?? 1;

            // Look up price in price list
            $priceList = PriceList::where('item_type', $itemType)
                ->where('service_category', 'HD')
                ->first();

            if ($priceList) {
                $total += (float) $priceList->price * $quantity;
            }
        }

        return $total;
    }

    /**
     * Apply minimum charge
     */
    public function applyMinimum(float $amount): float
    {
        $minimum = config('lce.ppo_minimum', 30.00);
        return max($amount, $minimum);
    }
}
