<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\CreditCard;
use App\Models\Finance\FinanceGoal;

/**
 * Alertas derivados de transações, cartões e metas (sem persistência).
 *
 * @phpstan-type AlertItem array{
 *   type: string,
 *   severity: string,
 *   title: string,
 *   message: string,
 *   action_hint: string,
 *   meta?: array<string, mixed>
 * }
 */
final class FinanceAlertService
{
    private const HIGH_BILL_LIMIT_RATIO = 0.50;

    public function __construct(
        private readonly FinanceMonthMetrics $monthMetrics,
        private readonly FinanceGoalService $goals,
    ) {}

    /**
     * @return list<AlertItem>
     */
    public function forUser(int $userId, ?string $monthQuery = null): array
    {
        $snap = $this->monthMetrics->snapshot($userId, $monthQuery);
        $alerts = [];

        if ($snap['negative_balance']) {
            $gap = abs($snap['saldo_com_cartao']);
            $alerts[] = [
                'type' => 'negative_balance',
                'severity' => 'warning',
                'title' => 'Saldo no vermelho neste mês',
                'message' => sprintf(
                    'Suas despesas (incluindo o que já está no cartão) ultrapassam a receita em %s neste mês. Sem ajuste, o caixa tende a ficar mais apertado nos próximos meses.',
                    $this->brl($gap)
                ),
                'action_hint' => 'Revise despesas fixas, parcelas no cartão e o que ainda dá para cortar neste mês.',
                'meta' => [
                    'month' => $snap['month'],
                    'saldo_com_cartao' => $snap['saldo_com_cartao'],
                ],
            ];
        }

        if ($snap['spending_spike']) {
            $pct = $snap['spending_spike_percent'] ?? 0.0;
            $alerts[] = [
                'type' => 'spending_above_average',
                'severity' => 'info',
                'title' => 'Gastos acima do seu “normal” recente',
                'message' => sprintf(
                    'Você está gastando cerca de %s%% a mais que a média dos últimos três meses. Manter esse ritmo pode comprometer o saldo nas próximas faturas e no mês que vem.',
                    $this->fmtPct($pct)
                ),
                'action_hint' => 'Revise suas despesas recentes e as categorias que mais cresceram.',
                'meta' => [
                    'month' => $snap['month'],
                    'total_despesas' => $snap['total_expense'],
                    'media_meses_anteriores' => $snap['avg_prior_expense'],
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
                $ratioPct = (int) (self::HIGH_BILL_LIMIT_RATIO * 100);
                $alerts[] = [
                    'type' => 'high_credit_card_bill',
                    'severity' => 'warning',
                    'title' => sprintf('Fatura do cartão “%s” está alta', $card->name),
                    'message' => sprintf(
                        'A fatura atual (%s) passa de %d%% do limite (%s). Isso aumenta o risco de estourar o limite ou pagar juros se não planejar o pagamento.',
                        $this->brl($fatura),
                        $ratioPct,
                        $this->brl($limite)
                    ),
                    'action_hint' => 'Confira compras parceladas e antecipe parte da fatura, se possível.',
                    'meta' => [
                        'credit_card_id' => $card->id,
                        'fatura_total' => $fatura,
                        'limite' => $limite,
                    ],
                ];
            }
        }

        $today = now()->toDateString();
        $goalRows = FinanceGoal::forUser($userId)
            ->whereDate('deadline', '>=', $today)
            ->orderBy('deadline')
            ->orderBy('id')
            ->get();

        foreach ($goalRows as $goal) {
            $effective = $this->goals->effectiveCurrentAmount($goal);
            $shortfallInfo = $this->goals->linearPaceShortfall(
                $goal,
                $effective,
                null,
                FinanceGoalService::LINEAR_PACE_ALERT_MIN_ELAPSED_DAYS
            );
            if ($shortfallInfo === null) {
                continue;
            }

            $target = (float) $goal->target_amount;
            $insights = $this->goals->paceInsights($goal, $effective);

            $alerts[] = [
                'type' => 'goal_risk',
                'severity' => 'warning',
                'title' => 'Meta pode ficar aquém do prazo',
                'message' => sprintf(
                    'Você pode não atingir a meta “%s” de %s até o prazo. No ritmo atual de acúmulo desde o início, ainda faltariam cerca de %s no fim.',
                    $goal->title,
                    $this->brl($target),
                    $this->brl($shortfallInfo['shortfall'])
                ),
                'action_hint' => 'Considere aumentar aportes, revisar o valor alvo ou estender o prazo, se fizer sentido.',
                'meta' => [
                    'goal_id' => $goal->id,
                    'goal_title' => $goal->title,
                    'target_amount' => round($target, 2),
                    'current_amount_effective' => round($effective, 2),
                    'projected_total' => $shortfallInfo['projected_total'],
                    'shortfall' => $shortfallInfo['shortfall'],
                    'deadline' => $goal->deadline->format('Y-m-d'),
                    'days_remaining' => $insights['days_remaining'],
                ],
            ];
        }

        return $alerts;
    }

    private function brl(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    private function fmtPct(float $value): string
    {
        $s = number_format($value, 1, ',', '.');

        return preg_replace('/,0$/', '', $s) ?: '0';
    }
}
