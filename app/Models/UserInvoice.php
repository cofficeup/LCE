<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserInvoice extends Model
{
    use HasFactory;

    protected $table = 'lce_user_invoice';

    protected $fillable = [
        'user_id',
        'pickup_id',
        'invoice_number',
        'subtotal',
        'credits_applied',
        'total',
        'status', // 'pending', 'paid', 'refunded', 'cancelled'
        'order_type', // 'ppo', 'subscription'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'credits_applied' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserInfo::class, 'user_id');
    }

    /**
     * Get the pickup
     */
    public function pickup(): BelongsTo
    {
        return $this->belongsTo(UserPickup::class, 'pickup_id');
    }

    /**
     * Get invoice line items
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(UserInvoiceLine::class, 'invoice_id');
    }

    /**
     * Get transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(UserTransaction::class, 'invoice_id');
    }

    /**
     * Get final amount after credits
     */
    public function getFinalAmount(): float
    {
        return max(0, (float)$this->total - (float)$this->credits_applied);
    }
}
