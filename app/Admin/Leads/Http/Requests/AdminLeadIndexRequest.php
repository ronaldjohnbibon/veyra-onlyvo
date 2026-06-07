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
}
