<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $table = 'lce_holidays';

    protected $fillable = [
        'name',
        'date',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Check if a specific date is a holiday
     */
    public static function isHoliday(Carbon $date): bool
    {
        return self::where('date', $date->toDateString())
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get upcoming holidays
     */
    public static function getUpcoming(int $limit = 10)
    {
        return self::where('date', '>=', now()->toDateString())
            ->where('is_active', true)
            ->orderBy('date')
            ->limit($limit)
            ->get();
    }
}
