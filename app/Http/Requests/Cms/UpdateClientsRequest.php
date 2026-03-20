<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

use App\Helpers\CmsHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => CmsHelper::CheckboxCheck($this->has('active')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'active' => ['nullable', 'boolean'],
        ];
    }
}
