<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTransaction extends Model
{
    use HasFactory;

    protected $table = 'lce_user_transactions';

    protected $fillable = [
        'user_id',
        'invoice_id',
        'transaction_type', // 'charge', 'refund', 'credit'
        'amount',
        'payment_method',
        'status', // 'pending', 'completed', 'failed'
        'transaction_id', // External payment gateway ID
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
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
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(UserInvoice::class, 'invoice_id');
    }
}
