<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SwapSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_tenant');
    }

    public function rules(): array
    {
        return [
            'plan_slug' => ['required', 'string', Rule::exists('plans', 'slug')],
        ];
    }
}
