<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Alinha-se ao fluxo antigo: se, após strip_tags, não sobrar texto, gravar vazio;
     * caso contrário manter o valor original (pode incluir HTML do editor).
     */
    protected function prepareForValidation(): void
    {
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
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'whatsapp' => ['required', 'string', 'min:14', 'max:15'],
            /** Não existia no validador legado; mantém envio do formulário sem endurecer regra. */
            'phone' => ['nullable', 'string', 'max:50'],
            'facebook' => ['required', 'string', 'url'],
            'instagram' => ['required', 'string', 'url'],
            'linkedin' => ['required', 'string', 'url'],
            'form_email' => ['required', 'string', 'email'],
            'email' => ['required', 'email', 'string'],
            'keywords' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'É necessário colocar um nome',
            'title.max' => 'Máximo 100 caracteres',
            'description.required' => 'É necessário preencher a descrição',
            'whatsapp.min' => 'Número ínvalido',
            'whatsapp.max' => 'Número ínvalido',
            'whatsapp.required' => 'É necessário preencher o Whatsapp',
            'facebook.required' => 'É necessário preencher o Facebook',
            'instagram.required' => 'É necessário preencher o Instagram',
            'linkedin.required' => 'É necessário preencher o Linkedin',
            'form_email.required' => 'É necessário preencher o e-mail',
            'email.required' => 'É necessário preencher o e-mail',
            'email.email' => 'Insira um e-mail',
            'form_email.email' => 'Insira um e-mail',
            'keywords.required' => 'É necessário preencher pelo menos uma palavra-chave',
        ];
    }
}
