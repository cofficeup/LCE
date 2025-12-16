@extends('layouts.app')

@section('title', 'Dashboard - LCE 2.0')

@section('content')
<h1>Welcome, {{ $user->name }}!</h1>

<div class="grid" style="margin-top: 30px;">
    <div class="card">
        <h3>Available Credit</h3>
        <p style="font-size: 32px; color: #28a745; font-weight: bold; margin-top: 10px;">
            ${{ number_format($availableCredit, 2) }}
        </p>
    </div>

    <div class="card">
        <h3>Active Subscription</h3>
        @if($activeSubscription)
        <p style="font-size: 24px; margin-top: 10px;">{{ $activeSubscription->plan->name }}</p>
        <p style="margin-top: 5px;">
            <span class="badge badge-success">{{ $activeSubscription->status }}</span>
        </p>
        <p style="margin-top: 10px;">Banked Bags: <strong>{{ $activeSubscription->banked_bags }}</strong></p>
        <a href="/subscriptions/{{ $activeSubscription->id }}" class="btn" style="margin-top: 10px;">View Details</a>
        @else
        <p style="margin-top: 10px;">No active subscription</p>
        <a href="/subscriptions/create" class="btn" style="margin-top: 10px;">Subscribe Now</a>
        @endif
    </div>
</div>

<div class="card" style="margin-top: 30px;">
    <h3>Quick Actions</h3>
    <div style="margin-top: 15px;">
        <a href="/orders/create" class="btn">Create PPO Order</a>
        @if(!$activeSubscription)
        <a href="/subscriptions/create" class="btn btn-success" style="margin-left: 10px;">Start Subscription</a>
        @endif
        <a href="/invoices" class="btn btn-secondary" style="margin-left: 10px;">View Invoices</a>
    </div>
</div>

@if($recentPickups->count() > 0)
<div class="card">
    <h3>Recent Orders</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Status</th>
                <th>Pickup Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentPickups as $pickup)
            <tr>
                <td>{{ $pickup->created_at->format('M d, Y') }}</td>
                <td>{{ strtoupper($pickup->order_type) }}</td>
                <td>
                    <span class="badge badge-{{ $pickup->status == 'delivered' ? 'success' : 'warning' }}">
                        {{ $pickup->status }}
                    </span>
                </td>
                <td>{{ $pickup->pickup_date->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($recentInvoices->count() > 0)
<div class="card">
    <h3>Recent Invoices</h3>
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Total</th>
                <th>Credits Applied</th>
                <th>Final Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentInvoices as $invoice)
            <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                <td>${{ number_format($invoice->total, 2) }}</td>
                <td>${{ number_format($invoice->credits_applied, 2) }}</td>
                <td>${{ number_format($invoice->getFinalAmount(), 2) }}</td>
                <td>
                    <span class="badge badge-{{ $invoice->status == 'paid' ? 'success' : 'warning' }}">
                        {{ $invoice->status }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection