<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_admin' => ['sometimes', 'boolean'],
            'saas_module_ids' => ['nullable', 'array'],
            'saas_module_ids.*' => ['integer', 'exists:saas_modules,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_admin' => $this->boolean('is_admin'),
        ]);
    }
}
