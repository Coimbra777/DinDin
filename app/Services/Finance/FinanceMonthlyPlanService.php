<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\FinanceMonthlyPlan;

final class FinanceMonthlyPlanService
{
    public function __construct(
        private readonly FinancialSummaryService $summaries,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        $items = FinanceMonthlyPlan::forUser($userId)
            ->orderByDesc('year_month')
            ->get();

        return $items->map(fn (FinanceMonthlyPlan $p) => $this->toArrayWithActuals($p))->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function show(FinanceMonthlyPlan $plan): array
    {
        return $this->toArrayWithActuals($plan);
    }

    /**
     * @param  array{year_month: string, planned_expense?: float|int|string, planned_saving?: float|int|string}  $data
     */
    public function create(int $userId, array $data): array
    {
        $data['user_id'] = $userId;
        $plan = FinanceMonthlyPlan::create($data);

        return $this->toArrayWithActuals($plan->fresh());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FinanceMonthlyPlan $plan, array $data): array
    {
        $plan->update($data);

        return $this->toArrayWithActuals($plan->fresh());
    }

    public function delete(FinanceMonthlyPlan $plan): void
    {
        $plan->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArrayWithActuals(FinanceMonthlyPlan $plan): array
    {
        $userId = (int) $plan->user_id;
        $m = $plan->year_month;
        $row = $this->summaries->aggregateMonthStats($userId, $m, null);
        $income = (float) ($row->income_total ?? 0);
        $exp = (float) ($row->expense_total ?? 0);
        $savingActual = $income - $exp;

        return [
            'id' => $plan->id,
            'year_month' => $plan->year_month,
            'planned_expense' => (float) $plan->planned_expense,
            'planned_saving' => (float) $plan->planned_saving,
            'actual_income' => round($income, 2),
            'actual_total_expense' => round($exp, 2),
            'actual_net_after_expenses' => round($savingActual, 2),
            'delta_expense_vs_plan' => round($exp - (float) $plan->planned_expense, 2),
            'delta_saving_vs_plan' => round($savingActual - (float) $plan->planned_saving, 2),
            'created_at' => $plan->created_at?->toIso8601String(),
            'updated_at' => $plan->updated_at?->toIso8601String(),
        ];
    }
}
