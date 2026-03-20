<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Services\Finance\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends RestrictedController
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {
        parent::__construct();
    }

    public function show(Request $request): JsonResponse
    {
        $payload = $this->dashboard->buildPayload(
            (int) $request->user()->id,
            $request->query('month')
        );

        return response()->json($payload);
    }
}
