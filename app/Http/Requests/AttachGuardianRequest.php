<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_participants');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required_without:email', 'nullable', 'exists:users,id'],
            'email' => ['required_without:user_id', 'nullable', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
        ];
    }
}
