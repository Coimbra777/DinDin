<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use App\Http\Requests\Goals\StoreFinanceGoalRequest;
use App\Http\Requests\Goals\UpdateFinanceGoalRequest;
use App\Models\Finance\FinanceGoal;
use App\Services\Finance\FinanceGoalResource;
use App\Services\Finance\FinanceGoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceGoalApiController extends RestrictedController
{
    public function __construct(
        private readonly FinanceGoalService $goals,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->goals->listForUser((int) $request->user()->id);

        return response()->json(['data' => $data]);
    }

    public function show(FinanceGoal $finance_goal): JsonResponse
    {
        return response()->json($this->goals->show($finance_goal));
    }

    public function store(StoreFinanceGoalRequest $request): JsonResponse
    {
        $payload = $this->goals->create((int) $request->user()->id, $request->validated());

        return response()->json($payload, 201);
    }

    public function update(UpdateFinanceGoalRequest $request, FinanceGoal $finance_goal): JsonResponse
    {
        $payload = $this->goals->update($finance_goal, $request->validated());

        return response()->json($payload);
    }

    public function destroy(FinanceGoal $finance_goal): JsonResponse
    {
        $this->goals->delete($finance_goal);

        return response()->json(['ok' => true]);
    }

    /**
     * Recalcula current_amount a partir das receitas na categoria vinculada (income_category_id).
     */
    public function syncFromIncome(FinanceGoal $finance_goal): JsonResponse
    {
        if ($finance_goal->income_category_id === null) {
            return response()->json([
                'message' => 'Esta meta não possui income_category_id. Vincule uma categoria de receita ou atualize current_amount manualmente.',
            ], 422);
        }
        $goal = $this->goals->syncCurrentFromLinkedIncome($finance_goal);
        $goal->load('incomeCategory');

        return response()->json(
            FinanceGoalResource::toArray($goal, $this->goals->progressPercent($goal))
        );
    }
}
