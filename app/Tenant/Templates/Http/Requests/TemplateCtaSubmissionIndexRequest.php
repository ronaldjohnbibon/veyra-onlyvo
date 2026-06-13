<?php

namespace App\Tenant\Templates\Http\Requests;

use App\Tenant\Templates\Models\TemplateCtaSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateCtaSubmissionIndexRequest extends FormRequest
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
            'search'      => ['nullable', 'string', 'max:255'],
            'status'      => ['nullable', 'string', Rule::in(TemplateCtaSubmission::STATUSES)],
            'template_id' => ['nullable', 'uuid'],
            'cta_type'    => ['nullable', 'string', 'max:60'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
            'sort'        => ['nullable', 'string', Rule::in(['created_at', 'status', 'cta_type', 'template'])],
            'direction'   => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page'        => ['nullable', 'integer', 'min:1'],
            'pageSize'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
