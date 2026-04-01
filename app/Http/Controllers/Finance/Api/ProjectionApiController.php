<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Services\Finance\FinanceProjectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectionApiController extends FinanceApiController
{
    public function __construct(
        private readonly FinanceProjectionService $projection,
    ) {
        parent::__construct();
    }

    public function show(Request $request): JsonResponse
    {
        $payload = $this->projection->project((int) $request->user()->id);

        return response()->json($payload);
    }
}
