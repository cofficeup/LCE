<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services
        $this->app->singleton(\App\Services\CreditService::class);
        $this->app->singleton(\App\Services\PPOPricingService::class);
        $this->app->singleton(\App\Services\SubscriptionService::class);
        $this->app->singleton(\App\Services\BillingService::class);
        $this->app->singleton(\App\Services\SchedulingService::class);
        $this->app->singleton(\App\Services\StripePaymentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
