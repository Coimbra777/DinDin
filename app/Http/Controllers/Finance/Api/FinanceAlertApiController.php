<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Models\Finance\Transaction;
use App\Services\Finance\FinanceAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceAlertApiController extends RestrictedController
{
    public function __construct(
        private readonly FinanceAlertService $alerts,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->alerts->forUser((int) $request->user()->id, $request->query('month'));

        return response()->json([
            'month' => Transaction::normalizeMonth($request->query('month')),
            'alerts' => $data,
        ]);
    }
}
