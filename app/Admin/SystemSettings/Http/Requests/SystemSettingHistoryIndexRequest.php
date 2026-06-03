<?php

namespace App\Admin\SystemSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemSettingHistoryIndexRequest extends FormRequest
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
            'setting_key' => ['nullable', 'string', 'max:150'],
            'action'      => ['nullable', 'string', Rule::in(['created', 'updated', 'deleted'])],
            'search'      => ['nullable', 'string', 'max:255'],
            'sort'        => ['nullable', 'string', Rule::in(['setting_key', 'action', 'changed_by', 'changed_at'])],
            'direction'   => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page'        => ['nullable', 'integer', 'min:1'],
            'pageSize'    => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
