<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class TransactionApiService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId, array $filters): array
    {
        $items = Transaction::forUser($userId)
            ->with(['category', 'creditCard'])
            ->filter($filters)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return $items->map(fn (Transaction $t) => TransactionResource::toArray($t))->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentForUser(int $userId, string $month, int $limit = 8): array
    {
        $filters = ['month' => $month];
        $items = Transaction::forUser($userId)
            ->with(['category', 'creditCard'])
            ->filter($filters)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $items->map(fn (Transaction $t) => TransactionResource::toArray($t))->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function create(int $userId, array $data): array
    {
        $data['user_id'] = $userId;
        $t = Transaction::create($data);
        $t->load(['category', 'creditCard']);

        return TransactionResource::toArray($t);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(Transaction $transaction, array $data): array
    {
        $transaction->update($data);
        $transaction->load(['category', 'creditCard']);

        return TransactionResource::toArray($transaction);
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validatePayload(Request $request): array
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'type' => ['required', Rule::in([Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE])],
            'category_id' => [
                'nullable',
                Rule::exists('finance_categories', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:5000',
            'installment_number' => ['nullable', 'integer', 'min:1', 'max:360'],
            'installment_of' => ['nullable', 'integer', 'min:2', 'max:360'],
            'credit_card_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_credit_cards', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
        ]);

        $hasN = array_key_exists('installment_number', $data) && $data['installment_number'] !== null;
        $hasO = array_key_exists('installment_of', $data) && $data['installment_of'] !== null;
        if ($hasN xor $hasO) {
            throw ValidationException::withMessages([
                'installment_number' => 'Informe parcela atual e total (ex.: 3 e 12) ou deixe os dois em branco.',
            ]);
        }
        if ($hasN && $hasO && (int) $data['installment_number'] >= (int) $data['installment_of']) {
            throw ValidationException::withMessages([
                'installment_number' => 'A parcela atual deve ser menor que o número total de parcelas.',
            ]);
        }

        $hasCard = ! empty($data['credit_card_id']);
        if ($hasCard && $data['type'] !== Transaction::TYPE_EXPENSE) {
            throw ValidationException::withMessages([
                'credit_card_id' => 'Cartão de crédito só pode ser usado em despesas.',
            ]);
        }
        if ($hasCard) {
            $data['is_credit_card'] = true;
        } else {
            $data['credit_card_id'] = null;
            $data['is_credit_card'] = false;
        }

        return $data;
    }
}
