<?php

namespace App\Tenant\AuditLogs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditLogIndexRequest extends FormRequest
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
            'category'    => ['nullable', 'string', Rule::in(['activity', 'auth', 'audit', 'error', 'notification', 'system', 'file_upload', 'permission', 'transaction'])],
            'action'      => ['nullable', 'string', 'max:120'],
            'severity'    => ['nullable', 'string', Rule::in(['info', 'warning', 'error', 'critical'])],
            'actor'       => ['nullable', 'string', 'max:255'],
            'entity_type' => ['nullable', 'string', 'max:80'],
            'entity_id'   => ['nullable', 'string', 'max:255'],
            'ip_address'  => ['nullable', 'string', 'max:45'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date', 'after_or_equal:date_from'],
            'page'        => ['nullable', 'integer', 'min:1'],
            'pageSize'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort'        => ['nullable', 'string', Rule::in(['occurred_at', 'category', 'severity', 'actor', 'entity', 'action', 'ip_address'])],
            'direction'   => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string'          => 'The audit log search must be text.',
            'search.max'             => 'The audit log search may not exceed :max characters.',
            'category.string'        => 'The audit category filter must be text.',
            'category.in'            => 'Select a valid audit category.',
            'action.string'          => 'The audit action filter must be text.',
            'action.max'             => 'The audit action filter may not exceed :max characters.',
            'severity.string'        => 'The audit severity filter must be text.',
            'severity.in'            => 'Select a valid audit severity.',
            'actor.string'           => 'The audit actor filter must be text.',
            'actor.max'              => 'The audit actor filter may not exceed :max characters.',
            'entity_type.string'     => 'The entity type filter must be text.',
            'entity_type.max'        => 'The entity type filter may not exceed :max characters.',
            'entity_id.string'       => 'The entity identifier filter must be text.',
            'entity_id.max'          => 'The entity identifier filter may not exceed :max characters.',
            'ip_address.string'      => 'The IP address filter must be text.',
            'ip_address.max'         => 'The IP address filter may not exceed :max characters.',
            'date_from.date'         => 'Enter a valid audit start date.',
            'date_to.date'           => 'Enter a valid audit end date.',
            'date_to.after_or_equal' => 'The audit end date must be on or after the start date.',
            'page.integer'           => 'The page must be a whole number.',
            'page.min'               => 'The page must be at least :min.',
            'pageSize.integer'       => 'The page size must be a whole number.',
            'pageSize.min'           => 'The page size must be at least :min.',
            'pageSize.max'           => 'The page size may not exceed :max.',
            'sort.string'            => 'The audit log sort field must be text.',
            'sort.in'                => 'Select a valid audit log sort field.',
            'direction.string'       => 'The sort direction must be text.',
            'direction.in'           => 'Select a valid sort direction.',
        ];
    }

    public function prepareForValidation(): void
    {
        $filters = $this->all();

        foreach (['search', 'category', 'action', 'severity', 'actor', 'entity_type', 'entity_id', 'ip_address', 'date_from', 'date_to', 'sort', 'direction'] as $key) {
            if (! array_key_exists($key, $filters)) {
                continue;
            }

            $value         = is_string($filters[$key]) ? trim($filters[$key]) : $filters[$key];
            $filters[$key] = $value === '' ? null : $value;
        }

        $this->merge($filters);
    }
}
