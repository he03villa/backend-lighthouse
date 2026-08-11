<?php

namespace App\Http\Requests;

use App\Enums\ActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_programs');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'age_group' => ['nullable', 'string', 'max:255'],
            'duration_weeks' => ['nullable', 'integer', 'min:1', 'max:260'],
            'is_published' => ['nullable', 'boolean'],
            'thumbnail' => ['nullable', 'string', 'max:2048'],
            'config' => ['nullable', 'array'],
            'modules' => ['nullable', 'array'],
            'modules.*.name' => ['required', 'string', 'max:255'],
            'modules.*.description' => ['nullable', 'string'],
            'modules.*.order' => ['nullable', 'integer', 'min:0'],
            'modules.*.activities' => ['nullable', 'array'],
            'modules.*.activities.*.name' => ['required', 'string', 'max:255'],
            'modules.*.activities.*.description' => ['nullable', 'string'],
            'modules.*.activities.*.type' => ['nullable', Rule::enum(ActivityType::class)],
            'modules.*.activities.*.order' => ['nullable', 'integer', 'min:0'],
            'modules.*.activities.*.config' => ['nullable', 'array'],
        ];
    }
}
