<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiExplainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('use_ai_assistant') || $this->user()->can('view_own_progress');
    }

    public function rules(): array
    {
        return [
            'activity_id' => ['required', 'exists:activities,id'],
        ];
    }
}
