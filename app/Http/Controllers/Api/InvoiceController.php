<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserInvoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * List user invoices
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $invoices = UserInvoice::where('user_id', $user->id)
            ->with(['lineItems', 'pickup'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Get invoice details
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $invoice = UserInvoice::where('user_id', $user->id)
            ->where('id', $id)
            ->with(['lineItems', 'pickup', 'transactions'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'invoice' => $invoice,
                'final_amount' => $invoice->getFinalAmount(),
            ],
        ]);
    }
}
