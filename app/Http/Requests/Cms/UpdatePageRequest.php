<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /** Mesmo fluxo do legado: checkbox ausente => 0 */
        $this->merge([
            'active' => $this->boolean('active') ? 1 : 0,
        ]);
    }

    /**
     * Inclui `description` (estava no update mas não no Validator legado).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:250'],
            'description' => ['nullable', 'string'],
            'facebook' => ['nullable', 'string'],
            'whatsapp' => ['nullable', 'string'],
            'instagram' => ['nullable', 'string'],
            'item' => ['nullable', 'string'],
            'street' => ['nullable', 'string'],
            'CEP' => ['nullable', 'string'],
            'cnpj' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'number' => ['nullable', 'numeric'],
            'district' => ['nullable', 'string'],
            'video' => ['nullable', 'string'],
            /** Legado: numeric|string; nullable evita falha com campo vazio quando visível. */
            'phone' => ['nullable', 'string', 'numeric'],
            'image' => ['nullable', 'image'],
            'active' => ['required', 'integer', 'in:0,1'],
        ];
    }
}
