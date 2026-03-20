<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\CreditCard;
use App\Models\Finance\Transaction;
use Carbon\Carbon;

/**
 * Alertas derivados de transações e cartões (sem persistência).
 *
 * @phpstan-type AlertItem array{type: string, severity: string, message: string, meta?: array<string, mixed>}
 */
final class FinanceAlertService
{
    private const SPENDING_SPIKE_RATIO = 1.25;

    private const HIGH_BILL_LIMIT_RATIO = 0.50;

    /**
     * @return list<AlertItem>
     */
    public function forUser(int $userId, ?string $monthQuery = null): array
    {
        $month = Transaction::normalizeMonth($monthQuery);
        $alerts = [];

        $row = Transaction::aggregateMonthStats($userId, $month, null);
        $income = (float) ($row->income_total ?? 0);
        $expCash = (float) ($row->expense_cash ?? 0);
        $expCard = (float) ($row->expense_card ?? 0);
        $totalExpense = $expCash + $expCard;
        $saldoComCartao = $income - $expCash - $expCard;

        if ($saldoComCartao < 0) {
            $alerts[] = [
                'type' => 'negative_balance',
                'severity' => 'warning',
                'message' => 'Seu saldo ficará negativo neste mês se considerar cartão de crédito.',
                'meta' => [
                    'month' => $month,
                    'saldo_com_cartao' => round($saldoComCartao, 2),
                ],
            ];
        }

        $avgPrior = $this->averageTotalExpensePriorMonths($userId, $month, 3);
        if ($avgPrior > 0 && $totalExpense > $avgPrior * self::SPENDING_SPIKE_RATIO) {
            $pct = round((($totalExpense / $avgPrior) - 1) * 100, 1);
            $alerts[] = [
                'type' => 'spending_above_average',
                'severity' => 'info',
                'message' => 'Você gastou mais que o normal em relação à média dos meses anteriores.',
                'meta' => [
                    'month' => $month,
                    'total_despesas' => round($totalExpense, 2),
                    'media_meses_anteriores' => round($avgPrior, 2),
                    'percentual_acima_media' => $pct,
                ],
            ];
        }

        $cards = CreditCard::query()->forUser($userId)->get();
        foreach ($cards as $card) {
            $bill = CreditCardBillingService::billPayload($card);
            $fatura = (float) ($bill['fatura_total'] ?? 0);
            $limite = (float) ($bill['credit_card']['limit'] ?? 0);
            if ($limite > 0 && $fatura > $limite * self::HIGH_BILL_LIMIT_RATIO) {
                $alerts[] = [
                    'type' => 'high_credit_card_bill',
                    'severity' => 'warning',
                    'message' => sprintf(
                        'Fatura alta no cartão "%s" (acima de %d%% do limite).',
                        $card->name,
                        (int) (self::HIGH_BILL_LIMIT_RATIO * 100)
                    ),
                    'meta' => [
                        'credit_card_id' => $card->id,
                        'fatura_total' => $fatura,
                        'limite' => $limite,
                    ],
                ];
            }
        }

        return $alerts;
    }

    private function averageTotalExpensePriorMonths(int $userId, string $yearMonth, int $count): float
    {
        $cursor = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth()->subMonth();
        $sum = 0.0;
        $n = 0;
        for ($i = 0; $i < $count; $i++) {
            $key = $cursor->format('Y-m');
            $row = Transaction::aggregateMonthStats($userId, $key, null);
            $total = (float) ($row->expense_cash ?? 0) + (float) ($row->expense_card ?? 0);
            $sum += $total;
            $n++;
            $cursor = $cursor->copy()->subMonth();
        }

        return $n > 0 ? $sum / $n : 0.0;
    }
}
