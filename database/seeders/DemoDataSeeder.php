<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\UserInfo;
use App\Models\UserCredit;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Models\UserPickup;
use App\Models\UserInvoice;
use App\Models\UserInvoiceLine;
use App\Models\UserTransaction;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create demo users
        $customer = UserInfo::create([
            'name' => 'John Customer',
            'email' => 'customer@test.com',
            'password' => Hash::make('password'),
            'phone' => '555-0101',
            'address' => '123 Main Street',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
            'role' => 'customer',
        ]);

        $admin = UserInfo::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'phone' => '555-0100',
            'address' => '456 Admin Ave',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10002',
            'role' => 'admin',
        ]);

        $customer2 = UserInfo::create([
            'name' => 'Jane Subscriber',
            'email' => 'subscriber@test.com',
            'password' => Hash::make('password'),
            'phone' => '555-0102',
            'address' => '789 Oak Lane',
            'city' => 'Brooklyn',
            'state' => 'NY',
            'zip' => '11201',
            'role' => 'customer',
        ]);

        // 2. Add welcome credits
        UserCredit::create([
            'user_id' => $customer->id,
            'type' => 'welcome',
            'amount' => 20.00,
            'remaining_amount' => 15.00, // Used $5
            'description' => 'Welcome bonus',
        ]);

        UserCredit::create([
            'user_id' => $customer2->id,
            'type' => 'welcome',
            'amount' => 20.00,
            'remaining_amount' => 20.00,
            'description' => 'Welcome bonus',
        ]);

        // Add manual credit for demo
        UserCredit::create([
            'user_id' => $customer->id,
            'type' => 'manual',
            'amount' => 10.00,
            'remaining_amount' => 10.00,
            'description' => 'Apology credit for delayed service',
        ]);

        // 3. Create an active subscription for customer2
        $monthlyPlan = SubscriptionPlan::where('name', '2 Bags Monthly')->first();
        if ($monthlyPlan) {
            UserSubscription::create([
                'user_id' => $customer2->id,
                'plan_id' => $monthlyPlan->id,
                'status' => 'active',
                'start_date' => now()->subDays(10),
                'next_billing_date' => now()->addDays(20),
                'banked_bags' => 1, // 1 unused bag from previous month
            ]);
        }

        // 4. Create some past pickups
        $pickup1 = UserPickup::create([
            'user_id' => $customer->id,
            'order_type' => 'ppo',
            'pickup_date' => now()->subDays(7)->format('Y-m-d'),
            'delivery_date' => now()->subDays(5)->format('Y-m-d'),
            'pickup_zone' => 'Manhattan',
            'weight_lbs' => 15.5,
            'status' => 'delivered',
            'notes' => 'First PPO order',
        ]);

        $pickup2 = UserPickup::create([
            'user_id' => $customer2->id,
            'order_type' => 'subscription',
            'pickup_date' => now()->subDays(3)->format('Y-m-d'),
            'delivery_date' => now()->subDays(1)->format('Y-m-d'),
            'pickup_zone' => 'Brooklyn',
            'weight_lbs' => 40.0,
            'status' => 'delivered',
            'notes' => 'Subscription pickup - 2 bags',
        ]);

        // 5. Create invoices for the pickups
        $invoice1 = UserInvoice::create([
            'user_id' => $customer->id,
            'pickup_id' => $pickup1->id,
            'invoice_number' => 'INV-' . str_pad(1, 6, '0', STR_PAD_LEFT),
            'order_type' => 'ppo',
            'subtotal' => 44.99,
            'credits_applied' => 5.00,
            'total' => 39.99,
            'status' => 'paid',
        ]);

        // Invoice line items for PPO order
        UserInvoiceLine::create([
            'invoice_id' => $invoice1->id,
            'line_type' => 'WF',
            'description' => 'Wash & Fold (15.5 lbs)',
            'quantity' => 15.5,
            'unit_price' => 2.99,
            'total' => 30.00, // Minimum charge applied
        ]);

        UserInvoiceLine::create([
            'invoice_id' => $invoice1->id,
            'line_type' => 'FEE_PND',
            'description' => 'Pickup & Delivery Fee',
            'quantity' => 1,
            'unit_price' => 9.99,
            'total' => 9.99,
        ]);

        UserInvoiceLine::create([
            'invoice_id' => $invoice1->id,
            'line_type' => 'FEE_SERVICE',
            'description' => 'Service Fee',
            'quantity' => 1,
            'unit_price' => 5.00,
            'total' => 5.00,
        ]);

        $invoice2 = UserInvoice::create([
            'user_id' => $customer2->id,
            'pickup_id' => $pickup2->id,
            'invoice_number' => 'INV-' . str_pad(2, 6, '0', STR_PAD_LEFT),
            'order_type' => 'subscription',
            'subtotal' => 74.99,
            'credits_applied' => 0.00,
            'total' => 74.99,
            'status' => 'paid',
        ]);

        // Invoice line for subscription
        UserInvoiceLine::create([
            'invoice_id' => $invoice2->id,
            'line_type' => 'BAG',
            'description' => '2 Bags Monthly Subscription',
            'quantity' => 1,
            'unit_price' => 74.99,
            'total' => 74.99,
        ]);

        // 6. Create transactions
        UserTransaction::create([
            'user_id' => $customer->id,
            'invoice_id' => $invoice1->id,
            'transaction_type' => 'charge',
            'amount' => 39.99,
            'payment_method' => 'card',
            'status' => 'completed',
            'transaction_id' => 'pi_demo_' . uniqid(),
            'notes' => 'Stripe Payment Intent (Demo)',
        ]);

        UserTransaction::create([
            'user_id' => $customer2->id,
            'invoice_id' => $invoice2->id,
            'transaction_type' => 'charge',
            'amount' => 74.99,
            'payment_method' => 'card',
            'status' => 'completed',
            'transaction_id' => 'pi_demo_' . uniqid(),
            'notes' => 'Subscription Payment (Demo)',
        ]);

        // 7. Create a scheduled pickup for future
        UserPickup::create([
            'user_id' => $customer->id,
            'order_type' => 'ppo',
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'delivery_date' => now()->addDays(4)->format('Y-m-d'),
            'pickup_zone' => 'Manhattan',
            'weight_lbs' => null,
            'status' => 'scheduled',
            'notes' => 'Upcoming pickup',
        ]);

        $this->command->info('Demo data created successfully!');
        $this->command->info('');
        $this->command->info('Test Accounts:');
        $this->command->info('Customer: customer@test.com / password');
        $this->command->info('Subscriber: subscriber@test.com / password');
        $this->command->info('Admin: admin@test.com / password');
    }
}
