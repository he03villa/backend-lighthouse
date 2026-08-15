<?php

namespace App\Http\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_participants');
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->id();

        return [
            'participant_id' => ['required', Rule::exists('participants', 'id')->where('tenant_id', $tenantId)],
            'program_id' => ['required', Rule::exists('programs', 'id')->where('tenant_id', $tenantId)->where('is_published', true)],
        ];
    }
}
