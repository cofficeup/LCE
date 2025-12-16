<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * List invoices
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $invoices = $user->invoices()
            ->with(['lineItems', 'pickup'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('invoices.index', ['invoices' => $invoices]);
    }

    /**
     * Show invoice details
     */
    public function show($id)
    {
        $user = auth()->user();

        $invoice = $user->invoices()
            ->where('id', $id)
            ->with(['lineItems', 'pickup', 'transactions'])
            ->firstOrFail();

        return view('invoices.show', ['invoice' => $invoice]);
    }
}
