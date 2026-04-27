<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Requests\Finance\GenerateRecurringForMonthRequest;
use App\Services\Finance\RecurringTransactionService;
use Illuminate\Http\JsonResponse;

class RecurringGenerateApiController extends FinanceApiController
{
    public function store(
        GenerateRecurringForMonthRequest $request,
        RecurringTransactionService $recurring,
    ): JsonResponse {
        $payload = $recurring->generateForMonth(
            (int) $request->user()->id,
            $request->yearMonth(),
        );

        return response()->json([
            'month' => $request->yearMonth(),
            'created_count' => count($payload['generated']),
            'skipped' => $payload['skipped'],
            'data' => $payload['generated'],
        ]);
    }
}
