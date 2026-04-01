<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Models\Finance\Transaction;
use App\Services\Finance\TransactionCategoryTypeGuard;
use App\Services\Finance\TransactionExpenseRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Selecione uma categoria.',
            'category_id.exists' => 'A categoria selecionada não é válida.',
            'amount.required' => 'Informe o valor.',
            'amount.min' => 'O valor deve ser pelo menos :min.',
            'type.required' => 'Selecione o tipo da transação (receita ou despesa).',
            'type.in' => 'O tipo da transação deve ser receita ou despesa.',
            'title.required' => 'Informe um título.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'type' => ['required', Rule::in([Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE])],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('finance_categories', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'transaction_date' => 'required|date',
            'payment_status' => ['sometimes', 'nullable', Rule::in([Transaction::STATUS_PENDING, Transaction::STATUS_PAID])],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'is_recurring' => ['sometimes', 'boolean'],
            'recurrence_day' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'description' => 'nullable|string|max:5000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $data = $validator->getData();
            $userId = (int) $this->user()->id;

            if (($data['type'] ?? '') === Transaction::TYPE_INCOME) {
                if (! empty($data['is_recurring']) && filter_var($data['is_recurring'], FILTER_VALIDATE_BOOLEAN)) {
                    $validator->errors()->add('is_recurring', 'Recorrência mensal só é permitida para despesas.');
                }
                if (isset($data['recurrence_day']) && $data['recurrence_day'] !== null && $data['recurrence_day'] !== '') {
                    $validator->errors()->add('recurrence_day', 'Dia de recorrência só se aplica a despesas.');
                }
            }

            $due = $data['due_date'] ?? null;
            $txDate = $data['transaction_date'] ?? null;
            if ($due !== null && $due !== '' && $txDate !== null && $txDate !== '') {
                if (strtotime((string) $due) < strtotime((string) $txDate)) {
                    $validator->errors()->add('due_date', 'A data de vencimento não pode ser anterior à data da transação.');
                }
            }

            try {
                TransactionCategoryTypeGuard::assertCompatible(
                    $userId,
                    isset($data['category_id']) ? (int) $data['category_id'] : null,
                    (string) $data['type']
                );
            } catch (ValidationException $e) {
                foreach ($e->errors() as $key => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($key, $message);
                    }
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function toTransactionAttributes(): array
    {
        return TransactionExpenseRules::normalizeForPersistence($this->validated());
    }
}
