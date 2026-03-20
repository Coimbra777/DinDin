<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Previsão de saldo — próximos N meses: média móvel + parcelas futuras.
 */
final class FinanceProjectionService
{
    private const MONTHS_AHEAD = 12;

    private const REFERENCE_MONTHS = 3;

    /** @var list<string> */
    private static array $parsePatterns = [
        '/\b(\d{1,2})\s*\/\s*(\d{1,3})\b/u',
        '/parcela\s*#?\s*(\d{1,2})\s*(?:de|\/|\/\s*)\s*(\d{1,3})/iu',
        '/\b(\d{1,2})\s*[x×]\s*de\s*(\d{1,3})\b/iu',
    ];

    /**
     * @return array{
     *     meses: list<array{mes: string, label: string, receitas_previstas: float, despesas_previstas: float, saldo_projetado: float}>,
     *     meta: array{media_receitas_base: float, media_despesas_base: float, meses_referencia: list<string>, parcelas_detectadas: int}
     * }
     */
    public static function project(int $userId, ?Carbon $now = null): array
    {
        $now = $now ? $now->copy()->timezone(config('app.timezone')) : now(config('app.timezone'));
        $firstProjected = $now->copy()->startOfMonth()->addMonth();

        $referenceKeys = self::referenceMonthKeys($firstProjected, self::REFERENCE_MONTHS);
        $avgIncome = self::averageMonthlyIncomeExcludingInstallments($userId, $referenceKeys);
        $avgExpense = self::averageMonthlyExpenseExcludingInstallments($userId, $referenceKeys);

        $extrasByMonth = self::installmentExtrasByMonth($userId, $firstProjected, self::MONTHS_AHEAD);

        $meses = [];
        for ($i = 0; $i < self::MONTHS_AHEAD; $i++) {
            $cursor = $firstProjected->copy()->addMonths($i);
            $key = $cursor->format('Y-m');
            $extraIn = (float) ($extrasByMonth[$key][Transaction::TYPE_INCOME] ?? 0);
            $extraOut = (float) ($extrasByMonth[$key][Transaction::TYPE_EXPENSE] ?? 0);
            $rec = round($avgIncome + $extraIn, 2);
            $des = round($avgExpense + $extraOut, 2);
            $saldo = round($rec - $des, 2);
            $meses[] = [
                'mes' => $key,
                'label' => self::monthLabelPt($cursor),
                'receitas_previstas' => $rec,
                'despesas_previstas' => $des,
                'saldo_projetado' => $saldo,
            ];
        }

        $parcelasCount = self::countInstallmentSources($userId);

        return [
            'meses' => $meses,
            'meta' => [
                'media_receitas_base' => round($avgIncome, 2),
                'media_despesas_base' => round($avgExpense, 2),
                'meses_referencia' => $referenceKeys,
                'parcelas_detectadas' => $parcelasCount,
            ],
        ];
    }

    /**
     * @param  list<string>  $yearMonths
     */
    private static function averageMonthlyIncomeExcludingInstallments(int $userId, array $yearMonths): float
    {
        if ($yearMonths === []) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($yearMonths as $ym) {
            $sum += self::monthTotal($userId, $ym, Transaction::TYPE_INCOME, excludeInstallments: true, excludeCreditCard: false);
        }

        return $sum / count($yearMonths);
    }

    /**
     * @param  list<string>  $yearMonths
     */
    private static function averageMonthlyExpenseExcludingInstallments(int $userId, array $yearMonths): float
    {
        if ($yearMonths === []) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($yearMonths as $ym) {
            $sum += self::monthTotal($userId, $ym, Transaction::TYPE_EXPENSE, excludeInstallments: true, excludeCreditCard: true);
        }

        return $sum / count($yearMonths);
    }

    private static function monthTotal(
        int $userId,
        string $yearMonth,
        string $type,
        bool $excludeInstallments,
        bool $excludeCreditCard = false,
    ): float {
        $q = Transaction::query()->forUser($userId)->where('type', $type)->filter(['month' => $yearMonth]);
        if ($excludeInstallments) {
            $q->whereRaw('installment_of IS NULL AND installment_number IS NULL');
        }
        if ($excludeCreditCard && $type === Transaction::TYPE_EXPENSE) {
            $q->where(function ($w) {
                $w->where('is_credit_card', false)->orWhereNull('is_credit_card');
            });
        }

        return (float) $q->sum('amount');
    }

    /**
     * @return list<string>  Três meses civis imediatamente anteriores ao primeiro mês projetado
     */
    private static function referenceMonthKeys(Carbon $firstProjected, int $count): array
    {
        $keys = [];
        for ($i = $count; $i >= 1; $i--) {
            $keys[] = $firstProjected->copy()->subMonths($i)->format('Y-m');
        }

        return $keys;
    }

    /**
     * Acumula receitas/despesas extra por mês (parcelas futuras).
     *
     * @return array<string, array<string, float>> [Y-m => [income|expense => amount]]
     */
    private static function installmentExtrasByMonth(int $userId, Carbon $firstProjected, int $monthsAhead): array
    {
        $lastProjected = $firstProjected->copy()->addMonths($monthsAhead - 1)->endOfMonth();
        $map = [];

        /** @var Collection<int, Transaction> $fromDb */
        $fromDb = Transaction::query()
            ->forUser($userId)
            ->whereNotNull('installment_of')
            ->whereNotNull('installment_number')
            ->whereColumn('installment_number', '<', 'installment_of')
            ->get();

        foreach ($fromDb as $t) {
            self::spreadInstallmentToMap($map, $t->type, (float) $t->amount, (int) $t->installment_number, (int) $t->installment_of, Carbon::parse($t->transaction_date), $firstProjected, $lastProjected);
        }

        /** Inferência (título/descrição) só quando não há campos de parcela */
        $candidates = Transaction::query()
            ->forUser($userId)
            ->where(function ($w) {
                $w->whereNull('installment_of')->orWhereNull('installment_number');
            })
            ->whereRaw('transaction_date >= ?', [now()->subDays(365)->toDateString()])
            ->orderByDesc('transaction_date')
            ->limit(500)
            ->get();

        foreach ($candidates as $t) {
            if ($t->installment_of !== null && $t->installment_number !== null) {
                continue;
            }
            $parsed = self::parseInstallmentFromText((string) $t->title.' '.(string) $t->description);
            if ($parsed === null) {
                continue;
            }
            [$cur, $total] = $parsed;
            if ($cur >= $total || $cur < 1) {
                continue;
            }
            self::spreadInstallmentToMap($map, $t->type, (float) $t->amount, $cur, $total, Carbon::parse($t->transaction_date), $firstProjected, $lastProjected);
        }

        return self::roundMap($map);
    }

    /**
     * @param  array<string, array<string, float>>  $map
     */
    private static function spreadInstallmentToMap(
        array &$map,
        string $type,
        float $amount,
        int $installmentNumber,
        int $installmentOf,
        Carbon $transactionDate,
        Carbon $firstProjectedStart,
        Carbon $lastProjectedEnd,
    ): void {
        $remaining = $installmentOf - $installmentNumber;
        if ($remaining <= 0) {
            return;
        }

        $anchor = $transactionDate->copy()->startOfMonth();
        for ($i = 1; $i <= $remaining; $i++) {
            $target = $anchor->copy()->addMonths($i);
            if ($target->lt($firstProjectedStart->copy()->startOfMonth()) || $target->gt($lastProjectedEnd)) {
                continue;
            }
            $key = $target->format('Y-m');
            if (! isset($map[$key])) {
                $map[$key] = [Transaction::TYPE_INCOME => 0.0, Transaction::TYPE_EXPENSE => 0.0];
            }
            $map[$key][$type] = ($map[$key][$type] ?? 0) + $amount;
        }
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private static function parseInstallmentFromText(string $text): ?array
    {
        $text = trim($text);
        foreach (self::$parsePatterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $a = (int) $m[1];
                $b = (int) $m[2];
                if ($a < 1 || $b < 2 || $a > $b) {
                    continue;
                }

                return [$a, $b];
            }
        }

        return null;
    }

    /**
     * @param  array<string, array<string, float>>  $map
     * @return array<string, array<string, float>>
     */
    private static function roundMap(array $map): array
    {
        foreach ($map as $k => $inner) {
            foreach ($inner as $t => $v) {
                $map[$k][$t] = round($v, 2);
            }
        }

        return $map;
    }

    private static function monthLabelPt(Carbon $d): string
    {
        $months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

        return $months[$d->month - 1].'/'.$d->format('Y');
    }

    private static function countInstallmentSources(int $userId): int
    {
        $db = (int) Transaction::query()
            ->forUser($userId)
            ->whereNotNull('installment_of')
            ->whereNotNull('installment_number')
            ->whereColumn('installment_number', '<', 'installment_of')
            ->count();

        $parsed = 0;
        $rows = Transaction::query()
            ->forUser($userId)
            ->where(function ($w) {
                $w->whereNull('installment_of')->orWhereNull('installment_number');
            })
            ->whereRaw('transaction_date >= ?', [now()->subDays(365)->toDateString()])
            ->orderByDesc('transaction_date')
            ->limit(500)
            ->get(['title', 'description']);

        foreach ($rows as $r) {
            if (self::parseInstallmentFromText((string) $r->title.' '.(string) $r->description) !== null) {
                $parsed++;
            }
        }

        return $db + $parsed;
    }
}
