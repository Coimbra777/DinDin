<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\FinanceGoal;
use App\Models\Finance\Transaction;
use Carbon\Carbon;

final class FinanceGoalService
{
    /** Considera meta “próxima do prazo” com este número de dias ou menos. */
    public const DEADLINE_NEAR_DAYS = 30;

    /** Dias mínimos desde a criação para emitir alerta de ritmo (metas novas não disparam na hora). */
    public const LINEAR_PACE_ALERT_MIN_ELAPSED_DAYS = 7;

    public function __construct(
        private readonly FinanceMonthMetrics $monthMetrics,
    ) {}

    public function progressPercent(FinanceGoal $goal): float
    {
        return $this->progressPercentForAmounts((float) $goal->current_amount, (float) $goal->target_amount);
    }

    public function progressPercentForAmounts(float $currentAmount, float $targetAmount): float
    {
        if ($targetAmount <= 0) {
            return 0.0;
        }
        $pct = ($currentAmount / $targetAmount) * 100;

        return round(min(100, $pct), 2);
    }

    /**
     * Valor “real” para progresso: com categoria de receita, soma transações (mesma regra do sync, só leitura).
     */
    public function effectiveCurrentAmount(FinanceGoal $goal): float
    {
        if ($goal->income_category_id === null) {
            return (float) $goal->current_amount;
        }

        $start = Carbon::parse($goal->created_at)->startOfDay();
        $deadlineEnd = $goal->deadline->copy()->endOfDay();
        $todayEnd = now()->endOfDay();
        $end = $deadlineEnd->lt($todayEnd) ? $deadlineEnd : $todayEnd;

        if ($end->lt($start)) {
            return 0.0;
        }

        return (float) Transaction::query()
            ->forUser((int) $goal->user_id)
            ->income()
            ->where('category_id', $goal->income_category_id)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
    }

    /**
     * Indicadores para UI e alertas (sem inventar valores — só meta + datas + valor efetivo).
     *
     * @return array{
     *     remaining_amount: float,
     *     days_remaining: int|null,
     *     months_remaining_approx: float|null,
     *     needed_per_month: float|null,
     *     insight_summary: string|null,
     *     is_deadline_near: bool,
     *     is_past_deadline: bool
     * }
     */
    public function paceInsights(FinanceGoal $goal, float $effectiveCurrent, ?Carbon $now = null): array
    {
        $now = $now ? $now->copy() : now();
        $target = (float) $goal->target_amount;
        $remaining = round(max(0, $target - $effectiveCurrent), 2);

        $deadlineStart = $goal->deadline->copy()->startOfDay();
        $todayStart = $now->copy()->startOfDay();
        $isPastDeadline = $deadlineStart->lt($todayStart);

        $daysRemaining = null;
        $monthsApprox = null;
        $neededPerMonth = null;
        $insight = null;
        $isNear = false;

        if (! $isPastDeadline && $remaining > 0.00001) {
            $daysRemaining = max(1, (int) $todayStart->diffInDays($deadlineStart, false));
            $monthsApprox = max($daysRemaining / 30.0, 1 / 30.0);
            $neededPerMonth = round($remaining / max($monthsApprox, 0.01), 2);
            $mRounded = max(1, (int) ceil($monthsApprox));
            $insight = sprintf(
                'Faltam %s em %d dias (~%s). Para fechar no prazo: cerca de %s por mês.',
                'R$ '.number_format($remaining, 2, ',', '.'),
                $daysRemaining,
                $mRounded === 1 ? '1 mês' : sprintf('%d meses', $mRounded),
                'R$ '.number_format($neededPerMonth, 2, ',', '.')
            );
            $isNear = $daysRemaining <= self::DEADLINE_NEAR_DAYS;
        } elseif ($remaining <= 0.00001 && ! $isPastDeadline) {
            $insight = 'Meta atingida ou superada no valor acompanhado. Parabéns!';
        }

        return [
            'remaining_amount' => $remaining,
            'days_remaining' => $daysRemaining,
            'months_remaining_approx' => $monthsApprox !== null ? round($monthsApprox, 2) : null,
            'needed_per_month' => $neededPerMonth,
            'insight_summary' => $insight,
            'is_deadline_near' => $isNear,
            'is_past_deadline' => $isPastDeadline,
        ];
    }

    /**
     * Ritmo linear desde a criação: se continuar assim, quanto falta no fim do prazo (para alerta goal_risk).
     *
     * @return array{at_risk: bool, projected_total: float, shortfall: float, pace_per_day: float}|null null se não aplicável
     */
    public function linearPaceShortfall(FinanceGoal $goal, float $effectiveCurrent, ?Carbon $now = null, int $minElapsedDays = 0): ?array
    {
        $now = $now ? $now->copy() : now();
        $target = (float) $goal->target_amount;
        $remaining = $target - $effectiveCurrent;
        if ($remaining <= 0.00001) {
            return null;
        }

        $deadlineEnd = $goal->deadline->copy()->endOfDay();
        if ($deadlineEnd->lt($now)) {
            return null;
        }

        $createdStart = $goal->created_at->copy()->startOfDay();
        $todayStart = $now->copy()->startOfDay();
        $elapsedDays = max(1, (int) $createdStart->diffInDays($todayStart, false));
        if ($elapsedDays < $minElapsedDays) {
            return null;
        }

        $remainingDays = max(1, (int) $todayStart->diffInDays($goal->deadline->copy()->startOfDay(), false));

        $pacePerDay = $effectiveCurrent / $elapsedDays;
        $projectedTotal = $effectiveCurrent + ($pacePerDay * $remainingDays);
        $shortfall = $target - $projectedTotal;

        if ($shortfall <= 0.01) {
            return null;
        }

        return [
            'at_risk' => true,
            'projected_total' => round($projectedTotal, 2),
            'shortfall' => round($shortfall, 2),
            'pace_per_day' => round($pacePerDay, 4),
        ];
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

        return $items->map(fn (FinanceGoal $g) => $this->serializeGoal($g))->all();
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

        return $this->serializeGoal($fresh);
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

        return $this->serializeGoal($fresh);
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

        return $this->serializeGoal($goal);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeGoal(FinanceGoal $goal): array
    {
        $goal->loadMissing('incomeCategory');
        $effective = $this->effectiveCurrentAmount($goal);
        $progress = $this->progressPercentForAmounts($effective, (float) $goal->target_amount);
        $insights = $this->paceInsights($goal, $effective);

        $snap = $this->monthMetrics->snapshot((int) $goal->user_id, null);
        $contextNote = null;
        if (
            ($snap['negative_balance'] || $snap['spending_spike'])
            && $insights['remaining_amount'] > 0.00001
            && ! $insights['is_past_deadline']
        ) {
            $contextNote = 'Se continuar nesse ritmo de gastos neste mês, fica mais difícil guardar para esta meta.';
        }

        return array_merge(
            FinanceGoalResource::toArray($goal, $progress),
            [
                'current_amount' => round($effective, 2),
                'remaining_amount' => $insights['remaining_amount'],
                'days_remaining' => $insights['days_remaining'],
                'months_remaining_approx' => $insights['months_remaining_approx'],
                'needed_per_month' => $insights['needed_per_month'],
                'insight_summary' => $insights['insight_summary'],
                'is_deadline_near' => $insights['is_deadline_near'],
                'is_past_deadline' => $insights['is_past_deadline'],
                'context_note' => $contextNote,
            ]
        );
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
