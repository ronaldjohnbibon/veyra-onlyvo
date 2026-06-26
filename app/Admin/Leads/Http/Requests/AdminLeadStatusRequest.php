<?php

namespace App\Admin\Leads\Http\Requests;

use App\Admin\Leads\Models\TemplateCtaSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminLeadStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(TemplateCtaSubmission::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Select a lead status.',
            'status.in'       => 'Select a valid lead status.',
        ];
    }
}
