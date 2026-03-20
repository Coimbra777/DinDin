<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Modules\Finance\Services\SummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryApiController extends RestrictedController
{
    public function __construct(
        private readonly SummaryService $summary,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $payload = $this->summary->forMonth(
            (int) $request->user()->id,
            $request->query('month')
        );

        return response()->json($payload);
    }
}
