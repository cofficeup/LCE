<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\UserInvoice;
use App\Services\BillingService;
use App\Services\CreditService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected CreditService $creditService
    ) {}

    /**
     * Show payment checkout page
     */
    public function checkout(Request $request)
    {
        $invoiceId = $request->invoice_id;
        $invoice = UserInvoice::findOrFail($invoiceId);

        // Check if user owns this invoice
        if ($invoice->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Get available credits
        $availableCredit = $this->creditService->getAvailableBalance(auth()->user());

        // Calculate amounts
        $subtotal = $invoice->subtotal;
        $creditsToApply = min($availableCredit, $subtotal);
        $finalAmount = max(0, $subtotal - $creditsToApply);

        return view('payment.checkout', [
            'invoiceId' => $invoice->id,
            'orderType' => $invoice->order_type,
            'amount' => $subtotal,
            'creditsApplied' => $creditsToApply,
            'finalAmount' => $finalAmount,
        ]);
    }

    /**
     * Process payment
     */
    public function process(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:lce_user_invoice,id',
            'payment_method_id' => 'nullable|string', // From Stripe
        ]);

        $invoice = UserInvoice::findOrFail($validated['invoice_id']);

        // Check ownership
        if ($invoice->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Check if already paid
        if ($invoice->status === 'paid') {
            return redirect('/dashboard')->with('info', 'This invoice is already paid.');
        }

        try {
            // Calculate final amount after credits
            $finalAmount = $invoice->getFinalAmount();

            // Record transaction (Stripe payment happens here)
            $this->billingService->recordTransaction(
                $invoice,
                $finalAmount,
                $validated['payment_method_id'] ?? null,
                'card'
            );

            return redirect('/dashboard')->with('success', 'Payment successful! Order confirmed.');
        } catch (\Exception $e) {
            return back()->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }
}
