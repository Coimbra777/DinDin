<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TransactionApiService
{
    /**
     * Lista paginada (substitui o limite fixo de 200). Ordenação: data desc, id desc.
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|null>}
     */
    public function listForUserPaginated(int $userId, array $filters, int $perPage, int $page): array
    {
        $perPage = min(100, max(1, $perPage));
        $page = max(1, $page);

        $query = Transaction::forUser($userId)
            ->with(['category'])
            ->filter($filters)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Transaction $t) => TransactionResource::toArray($t))->values()
        );

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentForUser(int $userId, string $month, int $limit = 8): array
    {
        $filters = ['month' => $month];
        $items = Transaction::forUser($userId)
            ->with(['category'])
            ->filter($filters)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $items->map(fn (Transaction $t) => TransactionResource::toArray($t))->all();
    }

    /**
     * @param  array<string, mixed>  $transactionData
     * @return array<string, mixed>
     */
    public function create(int $userId, array $transactionData): array
    {
        return DB::transaction(function () use ($userId, $transactionData): array {
            $transactionData['user_id'] = $userId;
            $transactionData['recurring_transaction_id'] = null;
            $transactionData['parent_transaction_id'] = null;
            $t = Transaction::create($transactionData);
            $t->load(['category']);

            return TransactionResource::toArray($t);
        });
    }

    /**
     * @param  array<string, mixed>  $transactionData
     * @return array<string, mixed>
     */
    public function update(Transaction $transaction, array $transactionData): array
    {
        return DB::transaction(function () use ($transaction, $transactionData): array {
            unset(
                $transactionData['user_id'],
                $transactionData['parent_transaction_id'],
                $transactionData['recurring_transaction_id'],
            );
            $transaction->update($transactionData);
            $t = $transaction->fresh();
            $t->load(['category']);

            return TransactionResource::toArray($t);
        });
    }

    /**
     * Cópias nos meses seguintes (mesmo dia civil quando possível), com parent_transaction_id = transação origem.
     *
     * @return list<array<string, mixed>>
     */
    public function duplicateFollowingMonths(Transaction $source, int $months): array
    {
        if ($months < 1 || $months > 60) {
            throw new \InvalidArgumentException('months deve estar entre 1 e 60.');
        }

        return DB::transaction(function () use ($source, $months): array {
            $userId = (int) $source->user_id;
            $parentId = (int) $source->id;

            $datesToCreate = [];
            for ($i = 1; $i <= $months; $i++) {
                $datesToCreate[] = Carbon::parse($source->transaction_date)
                    ->startOfDay()
                    ->addMonths($i)
                    ->toDateString();
            }

            foreach ($datesToCreate as $ymd) {
                $d = Carbon::parse($ymd)->startOfDay();
                if ($this->hasDuplicateChildInMonth($userId, $parentId, $d)) {
                    throw ValidationException::withMessages([
                        'months' => [
                            'Já existe uma cópia desta transação no mês '.$d->format('m/Y').'. Reduza a quantidade de meses ou remova o duplicado.',
                        ],
                    ]);
                }
            }

            $ids = [];
            foreach ($datesToCreate as $ymd) {
                $row = Transaction::create([
                    'user_id' => $userId,
                    'parent_transaction_id' => $parentId,
                    'category_id' => $source->category_id,
                    'recurring_transaction_id' => null,
                    'title' => $source->title,
                    'amount' => $source->amount,
                    'type' => $source->type,
                    'transaction_date' => $ymd,
                    'description' => $source->description,
                    'installment_number' => null,
                    'installment_of' => null,
                ]);
                $ids[] = $row->id;
            }

            $fresh = Transaction::query()
                ->whereIn('id', $ids)
                ->with(['category'])
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            return $fresh->map(fn (Transaction $t) => TransactionResource::toArray($t))->values()->all();
        });
    }

    private function hasDuplicateChildInMonth(int $userId, int $parentId, Carbon $dateInMonth): bool
    {
        return Transaction::query()
            ->forUser($userId)
            ->where('parent_transaction_id', $parentId)
            ->whereYear('transaction_date', (int) $dateInMonth->format('Y'))
            ->whereMonth('transaction_date', (int) $dateInMonth->format('n'))
            ->exists();
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }
}
