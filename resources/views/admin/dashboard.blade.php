@extends('layouts.app')

@section('title', 'Admin Dashboard - LCE 2.0')

@section('content')
<h1>Admin Dashboard</h1>

<div class="grid" style="margin-top: 30px;">
    <div class="card">
        <h3>Total Customers</h3>
        <p style="font-size: 48px; font-weight: bold; color: #007bff; margin-top: 10px;">
            {{ $total_customers }}
        </p>
    </div>

    <div class="card">
        <h3>Active Subscriptions</h3>
        <p style="font-size: 48px; font-weight: bold; color: #28a745; margin-top: 10px;">
            {{ $active_subscriptions }}
        </p>
    </div>

    <div class="card">
        <h3>Orders Today</h3>
        <p style="font-size: 48px; font-weight: bold; color: #ffc107; margin-top: 10px;">
            {{ $total_orders_today }}
        </p>
    </div>
</div>

<div class="card" style="margin-top: 30px;">
    <h3>Quick Actions</h3>
    <div style="margin-top: 15px;">
        <a href="/admin/customers" class="btn">Manage Customers</a>
    </div>
</div>
@endsection