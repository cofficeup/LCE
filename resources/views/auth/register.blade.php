@extends('layouts.app')

@section('title', 'Register - LCE 2.0')

@section('content')
<div class="card" style="max-width: 500px; margin: 50px auto;">
    <h2 style="margin-bottom: 20px;">Register</h2>
    <p style="margin-bottom: 20px; color: #28a745; font-weight: bold;">Get $20 Welcome Credit!</p>

    <form method="POST" action="/register">
        @csrf

        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="{{ old('address') }}" required>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 10px;">
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="{{ old('city') }}">
            </div>

            <div class="form-group">
                <label for="state">State</label>
                <input type="text" id="state" name="state" value="{{ old('state') }}" maxlength="2">
            </div>

            <div class="form-group">
                <label for="zip">ZIP</label>
                <input type="text" id="zip" name="zip" value="{{ old('zip') }}">
            </div>
        </div>

        <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">Register</button>
    </form>

    <p style="margin-top: 20px; text-align: center;">
        Already have an account? <a href="/login">Login here</a>
    </p>
</div>
@endsection