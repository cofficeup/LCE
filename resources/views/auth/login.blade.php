@extends('layouts.app')

@section('title', 'Login - LCE 2.0')

@section('content')
<div class="card" style="max-width: 400px; margin: 100px auto;">
    <h2 style="margin-bottom: 20px;">Login</h2>

    <form method="POST" action="/login">
        @csrf

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">Login</button>
    </form>

    <p style="margin-top: 20px; text-align: center;">
        Don't have an account? <a href="/register">Register here</a>
    </p>
</div>
@endsection