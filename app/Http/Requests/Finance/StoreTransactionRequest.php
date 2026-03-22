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
        return $this->validated();
    }
}
