<?php

namespace App\Http\Requests;

use App\Enums\FieldNoteVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFieldNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('write_field_notes');
    }

    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'string'],
            'visibility' => ['sometimes', Rule::enum(FieldNoteVisibility::class)],
            'session_date' => ['sometimes', 'date'],
        ];
    }
}
