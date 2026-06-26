<?php

namespace App\Admin\SystemSettings\Http\Requests;

use App\Admin\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemSettingRequest extends FormRequest
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
        $keys = array_keys(app(SystemSettingService::class)->definitions());

        if (! $this->isMethod('post')) {
            return [
                'key'   => ['sometimes', 'string', Rule::in($keys)],
                'value' => ['present', 'nullable'],
            ];
        }

        return [
            'key'   => ['required', 'string', Rule::in($keys), Rule::unique('system_settings', 'key')],
            'value' => ['present', 'nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required'  => 'Select a system setting.',
            'key.string'    => 'The system setting key must be text.',
            'key.in'        => 'Select a valid system setting.',
            'key.unique'    => 'This system setting already exists.',
            'value.present' => 'Include a value for the system setting.',
        ];
    }
}
