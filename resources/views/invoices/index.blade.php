@extends('layouts.app')

@section('title', 'Invoices - LCE 2.0')

@section('content')
<div class="card">
    <h2>My Invoices</h2>

    @if($invoices->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Type</th>
                <th>Subtotal</th>
                <th>Credits</th>
                <th>Total</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
            <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                <td>{{ strtoupper($invoice->order_type) }}</td>
                <td>${{ number_format($invoice->subtotal, 2) }}</td>
                <td>${{ number_format($invoice->credits_applied, 2) }}</td>
                <td>${{ number_format($invoice->total, 2) }}</td>
                <td>
                    <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'refunded' ? 'danger' : 'warning') }}">
                        {{ strtoupper($invoice->status) }}
                    </span>
                </td>
                <td>
                    <a href="/invoices/{{ $invoice->id }}" class="btn" style="padding: 5px 10px; font-size: 12px;">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $invoices->links() }}
    </div>
    @else
    <p style="margin-top: 20px;">No invoices found.</p>
    @endif
</div>
@endsection