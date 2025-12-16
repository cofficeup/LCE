<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserPickup extends Model
{
    use HasFactory;

    protected $table = 'lce_user_pickup';

    protected $fillable = [
        'user_id',
        'pickup_date',
        'delivery_date',
        'status', // 'scheduled', 'picked_up', 'in_process', 'delivered', 'cancelled'
        'pickup_zone',
        'weight_lbs',
        'num_bags',
        'order_type', // 'ppo', 'subscription'
        'is_recurring',
        'recurring_frequency', // 'weekly', 'biweekly'
        'notes',
    ];

    protected $casts = [
        'pickup_date' => 'datetime',
        'delivery_date' => 'datetime',
        'weight_lbs' => 'decimal:2',
        'num_bags' => 'integer',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserInfo::class, 'user_id');
    }

    /**
     * Get the invoice
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(UserInvoice::class, 'pickup_id');
    }

    /**
     * Get bag usage (for subscription orders)
     */
    public function bagUsage(): HasOne
    {
        return $this->hasOne(SubscriptionBagUsage::class, 'pickup_id');
    }

    /**
     * Scope for subscription orders
     */
    public function scopeSubscription($query)
    {
        return $query->where('order_type', 'subscription');
    }

    /**
     * Scope for PPO orders
     */
    public function scopePpo($query)
    {
        return $query->where('order_type', 'ppo');
    }
}
