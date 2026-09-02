<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiGenerateDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('use_ai_assistant');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in('goal', 'activity')],
            'context' => ['sometimes', 'array'],
        ];
    }
}
