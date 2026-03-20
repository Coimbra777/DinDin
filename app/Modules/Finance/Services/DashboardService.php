<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Transaction;

final class DashboardService
{
    /**
     * Painel do mês: saldos, totais e últimas transações (uma agregação + uma lista).
     *
     * @return array<string, mixed>
     */
    public function buildPayload(int $userId, ?string $monthQuery): array
    {
        $month = Transaction::normalizeMonth($monthQuery);
        [$start, $end] = Transaction::monthToDateRange($month);

        $row = Transaction::aggregateMonthStats($userId, $month, null);
        $receitasMes = (float) ($row->income_total ?? 0);
        $despesasCaixa = (float) ($row->expense_cash ?? 0);
        $despesasCartao = (float) ($row->expense_card ?? 0);
        $saldoReal = $receitasMes - $despesasCaixa;
        $saldoComCartao = $receitasMes - $despesasCaixa - $despesasCartao;
        $totalTransacoes = (int) ($row->tx_count ?? 0);

        $ultimas = Transaction::forUser($userId)
            ->with(['category:id,name,color', 'creditCard:id,name'])
            ->whereBetween('transaction_date', [$start, $end])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return [
            'month' => $month,
            'saldo_real' => round($saldoReal, 2),
            'saldo_atual' => round($saldoReal, 2),
            'saldo_com_cartao' => round($saldoComCartao, 2),
            'receitas_mes' => round($receitasMes, 2),
            'despesas_mes' => round($despesasCaixa, 2),
            'despesas_caixa_mes' => round($despesasCaixa, 2),
            'despesas_cartao_mes' => round($despesasCartao, 2),
            'total_transacoes' => $totalTransacoes,
            'ultimas_transacoes' => $ultimas->map(fn (Transaction $t) => TransactionResource::toArray($t))->all(),
        ];
    }
}
