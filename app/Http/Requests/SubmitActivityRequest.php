<?php

namespace App\Http\Requests;

use App\Enums\EvidenceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('submit_evidence');
    }

    public function rules(): array
    {
        return [
            'enrollment_id' => ['required', 'exists:enrollments,id'],
            'evidence_type' => ['nullable', Rule::enum(EvidenceType::class)],
            'content' => ['nullable', 'string'],
            'evidence_file' => ['nullable', 'image', 'max:5120'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
