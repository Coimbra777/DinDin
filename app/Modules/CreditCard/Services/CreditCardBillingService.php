<?php

declare(strict_types=1);

namespace App\Modules\CreditCard\Services;

use App\Modules\CreditCard\Models\CreditCard;
use App\Modules\Finance\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class CreditCardBillingService
{
    /**
     * Ciclo de fatura aberto: do dia seguinte ao fechamento anterior até o dia de fechamento (inclusive).
     *
     * @return array{0: Carbon, 1: Carbon} [start, end] em início do dia
     */
    public static function currentBillingPeriod(CreditCard $card, ?Carbon $reference = null): array
    {
        $reference = $reference ? $reference->copy()->timezone(config('app.timezone')) : now(config('app.timezone'));
        $closingDay = max(1, min(31, (int) $card->closing_day));
        $y = (int) $reference->year;
        $m = (int) $reference->month;

        $closeThisMonth = self::closingDateForMonth($y, $m, $closingDay);

        if ($reference->copy()->startOfDay()->gt($closeThisMonth)) {
            $start = $closeThisMonth->copy()->addDay()->startOfDay();
            $next = $closeThisMonth->copy()->addMonthNoOverflow();
            $end = self::closingDateForMonth((int) $next->year, (int) $next->month, $closingDay)->endOfDay();
        } else {
            $prev = $closeThisMonth->copy()->subMonthNoOverflow();
            $prevClose = self::closingDateForMonth((int) $prev->year, (int) $prev->month, $closingDay);
            $start = $prevClose->copy()->addDay()->startOfDay();
            $end = $closeThisMonth->copy()->endOfDay();
        }

        return [$start, $end];
    }

    private static function closingDateForMonth(int $year, int $month, int $closingDay): Carbon
    {
        $last = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $d = min($closingDay, $last);

        return Carbon::createFromDate($year, $month, $d, config('app.timezone'))->startOfDay();
    }

    /**
     * @return array{
     *     credit_card: array<string, mixed>,
     *     period: array{start: string, end: string},
     *     fatura_total: float,
     *     transacoes: list<array<string, mixed>>,
     *     limite_disponivel: float,
     *     utilizado_no_cartao: float
     * }
     */
    public static function billPayload(CreditCard $card): array
    {
        [$start, $end] = self::currentBillingPeriod($card);
        $userId = (int) $card->user_id;

        /** @var Collection<int, Transaction> $items */
        $items = Transaction::query()
            ->forUser($userId)
            ->where('credit_card_id', $card->id)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->where('is_credit_card', true)
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->with('category')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $faturaTotal = (float) $items->sum(fn (Transaction $t) => (float) $t->amount);

        $utilizadoNoCartao = (float) Transaction::query()
            ->forUser($userId)
            ->where('credit_card_id', $card->id)
            ->where('type', Transaction::TYPE_EXPENSE)
            ->where('is_credit_card', true)
            ->sum('amount');

        $limite = (float) $card->credit_limit;
        $limiteDisponivel = max(0.0, round($limite - $utilizadoNoCartao, 2));

        return [
            'credit_card' => [
                'id' => $card->id,
                'name' => $card->name,
                'limit' => round($limite, 2),
                'closing_day' => (int) $card->closing_day,
                'due_day' => (int) $card->due_day,
            ],
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'fatura_total' => round($faturaTotal, 2),
            'transacoes' => $items->map(fn (Transaction $t) => self::transactionRow($t))->all(),
            'limite_disponivel' => round($limiteDisponivel, 2),
            'utilizado_no_cartao' => round($utilizadoNoCartao, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function transactionRow(Transaction $t): array
    {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'amount' => (float) $t->amount,
            'type' => $t->type,
            'transaction_date' => $t->transaction_date->format('Y-m-d'),
            'description' => $t->description,
            'category_id' => $t->category_id,
            'credit_card_id' => $t->credit_card_id,
            'is_credit_card' => (bool) $t->is_credit_card,
            'category' => $t->category ? [
                'id' => $t->category->id,
                'name' => $t->category->name,
                'color' => $t->category->color,
            ] : null,
        ];
    }
}
