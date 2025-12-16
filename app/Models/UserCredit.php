<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCredit extends Model
{
    use HasFactory;

    protected $table = 'lce_user_credits';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'remaining_amount',
        'description',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'expires_at' => 'date',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserInfo::class, 'user_id');
    }

    /**
     * Scope for credits with remaining balance
     */
    public function scopeAvailable($query)
    {
        return $query->where('remaining_amount', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if credit is fully used
     */
    public function isFullyUsed(): bool
    {
        return $this->remaining_amount <= 0;
    }

    /**
     * Check if credit is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Use credit
     */
    public function use(float $amount): void
    {
        $this->remaining_amount = max(0, $this->remaining_amount - $amount);
        $this->save();
    }
}
