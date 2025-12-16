<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LCE 2.0')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background: #f4f4f4;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .nav {
            background: #333;
            color: white;
            padding: 15px 0;
        }

        .nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
        }

        .nav a:hover {
            text-decoration: underline;
        }

        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-warning {
            background: #ffc107;
            color: black;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .badge-secondary {
            background: #6c757d;
            color: white;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
    </style>
</head>

<body>
    @auth
    <nav class="nav">
        <div class="container">
            <div>
                <a href="/dashboard" style="font-weight: bold; font-size: 20px;">LCE 2.0</a>
                <a href="/dashboard">Dashboard</a>
                <a href="/orders/create">New Order</a>
                <a href="/subscriptions/create">Subscription</a>
                <a href="/invoices">Invoices</a>
                @if(auth()->user()->isAdmin() || auth()->user()->isCsr())
                <a href="/admin">Admin</a>
                @endif
            </div>
            <div>
                <span style="margin-right: 15px;">{{ auth()->user()->name }}</span>
                <form action="/logout" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="padding: 5px 15px;">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    @endauth

    <div class="container">
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
        <div class="alert alert-error">
            <ul style="margin-left: 20px;">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @yield('content')
    </div>
</body>

</html>