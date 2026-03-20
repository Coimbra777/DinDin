<?php

namespace App\Http\Requests;

use App\Support\WhatsappNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('whatsapp')) {
            $this->merge([
                'whatsapp' => WhatsappNormalizer::normalize((string) $this->input('whatsapp')),
            ]);
        }
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'regex:/^[a-zA-Z]+(?:\s[a-zA-Z]+)+$/'],
            'email' => ['required', 'email', 'unique:jwt_users', 'string', 'regex:/^(([^<>()\\.,;:ç~\s@"]+(\.[^<>()\\.,;:ç~\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,24}))$/'],
            'whatsapp' => ['required', 'string', 'regex:/^\d{10,15}$/'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages()
    {

        return [
            'required' => 'Este campo é de preenchimento obrigatório.',
            'date' => 'Data inválida.',
            'unique' => 'Já há uma conta cadastrada para este E-mail.',
            'email.regex' => 'E-mail inválido.',
            'name.regex' => 'É necessário inserir pelo menos um sobrenome.',
            'password.min' => 'A senha deve conter no mínimo 8 caracteres, com letras e números.',
            'whatsapp.required' => 'O WhatsApp é obrigatório.',
            'whatsapp.regex' => 'Informe um número de WhatsApp válido (com DDD).',
        ];
    }
}
