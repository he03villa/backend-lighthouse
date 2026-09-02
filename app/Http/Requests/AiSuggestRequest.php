<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiSuggestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('use_ai_assistant');
    }

    public function rules(): array
    {
        return [
            'activity_id' => ['required', 'exists:activities,id'],
        ];
    }
}
