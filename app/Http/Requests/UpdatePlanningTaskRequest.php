<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanningTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_planning');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
