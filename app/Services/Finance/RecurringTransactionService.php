<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Gera linhas de despesa a partir de templates mensais (sem cron; uso explícito via API).
 */
final class RecurringTransactionService
{
    public function __construct(
        private readonly FinancialSummaryService $summaries,
        private readonly FinanceReadCache $readCache,
    ) {}

    /**
     * @return array{generated: list<array<string, mixed>>, skipped: int}
     */
    public function generateForMonth(int $userId, string $yearMonth): array
    {
        [$start] = $this->summaries->monthToDateRange($yearMonth);
        $target = Carbon::parse($start)->startOfMonth();
        $y = (int) $target->format('Y');
        $mNum = (int) $target->format('n');

        return DB::transaction(function () use ($userId, $yearMonth, $y, $mNum): array {
            $templates = Transaction::query()
                ->forUser($userId)
                ->where('type', Transaction::TYPE_EXPENSE)
                ->where('is_recurring', true)
                ->whereNull('parent_transaction_id')
                ->orderBy('id')
                ->get();

            $generated = [];
            $skipped = 0;

            foreach ($templates as $template) {
                $templateYm = $template->transaction_date->format('Y-m');
                if ($templateYm === $yearMonth) {
                    $skipped++;

                    continue;
                }

                if (TransactionDuplicateGuard::existsChildForYearMonth($userId, (int) $template->id, $y, $mNum)) {
                    $skipped++;

                    continue;
                }

                $day = $template->recurrence_day !== null
                    ? (int) $template->recurrence_day
                    : (int) $template->transaction_date->format('j');
                $transactionDate = $this->safeDateInMonth($day, $y, $mNum);
                $dueDate = $this->dueDateForMonth($template, $y, $mNum);

                $row = Transaction::create([
                    'user_id' => $userId,
                    'parent_transaction_id' => (int) $template->id,
                    'category_id' => $template->category_id,
                    'recurring_transaction_id' => null,
                    'title' => $template->title,
                    'amount' => $template->amount,
                    'type' => Transaction::TYPE_EXPENSE,
                    'transaction_date' => $transactionDate,
                    'payment_status' => Transaction::STATUS_PENDING,
                    'due_date' => $dueDate,
                    'description' => $template->description,
                    'installment_number' => null,
                    'installment_of' => null,
                    'is_recurring' => false,
                    'recurrence_day' => null,
                ]);

                $row->load(['category']);
                $generated[] = TransactionResource::toArray($row);
            }

            if ($generated !== []) {
                $this->readCache->bump($userId);
            }

            return [
                'generated' => $generated,
                'skipped' => $skipped,
            ];
        });
    }

    /**
     * Dia civil no mês (31 em fevereiro → último dia).
     */
    private function safeDateInMonth(int $day, int $year, int $month): string
    {
        $last = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $d = min(max(1, $day), $last);

        return sprintf('%04d-%02d-%02d', $year, $month, $d);
    }

    private function dueDateForMonth(Transaction $template, int $year, int $month): ?string
    {
        if ($template->due_date === null) {
            return null;
        }

        $d = (int) $template->due_date->format('j');

        return $this->safeDateInMonth($d, $year, $month);
    }
}
