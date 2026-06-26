<?php

namespace App\Admin\Leads\Http\Requests;

use App\Admin\Leads\Models\TemplateCtaSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminLeadIndexRequest extends FormRequest
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
            'tenant_id'   => ['nullable', 'uuid', Rule::exists('tenants', 'id')],
            'template_id' => ['nullable', 'uuid', Rule::exists('templates', 'id')],
            'cta_type'    => ['nullable', 'string', 'max:60'],
            'status'      => ['nullable', 'string', Rule::in(TemplateCtaSubmission::STATUSES)],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
            'sort'        => ['nullable', 'string', Rule::in(['created_at', 'status', 'cta_type', 'template', 'tenant'])],
            'direction'   => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page'        => ['nullable', 'integer', 'min:1'],
            'pageSize'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string'      => 'The lead search must be text.',
            'search.max'         => 'The lead search may not exceed :max characters.',
            'tenant_id.uuid'     => 'The tenant filter is invalid.',
            'tenant_id.exists'   => 'The selected tenant does not exist.',
            'template_id.uuid'   => 'The template filter is invalid.',
            'template_id.exists' => 'The selected template does not exist.',
            'cta_type.string'    => 'The CTA type filter must be text.',
            'cta_type.max'       => 'The CTA type filter may not exceed :max characters.',
            'status.string'      => 'The lead status filter must be text.',
            'status.in'          => 'Select a valid lead status.',
            'from.date'          => 'Enter a valid lead start date.',
            'to.date'            => 'Enter a valid lead end date.',
            'sort.string'        => 'The lead sort field must be text.',
            'sort.in'            => 'Select a valid lead sort field.',
            'direction.string'   => 'The sort direction must be text.',
            'direction.in'       => 'Select a valid sort direction.',
            'page.integer'       => 'The page must be a whole number.',
            'page.min'           => 'The page must be at least :min.',
            'pageSize.integer'   => 'The page size must be a whole number.',
            'pageSize.min'       => 'The page size must be at least :min.',
            'pageSize.max'       => 'The page size may not exceed :max.',
        ];
    }
}
