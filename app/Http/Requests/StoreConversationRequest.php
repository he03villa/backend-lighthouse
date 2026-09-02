<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_conversations');
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required', 'exists:users,id'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
