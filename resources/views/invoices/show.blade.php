@extends('layouts.app')

@section('title', 'Invoice Details - LCE 2.0')

@section('content')
<div class="card">
    <h2>Invoice {{ $invoice->invoice_number }}</h2>

    <div class="grid" style="margin-top: 20px;">
        <div>
            <p><strong>Date:</strong> {{ $invoice->created_at->format('M d, Y H:i') }}</p>
            <p><strong>Order Type:</strong> {{ strtoupper($invoice->order_type) }}</p>
            <p><strong>Status:</strong>
                <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'warning' }}">
                    {{ strtoupper($invoice->status) }}
                </span>
            </p>
        </div>

        @if($invoice->pickup)
        <div>
            <p><strong>Pickup Date:</strong> {{ $invoice->pickup->pickup_date->format('M d, Y') }}</p>
            <p><strong>Delivery Date:</strong> {{ $invoice->pickup->delivery_date->format('M d, Y') }}</p>
            <p><strong>Zone:</strong> {{ $invoice->pickup->pickup_zone }}</p>
        </div>
        @endif
    </div>

    <h3 style="margin-top: 30px;">Line Items</h3>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lineItems as $item)
            <tr>
                <td>{{ $item->line_type }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ number_format($item->quantity, 2) }}</td>
                <td>${{ number_format($item->unit_price, 2) }}</td>
                <td>${{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold;">
                <td colspan="4" style="text-align: right;">Subtotal:</td>
                <td>${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->credits_applied > 0)
            <tr style="color: #28a745;">
                <td colspan="4" style="text-align: right;">Credits Applied:</td>
                <td>-${{ number_format($invoice->credits_applied, 2) }}</td>
            </tr>
            @endif
            <tr style="font-weight: bold; font-size: 18px;">
                <td colspan="4" style="text-align: right;">Total:</td>
                <td>${{ number_format($invoice->total, 2) }}</td>
            </tr>
            <tr style="font-weight: bold; font-size: 18px; color: #007bff;">
                <td colspan="4" style="text-align: right;">Final Amount:</td>
                <td>${{ number_format($invoice->getFinalAmount(), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if($invoice->transactions->count() > 0)
    <h3 style="margin-top: 30px;">Transactions</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->transactions as $transaction)
            <tr>
                <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                <td>{{ strtoupper($transaction->transaction_type) }}</td>
                <td>${{ number_format($transaction->amount, 2) }}</td>
                <td>{{ $transaction->payment_method }}</td>
                <td>
                    <span class="badge badge-{{ $transaction->status === 'completed' ? 'success' : 'warning' }}">
                        {{ strtoupper($transaction->status) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div style="margin-top: 30px;">
        <a href="/invoices" class="btn btn-secondary">Back to Invoices</a>
    </div>
</div>
@endsection