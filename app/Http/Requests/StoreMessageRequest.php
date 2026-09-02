<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('send_messages');
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            'media' => ['nullable', 'array'],
            'media.*.url' => ['required_with:media', 'url'],
            'media.*.type' => ['required_with:media', 'string'],
            'media.*.name' => ['required_with:media', 'string'],
        ];
    }
}
