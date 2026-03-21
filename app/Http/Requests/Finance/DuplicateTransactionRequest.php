<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class DuplicateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'months' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'months.required' => 'Informe quantos meses duplicar.',
            'months.min' => 'Use pelo menos 1 mês.',
            'months.max' => 'No máximo 60 meses por vez.',
        ];
    }

    public function months(): int
    {
        return (int) $this->validated()['months'];
    }
}
