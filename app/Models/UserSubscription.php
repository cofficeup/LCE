<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $table = 'lce_user_subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'start_date',
        'next_billing_date',
        'cancelled_at',
        'paused_at',
        'banked_bags',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_billing_date' => 'date',
        'cancelled_at' => 'date',
        'paused_at' => 'date',
        'banked_bags' => 'integer',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserInfo::class, 'user_id');
    }

    /**
     * Get the subscription plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Get bag usage records
     */
    public function bagUsage(): HasMany
    {
        return $this->hasMany(SubscriptionBagUsage::class, 'subscription_id');
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if subscription is paused
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Check if subscription is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get days since subscription started
     */
    public function getDaysSinceStart(): int
    {
        return Carbon::parse($this->start_date)->diffInDays(now());
    }

    /**
     * Get available bags (banked + monthly quota)
     */
    public function getAvailableBags(): int
    {
        return $this->banked_bags + $this->plan->bags_per_month;
    }
}
