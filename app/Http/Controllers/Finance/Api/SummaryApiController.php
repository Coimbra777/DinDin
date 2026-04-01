<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Services\Finance\SummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryApiController extends FinanceApiController
{
    public function __construct(
        private readonly SummaryService $summary,
    ) {
        parent::__construct();
    }

    public function show(Request $request): JsonResponse
    {
        $payload = $this->summary->forMonth(
            (int) $request->user()->id,
            $request->query('month')
        );

        return response()->json($payload);
    }
}
