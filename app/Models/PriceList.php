<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    use HasFactory;

    protected $table = 'lce_prices_lists';

    protected $fillable = [
        'item_type', // 'shirt', 'pants', 'dress', 'comforter', etc.
        'service_category', // 'DC' (dry cleaning), 'HD' (heavy duty)
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
