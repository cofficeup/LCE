<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create lce_user_info table (users)
        Schema::create('lce_user_info', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('role', 20)->default('customer');
            $table->string('stripe_customer_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // 2. Create lce_subscription_plans
        Schema::create('lce_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('billing_cycle', 20); // monthly, yearly
            $table->integer('bags_per_month');
            $table->decimal('price', 10, 2);
            $table->decimal('bag_overage_rate', 10, 2);
            $table->decimal('price_per_lb_overage', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Create lce_user_subscriptions
        Schema::create('lce_user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('lce_user_info')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('lce_subscription_plans')->onDelete('restrict');
            $table->string('status', 20)->default('active'); // active, paused, cancelled, expired
            $table->date('start_date');
            $table->date('next_billing_date')->nullable();
            $table->integer('banked_bags')->default(0);
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        // 5. Create lce_user_credits
        Schema::create('lce_user_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('lce_user_info')->onDelete('cascade');
            $table->string('type', 20); // welcome, refund, manual, promo
            $table->decimal('amount', 10, 2);
            $table->decimal('remaining_amount', 10, 2);
            $table->string('description')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });

        // 6. Create lce_user_pickup
        Schema::create('lce_user_pickup', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('lce_user_info')->onDelete('cascade');
            $table->string('order_type', 20); // ppo, subscription
            $table->date('pickup_date');
            $table->date('delivery_date');
            $table->string('pickup_zone', 50);
            $table->decimal('weight_lbs', 10, 2)->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->boolean('is_recurring')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Create lce_subscription_bag_usage
        Schema::create('lce_subscription_bag_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('lce_user_subscriptions')->onDelete('cascade');
            $table->foreignId('pickup_id')->constrained('lce_user_pickup')->onDelete('cascade');
            $table->integer('bags_used');
            $table->integer('extra_bags')->default(0);
            $table->decimal('overweight_lbs', 10, 2)->default(0);
            $table->timestamps();
        });

        // 8. Create lce_user_invoice
        Schema::create('lce_user_invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('lce_user_info')->onDelete('cascade');
            $table->foreignId('pickup_id')->nullable()->constrained('lce_user_pickup')->onDelete('set null');
            $table->string('invoice_number', 50)->unique();
            $table->string('order_type', 20);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('credits_applied', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });

        // 9. Create lce_user_invoice_line
        Schema::create('lce_user_invoice_line', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('lce_user_invoice')->onDelete('cascade');
            $table->string('line_type', 20);
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        // 10. Create lce_user_transactions
        Schema::create('lce_user_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('lce_user_info')->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained('lce_user_invoice')->onDelete('set null');
            $table->string('transaction_type', 20);
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 11. Create lce_holidays
        Schema::create('lce_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->date('date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 12. Create lce_prices
        Schema::create('lce_prices', function (Blueprint $table) {
            $table->id();
            $table->string('service_type', 50);
            $table->decimal('rate_per_lb', 10, 2);
            $table->decimal('minimum_charge', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 13. Create lce_prices_lists
        Schema::create('lce_prices_lists', function (Blueprint $table) {
            $table->id();
            $table->string('item_name', 100);
            $table->string('item_code', 20)->unique();
            $table->string('service_type', 20);
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 14. Create lce_pickup_zones
        Schema::create('lce_pickup_zones', function (Blueprint $table) {
            $table->id();
            $table->string('zone_code', 20)->unique();
            $table->string('zone_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lce_pickup_zones');
        Schema::dropIfExists('lce_prices_lists');
        Schema::dropIfExists('lce_prices');
        Schema::dropIfExists('lce_holidays');
        Schema::dropIfExists('lce_user_transactions');
        Schema::dropIfExists('lce_user_invoice_line');
        Schema::dropIfExists('lce_user_invoice');
        Schema::dropIfExists('lce_subscription_bag_usage');
        Schema::dropIfExists('lce_user_pickup');
        Schema::dropIfExists('lce_user_credits');
        Schema::dropIfExists('lce_user_subscriptions');
        Schema::dropIfExists('lce_subscription_plans');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('lce_user_info');
    }
};
