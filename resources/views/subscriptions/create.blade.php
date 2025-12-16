@extends('layouts.app')

@section('title', 'Create Subscription - LCE 2.0')

@section('content')
<div class="card">
    <h2>Choose a Subscription Plan</h2>
    <p style="margin-top: 10px;">Save 15% with annual plans!</p>

    <form method="POST" action="/subscriptions">
        @csrf

        <h3 style="margin-top: 30px;">Monthly Plans</h3>
        <div class="grid" style="margin-top: 15px;">
            @foreach($plans as $plan)
            @if($plan->billing_cycle === 'monthly')
            <div class="card" style="border: 2px solid #ddd; cursor: pointer;" onclick="selectPlan({{ $plan->id }})">
                <input type="radio" name="plan_id" value="{{ $plan->id }}" id="plan_{{ $plan->id }}" style="display: none;">
                <h3>{{ $plan->name }}</h3>
                <p style="font-size: 32px; color: #007bff; font-weight: bold; margin: 10px 0;">
                    ${{ number_format($plan->price, 2) }}<span style="font-size: 16px;">/month</span>
                </p>
                <ul style="margin-left: 20px; margin-top: 15px;">
                    <li>{{ $plan->bags_per_month }} bag{{ $plan->bags_per_month > 1 ? 's' : '' }} per month</li>
                    <li>~{{ $plan->bags_per_month * 20 }} lbs of laundry</li>
                    <li>Unused bags roll over</li>
                    <li>No pickup/service fees</li>
                    <li>Extra bags: ${{ number_format($plan->bag_overage_rate, 2) }}</li>
                </ul>
            </div>
            @endif
            @endforeach
        </div>

        <h3 style="margin-top: 30px;">Annual Plans (Save 15%!)</h3>
        <div class="grid" style="margin-top: 15px;">
            @foreach($plans as $plan)
            @if($plan->billing_cycle === 'yearly')
            <div class="card" style="border: 2px solid #ddd; cursor: pointer; position: relative;" onclick="selectPlan({{ $plan->id }})">
                <span class="badge badge-success" style="position: absolute; top: 10px; right: 10px;">15% OFF</span>
                <input type="radio" name="plan_id" value="{{ $plan->id }}" id="plan_{{ $plan->id }}" style="display: none;">
                <h3>{{ $plan->name }}</h3>
                <p style="font-size: 32px; color: #28a745; font-weight: bold; margin: 10px 0;">
                    ${{ number_format($plan->price, 2) }}<span style="font-size: 16px;">/year</span>
                </p>
                <p style="color: #666;">${{ number_format($plan->price / 12, 2) }}/month equivalent</p>
                <ul style="margin-left: 20px; margin-top: 15px;">
                    <li>{{ $plan->bags_per_month }} bag{{ $plan->bags_per_month > 1 ? 's' : '' }} per month</li>
                    <li>~{{ $plan->bags_per_month * 20 }} lbs of laundry</li>
                    <li>Unused bags roll over</li>
                    <li>No pickup/service fees</li>
                    <li>Extra bags: ${{ number_format($plan->bag_overage_rate, 2) }}</li>
                </ul>
            </div>
            @endif
            @endforeach
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <button type="submit" class="btn" style="padding: 15px 40px; font-size: 18px;">Subscribe Now</button>
            <a href="/dashboard" class="btn btn-secondary" style="margin-left: 10px; padding: 15px 40px;">Cancel</a>
        </div>
    </form>
</div>

<script>
    function selectPlan(planId) {
        // Deselect all
        document.querySelectorAll('input[name="plan_id"]').forEach(radio => {
            radio.checked = false;
            radio.parentElement.style.border = '2px solid #ddd';
        });

        // Select clicked
        const radio = document.getElementById('plan_' + planId);
        radio.checked = true;
        radio.parentElement.style.border = '2px solid #007bff';
    }
</script>
@endsection