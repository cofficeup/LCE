<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInvoiceLine extends Model
{
    use HasFactory;

    protected $table = 'lce_user_invoice_line';

    protected $fillable = [
        'invoice_id',
        'line_type', // 'WF', 'DC', 'HD', 'FEE_PND', 'FEE_SERVICE', 'SUBSCRIPTION_BAG', 'SUB_OVERWEIGHT_LBS'
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the invoice
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(UserInvoice::class, 'invoice_id');
    }
}
