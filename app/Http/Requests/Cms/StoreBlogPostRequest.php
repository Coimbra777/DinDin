<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $dateInput = $this->input('date', '');
        if (is_string($dateInput) && strlen($dateInput) >= 10) {
            $this->merge([
                'date' => substr($dateInput, 0, 10),
                'time' => strlen($dateInput) > 10 ? substr($dateInput, 11) : '00:00:00',
            ]);
        }

        $this->merge([
            'active' => $this->boolean('active') ? 1 : 0,
            'highlight' => $this->boolean('highlight') ? 1 : 0,
        ]);

        $raw = $this->input('description', '');
        $stripped = strip_tags(is_string($raw) ? $raw : '');
        $this->merge([
            'description' => $stripped !== '' ? $raw : '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'lead' => ['nullable', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'image' => ['required', 'image'],
            'user' => ['required', 'string'],
            'video' => ['nullable', 'string'],
            'blog_category_id' => ['required', 'string'],
            'active' => ['nullable', 'boolean'],
            'highlight' => ['nullable', 'boolean'],
            'date' => ['required', 'string'],
            'time' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image' => 'A imagem não pode ser carregada',
            'image.required' => 'É necessário preencher o campo de imagem',
            'name.required' => 'É necessário um nome para a notícia',
            'user.required' => 'É necessário preencher o nome do autor',
            'description.required' => 'É necessária uma descrição para a notícia',
            'name.max' => 'O número máximo de caracteres é de 150',
            'lead.max' => 'O número máximo de caracteres é de 200',
            'blog_category_id.required' => 'É necessário escolher uma categoria',
        ];
    }
}
