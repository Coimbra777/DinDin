<?php

declare(strict_types=1);

namespace App\Http\Requests\Goals;

use App\Models\Finance\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinanceGoalRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'current_amount' => ['nullable', 'numeric', 'min:0'],
            'deadline' => ['required', 'date'],
            'income_category_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_categories', 'id')->where(fn ($q) => $q->where('user_id', $userId)->where('type', Category::TYPE_INCOME)),
            ],
        ];
    }
}
