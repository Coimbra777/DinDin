<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'image' => ['required', 'image'],
            'description' => ['nullable', 'string'],
        ];
    }
}
