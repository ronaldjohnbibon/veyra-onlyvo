<?php

namespace App\Modules\TrackingLogs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrackingLogIndexRequest extends FormRequest
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
            'from'              => ['nullable', 'date'],
            'to'                => ['nullable', 'date'],
            'event_type'        => ['nullable', 'string', Rule::in(['website_visit', 'cta_view', 'cta_click', 'form_submission', 'conversion'])],
            'template_id'       => ['nullable', 'uuid'],
            'conversion_status' => ['nullable', 'string', 'max:30'],
            'search'            => ['nullable', 'string', 'max:255'],
            'sort'              => ['nullable', 'string', Rule::in(['created_at', 'event_type', 'event_name', 'tenant', 'template'])],
            'direction'         => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page'              => ['nullable', 'integer', 'min:1'],
            'pageSize'          => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
