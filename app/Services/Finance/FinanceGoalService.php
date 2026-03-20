<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\FinanceGoal;
use App\Models\Finance\Transaction;
use Carbon\Carbon;

final class FinanceGoalService
{
    public function progressPercent(FinanceGoal $goal): float
    {
        $target = (float) $goal->target_amount;
        if ($target <= 0) {
            return 0.0;
        }
        $pct = ((float) $goal->current_amount / $target) * 100;

        return round(min(100, $pct), 2);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        $items = FinanceGoal::forUser($userId)
            ->with('incomeCategory')
            ->orderBy('deadline')
            ->orderBy('id')
            ->get();

        return $items->map(fn (FinanceGoal $g) => FinanceGoalResource::toArray($g, $this->progressPercent($g)))->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function create(int $userId, array $data): array
    {
        $data['user_id'] = $userId;
        $goal = FinanceGoal::create($data);
        if ($goal->income_category_id !== null) {
            $goal = $this->syncCurrentFromLinkedIncome($goal);
        }
        $fresh = $goal->fresh(['incomeCategory']);
        $fresh = $fresh ?? $goal;

        return FinanceGoalResource::toArray($fresh, $this->progressPercent($fresh));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(FinanceGoal $goal, array $data): array
    {
        $goal->update($data);
        $goal->refresh();
        if ($goal->income_category_id !== null) {
            $goal = $this->syncCurrentFromLinkedIncome($goal);
        }
        $fresh = $goal->fresh(['incomeCategory']);
        $fresh = $fresh ?? $goal;

        return FinanceGoalResource::toArray($fresh, $this->progressPercent($fresh));
    }

    public function delete(FinanceGoal $goal): void
    {
        $goal->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function show(FinanceGoal $goal): array
    {
        $goal->load('incomeCategory');

        return FinanceGoalResource::toArray($goal, $this->progressPercent($goal));
    }

    /**
     * Soma receitas na categoria vinculada entre a criação da meta e o menor entre hoje e o prazo.
     */
    public function syncCurrentFromLinkedIncome(FinanceGoal $goal): FinanceGoal
    {
        if ($goal->income_category_id === null) {
            return $goal;
        }

        $start = Carbon::parse($goal->created_at)->startOfDay();
        $deadlineEnd = $goal->deadline->copy()->endOfDay();
        $todayEnd = now()->endOfDay();
        $end = $deadlineEnd->lt($todayEnd) ? $deadlineEnd : $todayEnd;

        if ($end->lt($start)) {
            $goal->update(['current_amount' => '0.00']);

            return $goal->fresh() ?? $goal;
        }

        $sum = Transaction::query()
            ->forUser((int) $goal->user_id)
            ->income()
            ->where('category_id', $goal->income_category_id)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $goal->update(['current_amount' => $sum]);

        return $goal->fresh() ?? $goal;
    }
}
