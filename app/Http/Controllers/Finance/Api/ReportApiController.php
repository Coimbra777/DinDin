<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Models\Finance\Transaction;
use App\Services\Finance\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends RestrictedController
{
    public function __construct(
        private readonly ReportService $reports,
    ) {
        parent::__construct();
    }

    public function categories(Request $request): JsonResponse
    {
        $month = $request->query('month');
        $userId = (int) $request->user()->id;

        return response()->json([
            'month' => Transaction::normalizeMonth(is_string($month) ? $month : null),
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
