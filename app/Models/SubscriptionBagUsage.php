<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionBagUsage extends Model
{
    use HasFactory;

    protected $table = 'lce_subscription_bag_usage';

    protected $fillable = [
        'subscription_id',
        'pickup_id',
        'bags_used',
        'extra_bags',
        'overweight_lbs',
    ];

    protected $casts = [
        'bags_used' => 'integer',
        'extra_bags' => 'integer',
        'overweight_lbs' => 'decimal:2',
    ];

    /**
     * Get the subscription
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }

    /**
     * Get the pickup
     */
    public function pickup(): BelongsTo
    {
        return $this->belongsTo(UserPickup::class, 'pickup_id');
    }

    /**
     * Get total bags (used + extra)
     */
    public function getTotalBags(): int
    {
        return $this->bags_used + $this->extra_bags;
    }
}
