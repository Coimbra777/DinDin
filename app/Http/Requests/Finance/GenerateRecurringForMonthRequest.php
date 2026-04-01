<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Services\Finance\FinancialSummaryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GenerateRecurringForMonthRequest extends FormRequest
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
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.required' => 'Informe o mês (YYYY-MM).',
            'month.regex' => 'Use o formato YYYY-MM.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $m = (string) ($validator->getData()['month'] ?? '');
            if ($m === '') {
                return;
            }
            try {
                app(FinancialSummaryService::class)->monthToDateRange($m);
            } catch (\InvalidArgumentException) {
                $validator->errors()->add('month', 'Mês inválido.');
            }
        });
    }

    public function yearMonth(): string
    {
        return (string) $this->validated()['month'];
    }
}
