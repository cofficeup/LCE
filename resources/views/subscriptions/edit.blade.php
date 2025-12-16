@extends('layouts.app')

@section('title', 'Change Subscription Plan - LCE 2.0')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Change Subscription Plan</h1>
            <a href="{{ route('subscriptions.show', $subscription->id) }}" class="btn btn-secondary">
                ← Back to Subscription
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Current Plan: {{ $subscription->plan->name }}</h5>
            </div>
            <div class="card-body">
                <p>Status: <span class="badge badge-success">{{ strtoupper($subscription->status) }}</span></p>
                <p>Billing Cycle: {{ ucfirst($subscription->plan->billing_cycle) }}</p>
                <p>Price: ${{ number_format($subscription->plan->price, 2) }} / {{ $subscription->plan->billing_cycle === 'yearly' ? 'year' : 'month' }}</p>
            </div>
        </div>

        <h3>Available Plans</h3>
        <p class="text-muted mb-4">Select a new plan to switch. Prorated charges or credits will be applied automatically.</p>

        <form action="{{ route('subscriptions.update', $subscription->id) }}" method="POST">
            @csrf

            <div class="row">
                @foreach($plans as $plan)
                <div class="col-md-6 mb-4">
                    <div class="card h-100 {{ $plan->recommended ? 'border-primary' : '' }}">
                        @if($plan->recommended)
                        <div class="card-header bg-primary text-white text-center py-1">
                            <small>RECOMMENDED</small>
                        </div>
                        @endif
                        <div class="card-body text-center d-flex flex-column">
                            <h4 class="card-title">{{ $plan->name }}</h4>
                            <h2 class="card-price mb-3">
                                ${{ number_format($plan->price, 2) }}
                                <small class="text-muted">/ {{ $plan->billing_cycle === 'yearly' ? 'yr' : 'mo' }}</small>
                            </h2>

                            <ul class="list-unstyled mb-4 text-left mx-auto">
                                <li class="mb-2">✓ <strong>{{ $plan->bags_per_month }} Bags</strong> per month</li>
                                <li class="mb-2">✓ ${{ number_format($plan->price / $plan->bags_per_month, 2) }} per bag</li>
                                <li class="mb-2">✓ Rollover unused bags</li>
                                <li class="mb-2">✓ Free Pickup & Delivery</li>
                            </ul>

                            <div class="mt-auto">
                                <button type="submit" name="plan_id" value="{{ $plan->id }}"
                                    class="btn btn-outline-primary btn-block btn-lg"
                                    onclick="return confirm('Are you sure you want to switch to {{ $plan->name }}? Any price difference will be charged or credited to your account.');">
                                    Switch to {{ $plan->name }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </form>
    </div>
</div>
@endsection