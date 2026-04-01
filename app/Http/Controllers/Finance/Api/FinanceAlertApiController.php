<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Services\Finance\FinanceAlertService;
use App\Services\Finance\FinancialSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceAlertApiController extends FinanceApiController
{
    public function __construct(
        private readonly FinanceAlertService $alerts,
        private readonly FinancialSummaryService $summaries,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->alerts->forUser((int) $request->user()->id, $request->query('month'));

        return response()->json([
            'month' => $this->summaries->normalizeMonth(is_string($request->query('month')) ? $request->query('month') : null),
            'alerts' => $data,
        ]);
    }
}
