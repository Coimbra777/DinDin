<?php

declare(strict_types=1);

namespace App\Modules\Projection\Http\Controllers\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Modules\Projection\Services\FinanceProjectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectionApiController extends RestrictedController
{
    public function show(Request $request): JsonResponse
    {
        $payload = FinanceProjectionService::project((int) $request->user()->id);

        return response()->json($payload);
    }
}
