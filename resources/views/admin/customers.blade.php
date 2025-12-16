@extends('layouts.app')

@section('title', 'Customer Management - LCE 2.0')

@section('content')
<div class="card">
    <h2>Customer Management</h2>

    <form method="GET" action="/admin/customers" style="margin-top: 20px;">
        <div class="form-group">
            <input type="text" name="search" placeholder="Search by name, email, or phone..." value="{{ request('search') }}" style="width: 100%;">
        </div>
        <button type="submit" class="btn">Search</button>
    </form>

    @if($customers->count() > 0)
    <table style="margin-top: 30px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Subscriptions</th>
                <th>Orders</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $customer)
            <tr>
                <td>{{ $customer->id }}</td>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->email }}</td>
                <td>{{ $customer->phone }}</td>
                <td>{{ $customer->subscriptions_count }}</td>
                <td>{{ $customer->pickups_count }}</td>
                <td>
                    <a href="/admin/customers/{{ $customer->id }}" class="btn" style="padding: 5px 10px; font-size: 12px;">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $customers->links() }}
    </div>
    @else
    <p style="margin-top: 20px;">No customers found.</p>
    @endif
</div>
@endsection