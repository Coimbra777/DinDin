<?php

declare(strict_types=1);

namespace App\Services\Finance;

/**
 * Simula parcelamento simples (sem juros compostos; taxa opcional linear no total).
 */
final class CreditInstallmentSimulatorService
{
    /**
     * @return array{
     *   principal: float,
     *   installments: int,
     *   installment_value: float,
     *   total_repayment: float,
     *   interest_percent_total_applied: float,
     *   monthly_impact: array{parcela: float, observacao: string}
     * }
     */
    public function simulate(float $principal, int $installments, float $interestPercentTotal = 0.0): array
    {
        $installments = max(1, $installments);
        $interest = max(0.0, $interestPercentTotal);
        $total = round($principal * (1 + $interest / 100), 2);
        $parcela = round($total / $installments, 2);

        return [
            'principal' => round($principal, 2),
            'installments' => $installments,
            'installment_value' => $parcela,
            'total_repayment' => $total,
            'interest_percent_total_applied' => round($interest, 2),
            'monthly_impact' => [
                'parcela' => $parcela,
                'observacao' => sprintf(
                    'Impacto mensal aproximado: despesa fixa de R$ %s em %d vezes (total R$ %s).',
                    number_format($parcela, 2, ',', '.'),
                    $installments,
                    number_format($total, 2, ',', '.')
                ),
            ],
        ];
    }
}
