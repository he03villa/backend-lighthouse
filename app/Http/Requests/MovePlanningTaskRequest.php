<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovePlanningTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_planning');
    }

    public function rules(): array
    {
        return [
            'column_id' => ['nullable', 'uuid', 'exists:planning_columns,id'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
