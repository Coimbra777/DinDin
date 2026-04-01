<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Services\Finance\FinanceProjectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectionApiController extends FinanceApiController
{
    public function show(Request $request): JsonResponse
    {
        $payload = FinanceProjectionService::project((int) $request->user()->id);

        return response()->json($payload);
    }
}
