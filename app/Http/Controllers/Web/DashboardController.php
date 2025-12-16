<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show customer dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $data = [
            'user' => $user,
            'availableCredit' => $user->getAvailableCredit(),
            'activeSubscription' => $user->activeSubscription(),
            'recentPickups' => $user->pickups()->orderBy('created_at', 'desc')->limit(5)->get(),
            'recentInvoices' => $user->invoices()->orderBy('created_at', 'desc')->limit(5)->get(),
        ];

        return view('dashboard', $data);
    }
}
