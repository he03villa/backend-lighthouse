<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_participants');
    }

    public function rules(): array
    {
        return [
            'participant_id' => ['required', 'exists:participants,id'],
            'program_id' => ['required', 'exists:programs,id'],
        ];
    }
}
