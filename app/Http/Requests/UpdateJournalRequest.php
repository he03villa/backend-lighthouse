<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('write_journal');
    }

    public function rules(): array
    {
        return [
            'participant_id' => ['nullable', 'uuid', 'exists:participants,id'],
            'entry_date' => ['nullable', 'date'],
            'content' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['nullable', 'string', 'in:private,shared_family,shared_participant,public'],
        ];
    }
}
