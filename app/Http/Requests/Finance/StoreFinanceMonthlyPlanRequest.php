<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Services\Finance\FinancialSummaryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFinanceMonthlyPlanRequest extends FormRequest
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
        $userId = (int) $this->user()->id;

        return [
            'year_month' => [
                'required',
                'regex:/^\d{4}-\d{2}$/',
                Rule::unique('finance_monthly_plans', 'year_month')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'planned_expense' => ['required', 'numeric', 'min:0'],
            'planned_saving' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ym = $this->input('year_month');
            if (! is_string($ym) || ! preg_match('/^\d{4}-\d{2}$/', $ym)) {
                return;
            }
            try {
                app(FinancialSummaryService::class)->monthToDateRange($ym);
            } catch (\InvalidArgumentException) {
                $validator->errors()->add('year_month', 'Mês inválido.');
            }
        });
    }
}
