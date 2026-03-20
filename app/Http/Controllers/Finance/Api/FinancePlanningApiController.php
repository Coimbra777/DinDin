<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Http\Requests\Finance\StoreFinanceMonthlyPlanRequest;
use App\Http\Requests\Finance\UpdateFinanceMonthlyPlanRequest;
use App\Models\Finance\FinanceMonthlyPlan;
use App\Services\Finance\FinanceMonthlyPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancePlanningApiController extends RestrictedController
{
    public function __construct(
        private readonly FinanceMonthlyPlanService $planning,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->planning->listForUser((int) $request->user()->id);

        return response()->json(['data' => $data]);
    }

    public function show(FinanceMonthlyPlan $finance_monthly_plan): JsonResponse
    {
        return response()->json($this->planning->show($finance_monthly_plan));
    }

    public function store(StoreFinanceMonthlyPlanRequest $request): JsonResponse
    {
        $payload = $this->planning->create((int) $request->user()->id, $request->validated());

        return response()->json($payload, 201);
    }

    public function update(UpdateFinanceMonthlyPlanRequest $request, FinanceMonthlyPlan $finance_monthly_plan): JsonResponse
    {
        $payload = $this->planning->update($finance_monthly_plan, $request->validated());

        return response()->json($payload);
    }

    public function destroy(FinanceMonthlyPlan $finance_monthly_plan): JsonResponse
    {
        $this->planning->delete($finance_monthly_plan);

        return response()->json(['ok' => true]);
    }
}
