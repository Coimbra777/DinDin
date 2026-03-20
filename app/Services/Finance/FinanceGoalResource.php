<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\FinanceGoal;

final class FinanceGoalResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(FinanceGoal $goal, float $progressPercent): array
    {
        $goal->loadMissing('incomeCategory');

        return [
            'id' => $goal->id,
            'title' => $goal->title,
            'description' => $goal->description,
            'target_amount' => (float) $goal->target_amount,
            'current_amount' => (float) $goal->current_amount,
            'deadline' => $goal->deadline->format('Y-m-d'),
            'progress_percent' => $progressPercent,
            'income_category_id' => $goal->income_category_id,
            'income_category' => $goal->incomeCategory ? [
                'id' => $goal->incomeCategory->id,
                'name' => $goal->incomeCategory->name,
                'color' => $goal->incomeCategory->color,
            ] : null,
            'created_at' => $goal->created_at?->toIso8601String(),
            'updated_at' => $goal->updated_at?->toIso8601String(),
        ];
    }
}
