<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CreditService;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Get credit balance
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $balance = $this->creditService->getAvailableBalance($user);

        return response()->json([
            'success' => true,
            'data' => [
                'available_credit' => $balance,
            ],
        ]);
    }

    /**
     * Get credit history
     */
    public function history(Request $request)
    {
        $user = $request->user();

        $credits = $this->creditService->getCreditHistory($user);

        return response()->json([
            'success' => true,
            'data' => $credits,
        ]);
    }
}
