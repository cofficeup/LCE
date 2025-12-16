@extends('layouts.app')

@section('title', 'Subscription Details - LCE 2.0')

@section('content')
<div class="card">
    <h2>Subscription Details</h2>

    <div class="grid" style="margin-top: 20px;">
        <div>
            <h3>{{ $subscription->plan->name }}</h3>
            <p style="font-size: 24px; color: #007bff; margin: 10px 0;">
                ${{ number_format($subscription->plan->price, 2) }}/{{ $subscription->plan->billing_cycle === 'monthly' ? 'month' : 'year' }}
            </p>
            <p><strong>Status:</strong>
                <span class="badge badge-{{ $subscription->status === 'active' ? 'success' : 'secondary' }}">
                    {{ strtoupper($subscription->status) }}
                </span>
            </p>
        </div>

        <div>
            <h3>Usage Information</h3>
            <p><strong>Monthly Bags:</strong> {{ $subscription->plan->bags_per_month }}</p>
            <p><strong>Banked Bags:</strong> {{ $subscription->banked_bags }}</p>
            <p><strong>Available Bags:</strong> {{ $availableBags }}</p>
        </div>
    </div>

    <div style="margin-top: 30px;">
        <h3>Billing Information</h3>
        <p><strong>Start Date:</strong> {{ $subscription->start_date->format('M d, Y') }}</p>
        @if($subscription->next_billing_date)
        <p><strong>Next Billing Date:</strong> {{ $subscription->next_billing_date->format('M d, Y') }}</p>
        @endif
        @if($subscription->cancelled_at)
        <p><strong>Cancelled At:</strong> {{ $subscription->cancelled_at->format('M d, Y') }}</p>
        @endif
    </div>

    @if($subscription->status === 'active')
    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
        <h4 style="color: #856404; margin-bottom: 10px;">⚠️ Cancel Subscription</h4>
        <p style="margin-bottom: 15px;">
            <strong>Warning:</strong> Cancelling will immediately end your subscription.
            @if($subscription->getDaysSinceStart() < 5)
                <br><strong style="color: #28a745;">You are within the 5-day grace period - Full refund of ${{ number_format($subscription->plan->price, 2) }}</strong>
                @else
                @if($subscription->plan->billing_cycle === 'yearly')
                <br>A $100 cancellation fee will apply. Refund: ${{ number_format(max(0, $subscription->plan->price - 100), 2) }}
                @else
                <br><strong style="color: #dc3545;">No refund available (outside 5-day grace period)</strong>
                @endif
                @endif
        </p>
        <form method="POST" action="{{ url('/subscriptions/' . $subscription->id . '/cancel') }}" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-danger" style="font-size: 16px;">
                ✖ Cancel My Subscription
            </button>
        </form>
        <a href="/dashboard" class="btn btn-secondary" style="margin-left: 10px; font-size: 16px;">
            ← Keep Subscription
        </a>
        <p style="margin-top: 15px; font-size: 13px; color: #856404;">
            <em>You will be asked to confirm before cancellation is processed.</em>
        </p>
    </div>
    @endif

    <div style="margin-top: 20px;">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

@if($subscription->bagUsage->count() > 0)
<div class="card">
    <h3>Bag Usage History</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Bags Used</th>
                <th>Extra Bags</th>
                <th>Overweight (lbs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subscription->bagUsage as $usage)
            <tr>
                <td>{{ $usage->created_at->format('M d, Y') }}</td>
                <td>{{ $usage->bags_used }}</td>
                <td>{{ $usage->extra_bags }}</td>
                <td>{{ number_format($usage->overweight_lbs, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection