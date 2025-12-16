<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserInfo extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'lce_user_info';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'role', // 'customer', 'admin', 'csr'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get user subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'user_id');
    }

    /**
     * Get user credits
     */
    public function credits(): HasMany
    {
        return $this->hasMany(UserCredit::class, 'user_id');
    }

    /**
     * Get user pickups
     */
    public function pickups(): HasMany
    {
        return $this->hasMany(UserPickup::class, 'user_id');
    }

    /**
     * Get user invoices
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(UserInvoice::class, 'user_id');
    }

    /**
     * Get user transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(UserTransaction::class, 'user_id');
    }

    /**
     * Get active subscription
     */
    public function activeSubscription()
    {
        return $this->subscriptions()->active()->first();
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is CSR
     */
    public function isCsr(): bool
    {
        return $this->role === 'csr';
    }

    /**
     * Check if user is customer
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Get total available credit
     */
    public function getAvailableCredit(): float
    {
        return (float) $this->credits()->available()->sum('remaining_amount');
    }
}
