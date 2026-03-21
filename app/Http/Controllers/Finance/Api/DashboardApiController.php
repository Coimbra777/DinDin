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

    public function upcoming(Request $request): JsonResponse
    {
        $limit = min(60, max(1, (int) $request->query('limit', 30)));

        return response()->json([
            'data' => $this->dashboard->getUpcomingCommitments((int) $request->user()->id, $limit),
        ]);
    }
}
