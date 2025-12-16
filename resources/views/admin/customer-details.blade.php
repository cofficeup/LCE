@extends('layouts.app')

@section('title', 'Customer Details - LCE 2.0')

@section('content')
<div class="card">
    <h2>Customer: {{ $customer->name }}</h2>

    <div class="grid" style="margin-top: 20px;">
        <div>
            <h3>Contact Information</h3>
            <p><strong>Email:</strong> {{ $customer->email }}</p>
            <p><strong>Phone:</strong> {{ $customer->phone }}</p>
            <p><strong>Address:</strong> {{ $customer->address }}</p>
            @if($customer->city)
            <p><strong>City:</strong> {{ $customer->city }}, {{ $customer->state }} {{ $customer->zip }}</p>
            @endif
        </div>

        <div>
            <h3>Account Information</h3>
            <p><strong>Customer ID:</strong> {{ $customer->id }}</p>
            <p><strong>Role:</strong> {{ $customer->role }}</p>
            <p><strong>Available Credit:</strong> ${{ number_format($availableCredit, 2) }}</p>
            <p><strong>Member Since:</strong> {{ $customer->created_at->format('M d, Y') }}</p>
        </div>
    </div>
</div>

<div class="card">
    <h3>Add Manual Credit</h3>
    <form method="POST" action="/admin/customers/{{ $customer->id }}/credits" style="max-width: 500px;">
        @csrf
        <div class="form-group">
            <label for="amount">Amount ($)</label>
            <input type="number" step="0.01" id="amount" name="amount" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" required>
        </div>
        <button type="submit" class="btn">Add Credit</button>
    </form>
</div>

@if($customer->credits->count() > 0)
<div class="card">
    <h3>Credit History</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Remaining</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customer->credits as $credit)
            <tr>
                <td>{{ $credit->created_at->format('M d, Y') }}</td>
                <td>{{ strtoupper($credit->type) }}</td>
                <td>${{ number_format($credit->amount, 2) }}</td>
                <td>${{ number_format($credit->remaining_amount, 2) }}</td>
                <td>{{ $credit->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($customer->subscriptions->count() > 0)
<div class="card">
    <h3>Subscriptions</h3>
    <table>
        <thead>
            <tr>
                <th>Plan</th>
                <th>Status</th>
                <th>Start Date</th>
                <th>Next Billing</th>
                <th>Banked Bags</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customer->subscriptions as $subscription)
            <tr>
                <td>{{ $subscription->plan->name }}</td>
                <td>
                    <span class="badge badge-{{ $subscription->status === 'active' ? 'success' : 'secondary' }}">
                        {{ strtoupper($subscription->status) }}
                    </span>
                </td>
                <td>{{ $subscription->start_date->format('M d, Y') }}</td>
                <td>{{ $subscription->next_billing_date ? $subscription->next_billing_date->format('M d, Y') : 'N/A' }}</td>
                <td>{{ $subscription->banked_bags }}</td>
                <td>
                    @if($subscription->status === 'active')
                    <form method="POST" action="/admin/subscriptions/{{ $subscription->id }}/cancel" onsubmit="return confirm('Cancel this subscription?');" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Cancel</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($customer->pickups->count() > 0)
<div class="card">
    <h3>Recent Orders</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Status</th>
                <th>Pickup Date</th>
                <th>Weight</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customer->pickups->take(10) as $pickup)
            <tr>
                <td>{{ $pickup->created_at->format('M d, Y') }}</td>
                <td>{{ strtoupper($pickup->order_type) }}</td>
                <td>
                    <span class="badge badge-{{ $pickup->status === 'delivered' ? 'success' : 'warning' }}">
                        {{ $pickup->status }}
                    </span>
                </td>
                <td>{{ $pickup->pickup_date->format('M d, Y') }}</td>
                <td>{{ $pickup->weight_lbs ? number_format($pickup->weight_lbs, 2) . ' lbs' : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div style="margin-top: 20px;">
    <a href="/admin/customers" class="btn btn-secondary">Back to Customers</a>
</div>
@endsection