@extends('layouts.app')

@section('title', 'Order #' . $order->id . ' - LCE 2.0')

@section('content')
<div class="row">
    <div class="col-md-12 mb-3">
        <a href="{{ route('orders.index') }}" class="text-decoration-none">← Back to Order History</a>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Order #{{ $order->id }}</h3>
                @php
                $statusColors = [
                'pending' => 'warning',
                'picked_up' => 'info',
                'processing' => 'primary',
                'delivered' => 'success',
                'cancelled' => 'danger'
                ];
                $color = $statusColors[$order->status] ?? 'secondary';
                @endphp
                <span class="badge badge-{{ $color }} p-2" style="font-size: 14px;">
                    {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-muted mb-3">Pickup Details</h5>
                        <p class="mb-1"><strong>Date:</strong> {{ $order->pickup_date ? $order->pickup_date->format('l, F j, Y') : 'Not scheduled' }}</p>
                        <p class="mb-1"><strong>Window:</strong> 8:00 AM - 10:00 AM</p>
                        <p class="mb-1"><strong>Address:</strong><br>
                            {{ auth()->user()->street_address }}<br>
                            {{ auth()->user()->city }}, {{ auth()->user()->state }} {{ auth()->user()->zip_code }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-muted mb-3">Delivery Details</h5>
                        <p class="mb-1"><strong>Estimated:</strong> {{ $order->delivery_date ? $order->delivery_date->format('l, F j, Y') : 'Pending' }}</p>
                        <p class="mb-1"><strong>Window:</strong> 4:00 PM - 8:00 PM</p>
                    </div>
                </div>

                <hr>

                <h5 class="text-muted mb-3">Order Items</h5>
                @if($order->weight_lbs > 0)
                <div class="d-flex justify-content-between align-items-center border p-3 rounded mb-2">
                    <div>
                        <strong>Wash & Fold Laundry</strong>
                        <div class="text-muted small">Standard service</div>
                    </div>
                    <div>{{ $order->weight_lbs }} lbs</div>
                </div>
                @endif

                @if($order->notes)
                <div class="mt-4 p-3 bg-light rounded">
                    <strong>Special Instructions:</strong><br>
                    {{ $order->notes }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        @if($order->invoice)
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">Invoice</h4>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Invoice #{{ $order->invoice->invoice_number }}</span>
                    <span class="badge badge-{{ $order->invoice->status === 'paid' ? 'success' : 'warning' }}">
                        {{ strtoupper($order->invoice->status) }}
                    </span>
                </div>

                <table class="table table-sm table-borderless">
                    @foreach($order->invoice->lineItems as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">${{ number_format($item->amount, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="border-top">
                        <td><strong>Subtotal</strong></td>
                        <td class="text-right"><strong>${{ number_format($order->invoice->subtotal, 2) }}</strong></td>
                    </tr>
                    @if($order->invoice->credits_applied > 0)
                    <tr class="text-success">
                        <td>Credits Applied</td>
                        <td class="text-right">-${{ number_format($order->invoice->credits_applied, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="border-top">
                        <td>
                            <h4 class="mb-0">Total</h4>
                        </td>
                        <td class="text-right">
                            <h4 class="mb-0 text-primary">${{ number_format($order->invoice->getFinalAmount(), 2) }}</h4>
                        </td>
                    </tr>
                </table>

                @if($order->invoice->status !== 'paid')
                <div class="mt-3">
                    <a href="{{ route('payment.checkout', ['invoice_id' => $order->invoice->id]) }}" class="btn btn-success btn-block">
                        Pay Invoice Now
                    </a>
                </div>
                @endif

                <div class="mt-3 text-center">
                    <a href="{{ route('invoices.show', $order->invoice->id) }}">View Full Invoice</a>
                </div>
            </div>
        </div>
        @endif

        @if($order->status === 'pending')
        <div class="card">
            <div class="card-body">
                <form action="#" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-outline-danger btn-block" disabled title="Cancellation implementation coming soon">
                        Cancel Order
                    </button>
                    <small class="text-muted d-block text-center mt-2">
                        Orders can only be cancelled before pickup.
                    </small>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection