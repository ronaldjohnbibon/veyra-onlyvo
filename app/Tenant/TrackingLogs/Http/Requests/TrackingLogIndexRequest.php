<?php

namespace App\Tenant\TrackingLogs\Http\Requests;

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

    public function messages(): array
    {
        return [
            'from.date'                => 'Enter a valid tracking start date.',
            'to.date'                  => 'Enter a valid tracking end date.',
            'event_type.string'        => 'The event type filter must be text.',
            'event_type.in'            => 'Select a valid tracking event type.',
            'template_id.uuid'         => 'The template filter is invalid.',
            'conversion_status.string' => 'The conversion status filter must be text.',
            'conversion_status.max'    => 'The conversion status filter may not exceed :max characters.',
            'search.string'            => 'The tracking log search must be text.',
            'search.max'               => 'The tracking log search may not exceed :max characters.',
            'sort.string'              => 'The tracking log sort field must be text.',
            'sort.in'                  => 'Select a valid tracking log sort field.',
            'direction.string'         => 'The sort direction must be text.',
            'direction.in'             => 'Select a valid sort direction.',
            'page.integer'             => 'The page must be a whole number.',
            'page.min'                 => 'The page must be at least :min.',
            'pageSize.integer'         => 'The page size must be a whole number.',
            'pageSize.min'             => 'The page size must be at least :min.',
            'pageSize.max'             => 'The page size may not exceed :max.',
        ];
    }
}
