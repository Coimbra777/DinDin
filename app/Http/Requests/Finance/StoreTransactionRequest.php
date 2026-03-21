<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Models\Finance\Transaction;
use App\Services\Finance\TransactionCategoryTypeGuard;
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
            'description' => 'nullable|string|max:5000',
            'installment_number' => ['nullable', 'integer', 'min:1', 'max:360'],
            'installment_of' => ['nullable', 'integer', 'min:2', 'max:360'],
            'credit_card_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_credit_cards', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
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

            $hasN = array_key_exists('installment_number', $data) && $data['installment_number'] !== null;
            $hasO = array_key_exists('installment_of', $data) && $data['installment_of'] !== null;
            if ($hasN xor $hasO) {
                $validator->errors()->add(
                    'installment_number',
                    'Informe parcela atual e total (ex.: 3 e 12) ou deixe os dois em branco.'
                );

                return;
            }
            if ($hasN && $hasO && (int) $data['installment_number'] >= (int) $data['installment_of']) {
                $validator->errors()->add(
                    'installment_number',
                    'A parcela atual deve ser menor que o número total de parcelas.'
                );

                return;
            }

            $hasCard = ! empty($data['credit_card_id']);
            if ($hasCard && ($data['type'] ?? '') !== Transaction::TYPE_EXPENSE) {
                $validator->errors()->add(
                    'credit_card_id',
                    'Cartão de crédito só pode ser usado em despesas.'
                );

                return;
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
     * Payload pronto para {@see \App\Services\Finance\TransactionApiService::create()} / {@see update()}.
     *
     * @return array<string, mixed>
     */
    public function toTransactionAttributes(): array
    {
        $data = $this->validated();
        $hasCard = ! empty($data['credit_card_id']);
        if ($hasCard) {
            $data['is_credit_card'] = true;
        } else {
            $data['credit_card_id'] = null;
            $data['is_credit_card'] = false;
        }

        return $data;
    }
}
