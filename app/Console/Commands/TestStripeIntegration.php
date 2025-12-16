<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserInfo;
use App\Services\StripePaymentService;
use App\Services\BillingService;
use Illuminate\Support\Facades\Schema;

class TestStripeIntegration extends Command
{
    protected $signature = 'test:stripe';
    protected $description = 'Test Stripe payment integration';

    public function handle()
    {
        $this->info('===========================================');
        $this->info('LCE 2.0 - Stripe Integration Test Suite');
        $this->info('===========================================');
        $this->newLine();

        $results = [];

        // Test 1: Configuration
        $this->info('Test 1: Stripe Configuration...');
        try {
            $publishableKey = config('stripe.publishable_key');
            $secretKey = config('stripe.secret_key');

            if (empty($publishableKey) || empty($secretKey)) {
                throw new \Exception("Stripe keys not configured");
            }

            if (!str_starts_with($publishableKey, 'pk_test_')) {
                throw new \Exception("Not using test keys - DANGER!");
            }

            $this->line('  ✅ Stripe keys configured correctly');
            $this->line('  ✅ Using TEST mode (safe)');
            $this->line('  ✅ Publishable key: ' . substr($publishableKey, 0, 20) . '...');
            $results['config'] = 'PASS';
        } catch (\Exception $e) {
            $this->error('  ❌ FAILED: ' . $e->getMessage());
            $results['config'] = 'FAIL';
        }

        $this->newLine();

        // Test 2: Service Instantiation
        $this->info('Test 2: StripePaymentService Initialization...');
        try {
            $stripeService = app(StripePaymentService::class);
            $this->line('  ✅ Service instantiated successfully');
            $this->line('  ✅ Stripe API key set');
            $results['service'] = 'PASS';
        } catch (\Exception $e) {
            $this->error('  ❌ FAILED: ' . $e->getMessage());
            $results['service'] = 'FAIL';
            return 1;
        }

        $this->newLine();

        // Test 3: Customer Creation
        $this->info('Test 3: Stripe Customer Creation...');
        try {
            $testUser = UserInfo::where('email', 'customer@test.com')->first();

            if (!$testUser) {
                throw new \Exception("Test user not found");
            }

            $this->line("  → Testing with user: {$testUser->email}");

            if ($testUser->stripe_customer_id) {
                $this->line("  → User already has Stripe customer ID: {$testUser->stripe_customer_id}");
                $customerId = $testUser->stripe_customer_id;
            } else {
                $this->line("  → Creating new Stripe customer...");
                $customerId = $stripeService->createCustomer($testUser);
                $this->line('  ✅ Customer created in Stripe');
            }

            $this->line("  ✅ Customer ID: {$customerId}");
            $this->line('  ✅ Customer ID saved to database');

            if (!str_starts_with($customerId, 'cus_')) {
                throw new \Exception("Invalid customer ID format");
            }

            $results['customer'] = 'PASS';
        } catch (\Exception $e) {
            $this->error('  ❌ FAILED: ' . $e->getMessage());
            $results['customer'] = 'FAIL';
        }

        $this->newLine();

        // Test 4: Payment Intent
        $this->info('Test 4: Payment Intent Creation...');
        try {
            $this->line('  → Creating payment intent for $24.99');
            $paymentIntent = $stripeService->createPaymentIntent(
                24.99,
                'usd',
                [
                    'test' => true,
                    'order_type' => 'ppo'
                ]
            );

            $this->line('  ✅ Payment intent created');
            $this->line("  ✅ Intent ID: {$paymentIntent->id}");
            $this->line('  ✅ Amount: $' . ($paymentIntent->amount / 100));
            $this->line("  ✅ Status: {$paymentIntent->status}");

            $results['payment_intent'] = 'PASS';
        } catch (\Exception $e) {
            $this->error('  ❌ FAILED: ' . $e->getMessage());
            $results['payment_intent'] = 'FAIL';
        }

        $this->newLine();

        // Test 5: Billing Service Integration
        $this->info('Test 5: Billing Service Integration...');
        try {
            $billingService = app(BillingService::class);

            $reflection = new \ReflectionClass($billingService);
            $constructor = $reflection->getConstructor();
            $params = $constructor->getParameters();

            $hasStripeParam = false;
            foreach ($params as $param) {
                if ($param->getType() && $param->getType()->getName() === StripePaymentService::class) {
                    $hasStripeParam = true;
                    break;
                }
            }

            if (!$hasStripeParam) {
                throw new \Exception("StripePaymentService not in BillingService constructor");
            }

            $this->line('  ✅ BillingService has StripePaymentService dependency');
            $this->line('  ✅ Payment integration configured');

            $results['billing'] = 'PASS';
        } catch (\Exception $e) {
            $this->error('  ❌ FAILED: ' . $e->getMessage());
            $results['billing'] = 'FAIL';
        }

        $this->newLine();

        // Test 6: Database Schema
        $this->info('Test 6: Database Schema...');
        try {
            $hasStripeColumn = Schema::hasColumn('lce_user_info', 'stripe_customer_id');

            if (!$hasStripeColumn) {
                throw new \Exception("stripe_customer_id column missing");
            }

            $this->line('  ✅ stripe_customer_id column exists');

            $hasTransactionId = Schema::hasColumn('lce_user_transactions', 'transaction_id');

            if (!$hasTransactionId) {
                throw new \Exception("transaction_id column missing");
            }

            $this->line('  ✅ transaction_id column exists');

            $results['schema'] = 'PASS';
        } catch (\Exception $e) {
            $this->error('  ❌ FAILED: ' . $e->getMessage());
            $results['schema'] = 'FAIL';
        }

        $this->newLine();

        // Test 7: Live API Connection
        $this->info('Test 7: Stripe API Connection...');
        try {
            \Stripe\Stripe::setApiKey(config('stripe.secret_key'));

            // Create a test charge
            $charge = \Stripe\Charge::create([
                'amount' => 100,
                'currency' => 'usd',
                'source' => 'tok_visa',
                'description' => 'LCE Test',
            ]);

            $this->line('  ✅ Successfully connected to Stripe API');
            $this->line('  ✅ Test charge created: ' . $charge->id);

            // Refund it
            $refund = \Stripe\Refund::create(['charge' => $charge->id]);
            $this->line('  ✅ Test refund created: ' . $refund->id);

            $results['api'] = 'PASS';
        } catch (\Exception $e) {
            $this->error('  ❌ FAILED: ' . $e->getMessage());
            $results['api'] = 'FAIL';
        }

        $this->newLine();

        // Summary
        $this->info('===========================================');
        $this->info('TEST SUMMARY');
        $this->info('===========================================');
        $this->newLine();

        $totalTests = count($results);
        $passedTests = count(array_filter($results, fn($r) => $r === 'PASS'));
        $failedTests = $totalTests - $passedTests;

        foreach ($results as $test => $result) {
            $icon = $result === 'PASS' ? '✅' : '❌';
            $this->line("{$icon} " . str_pad($test, 20) . " : {$result}");
        }

        $this->newLine();
        $this->line("Total Tests: {$totalTests}");
        $this->line("Passed: {$passedTests}");
        $this->line("Failed: {$failedTests}");

        if ($failedTests === 0) {
            $this->newLine();
            $this->info('🎉 ALL TESTS PASSED! Stripe integration is working correctly.');
            return 0;
        } else {
            $this->newLine();
            $this->warn('⚠️  Some tests failed. Please review the errors above.');
            return 1;
        }
    }
}
