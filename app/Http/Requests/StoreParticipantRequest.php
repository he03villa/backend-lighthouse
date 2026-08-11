<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_participants');
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'avatar' => ['nullable', 'string', 'max:2048'],
            'metadata' => ['nullable', 'array'],
            'guardians' => ['nullable', 'array'],
            'guardians.*.name' => ['nullable', 'string', 'max:255'],
            'guardians.*.email' => ['required_with:guardians', 'email', 'max:255'],
            'guardians.*.relationship' => ['nullable', 'string', 'max:255'],
            'guardians.*.is_primary' => ['nullable', 'boolean'],
            'guardians.*.permissions' => ['nullable', 'array'],
        ];
    }
}
