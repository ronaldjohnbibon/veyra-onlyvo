<?php

namespace App\Tenant\Templates\Http\Requests;

use App\Tenant\Templates\Models\TemplateCtaSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateCtaSubmissionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(TemplateCtaSubmission::TENANT_EDITABLE_STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Select a submission status.',
            'status.string'   => 'The submission status must be text.',
            'status.in'       => 'Select a valid submission status.',
        ];
    }
}
