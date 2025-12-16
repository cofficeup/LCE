<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    protected $table = 'lce_prices';

    protected $fillable = [
        'service_type', // 'wash_fold', 'dry_cleaning', 'heavy_duty'
        'price_per_lb',
        'is_active',
    ];

    protected $casts = [
        'price_per_lb' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
