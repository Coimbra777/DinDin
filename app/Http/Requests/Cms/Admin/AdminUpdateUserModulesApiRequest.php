<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateUserModulesApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'is_admin' => ['required', 'boolean'],
            'modules' => ['present', 'array'],
            'modules.*' => ['string', 'distinct', 'exists:saas_modules,slug'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_admin' => $this->boolean('is_admin'),
        ]);
    }
}
