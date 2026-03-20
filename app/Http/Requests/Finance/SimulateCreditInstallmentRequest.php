<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class SimulateCreditInstallmentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'installments' => ['required', 'integer', 'min:2', 'max:60'],
            'interest_percent_total' => ['nullable', 'numeric', 'min:0', 'max:500'],
        ];
    }
}
