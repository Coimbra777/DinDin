<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Services\Finance\FinancialSummaryService;
use App\Services\Finance\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends FinanceApiController
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly FinancialSummaryService $summaries,
    ) {
        parent::__construct();
    }

    public function categories(Request $request): JsonResponse
    {
        $month = $request->query('month');
        $userId = (int) $request->user()->id;

        return response()->json([
            'month' => $this->summaries->normalizeMonth(is_string($month) ? $month : null),
            'categories' => $this->reports->categoryBreakdown($userId, is_string($month) ? $month : null),
        ]);
    }

    public function trend(Request $request): JsonResponse
    {
        $n = (int) $request->query('months', 6);
        $userId = (int) $request->user()->id;

        return response()->json([
            'months' => max(1, min(36, $n)),
            'series' => $this->reports->monthlyTrend($userId, $n),
        ]);
    }
}
