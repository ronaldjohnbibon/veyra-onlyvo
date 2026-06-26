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

    public function messages(): array
    {
        return [
            'search.string'    => 'The submission search must be text.',
            'search.max'       => 'The submission search may not exceed :max characters.',
            'status.string'    => 'The submission status filter must be text.',
            'status.in'        => 'Select a valid submission status.',
            'template_id.uuid' => 'The template filter is invalid.',
            'cta_type.string'  => 'The CTA type filter must be text.',
            'cta_type.max'     => 'The CTA type filter may not exceed :max characters.',
            'from.date'        => 'Enter a valid submission start date.',
            'to.date'          => 'Enter a valid submission end date.',
            'sort.string'      => 'The submission sort field must be text.',
            'sort.in'          => 'Select a valid submission sort field.',
            'direction.string' => 'The sort direction must be text.',
            'direction.in'     => 'Select a valid sort direction.',
            'page.integer'     => 'The page must be a whole number.',
            'page.min'         => 'The page must be at least :min.',
            'pageSize.integer' => 'The page size must be a whole number.',
            'pageSize.min'     => 'The page size must be at least :min.',
            'pageSize.max'     => 'The page size may not exceed :max.',
        ];
    }
}
