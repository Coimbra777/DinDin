<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Controllers\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Modules\Finance\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends RestrictedController
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $payload = $this->dashboard->buildPayload(
            (int) $request->user()->id,
            $request->query('month')
        );

        return response()->json($payload);
    }
}
