<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Http\Requests\Finance\SimulateCreditInstallmentRequest;
use App\Services\Finance\CreditInstallmentSimulatorService;
use Illuminate\Http\JsonResponse;

class CreditSimulatorApiController extends RestrictedController
{
    public function __construct(
        private readonly CreditInstallmentSimulatorService $simulator,
    ) {}

    public function simulate(SimulateCreditInstallmentRequest $request): JsonResponse
    {
        $v = $request->validated();
        $interest = isset($v['interest_percent_total']) ? (float) $v['interest_percent_total'] : 0.0;
        $payload = $this->simulator->simulate(
            (float) $v['amount'],
            (int) $v['installments'],
            $interest
        );

        return response()->json($payload);
    }
}
