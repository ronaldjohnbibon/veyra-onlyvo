<?php

namespace App\Admin\AuditLogs\Http\Requests;

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
            'actor'       => ['nullable', 'string', 'max:255'],
            'entity_type' => ['nullable', 'string', 'max:80'],
            'entity_id'   => ['nullable', 'string', 'max:255'],
            'action'      => ['nullable', 'string', 'max:80'],
            'ip_address'  => ['nullable', 'string', 'max:45'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
            'page'        => ['nullable', 'integer', 'min:1'],
            'pageSize'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort'        => ['nullable', 'string', Rule::in(['occurred_at', 'actor', 'entity', 'action', 'ip_address'])],
            'direction'   => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
