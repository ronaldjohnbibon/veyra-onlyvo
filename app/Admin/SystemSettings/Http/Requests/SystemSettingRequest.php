<?php

namespace App\Admin\SystemSettings\Http\Requests;

use App\Shared\SystemSettings\Services\SystemSettingService;
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

        return [
            'key'   => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', Rule::in($keys), Rule::unique('system_settings', 'key')->ignore($this->route('systemSetting'))],
            'value' => ['present', 'nullable'],
        ];
    }
}
