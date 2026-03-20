<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Services\Finance\FinanceInsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceInsightApiController extends RestrictedController
{
    public function __construct(
        private readonly FinanceInsightService $insights,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payload = $this->insights->forUser((int) $request->user()->id, $request->query('month'));

        return response()->json($payload);
    }
}
