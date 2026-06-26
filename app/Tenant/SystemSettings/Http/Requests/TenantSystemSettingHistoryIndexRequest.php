<?php

namespace App\Tenant\SystemSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantSystemSettingHistoryIndexRequest extends FormRequest
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
            'action'      => ['nullable', 'string', Rule::in(['created', 'updated'])],
            'search'      => ['nullable', 'string', 'max:255'],
            'sort'        => ['nullable', 'string', Rule::in(['setting_key', 'action', 'changed_by', 'changed_at'])],
            'direction'   => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page'        => ['nullable', 'integer', 'min:1'],
            'pageSize'    => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'setting_key.string' => 'The setting key filter must be text.',
            'setting_key.max'    => 'The setting key filter may not exceed :max characters.',
            'action.string'      => 'The history action filter must be text.',
            'action.in'          => 'Select a valid setting history action.',
            'search.string'      => 'The setting history search must be text.',
            'search.max'         => 'The setting history search may not exceed :max characters.',
            'sort.string'        => 'The setting history sort field must be text.',
            'sort.in'            => 'Select a valid setting history sort field.',
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
