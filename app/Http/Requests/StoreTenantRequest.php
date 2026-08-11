<?php

namespace App\Http\Requests;

use App\Enums\TenantType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tenants,slug'],
            'type' => ['nullable', Rule::enum(TenantType::class)],
            'config' => ['nullable', 'array'],
        ];
    }
}
