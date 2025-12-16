<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $table = 'lce_subscription_plans';

    protected $fillable = [
        'name',
        'billing_cycle',
        'bags_per_month',
        'price',
        'bag_overage_rate',
        'price_per_lb_overage',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'bag_overage_rate' => 'decimal:2',
        'price_per_lb_overage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get subscriptions for this plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for monthly plans
     */
    public function scopeMonthly($query)
    {
        return $query->where('billing_cycle', 'monthly');
    }

    /**
     * Scope for yearly plans
     */
    public function scopeYearly($query)
    {
        return $query->where('billing_cycle', 'yearly');
    }

    /**
     * Get annual discount percentage
     */
    public function getAnnualDiscountAttribute(): float
    {
        if ($this->billing_cycle === 'yearly') {
            return (float) config('lce.annual_discount_percent', 15);
        }
        return 0;
    }

    /**
     * Get monthly equivalent price (for yearly plans)
     */
    public function getMonthlyEquivalentAttribute(): float
    {
        if ($this->billing_cycle === 'yearly') {
            return (float) $this->price / 12;
        }
        return (float) $this->price;
    }
}
