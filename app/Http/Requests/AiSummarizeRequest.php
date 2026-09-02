<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiSummarizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('use_ai_assistant');
    }

    public function rules(): array
    {
        return [
            'participant_id' => ['required', 'exists:participants,id'],
        ];
    }
}
