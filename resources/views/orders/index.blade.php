@extends('layouts.app')

@section('title', 'Order History - LCE 2.0')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Order History</h1>
            <a href="{{ route('orders.create') }}" class="btn btn-primary">
                + New Order
            </a>
        </div>

        @if($orders->count() > 0)
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Pickup Date</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                            <td>
                                <span class="badge badge-{{ $order->order_type === 'ppo' ? 'info' : 'secondary' }}">
                                    {{ strtoupper($order->order_type) }}
                                </span>
                            </td>
                            <td>
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
                                <span class="badge badge-{{ $color }}">
                                    {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td>{{ $order->pickup_date ? $order->pickup_date->format('M d, Y') : 'Pending' }}</td>
                            <td>
                                @if($order->invoice)
                                ${{ number_format($order->invoice->total, 2) }}
                                @else
                                -
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>

        @else
        <div class="text-center py-5">
            <div style="font-size: 64px; margin-bottom: 20px;">👕</div>
            <h3>No orders found</h3>
            <p class="text-muted">You haven't placed any orders yet.</p>
            <a href="{{ route('orders.create') }}" class="btn btn-primary mt-3">
                Place Your First Order
            </a>
        </div>
        @endif
    </div>
</div>
@endsection