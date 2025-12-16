<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupZone extends Model
{
    use HasFactory;

    protected $table = 'lce_pickup_zones';

    protected $fillable = [
        'zone_code', // e.g., 'NYC-MAN', 'LA-WEST'
        'zone_name',
        'city',
        'state',
        'zip_codes', // JSON array of zip codes
        'is_active',
    ];

    protected $casts = [
        'zip_codes' => 'array',
        'is_active' => 'boolean',
    ];
}
