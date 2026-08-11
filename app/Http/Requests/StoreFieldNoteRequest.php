<?php

namespace App\Http\Requests;

use App\Enums\FieldNoteVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFieldNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('write_field_notes');
    }

    public function rules(): array
    {
        return [
            'participant_id' => ['required', 'exists:participants,id'],
            'activity_submission_id' => ['nullable', 'exists:activity_submissions,id'],
            'session_date' => ['nullable', 'date'],
            'content' => ['required', 'string'],
            'visibility' => ['nullable', Rule::enum(FieldNoteVisibility::class)],
        ];
    }
}
