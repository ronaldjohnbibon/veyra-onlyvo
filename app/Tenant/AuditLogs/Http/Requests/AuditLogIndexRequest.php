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
