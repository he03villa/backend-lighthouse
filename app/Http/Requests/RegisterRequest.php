<?php

namespace App\Http\Requests;

use App\Enums\TenantType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'tenant_name' => ['nullable', 'string', 'max:255'],
            'tenant_type' => ['nullable', Rule::enum(TenantType::class)],
        ];
    }
}
