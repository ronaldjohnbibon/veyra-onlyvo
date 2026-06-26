<?php

namespace App\Admin\SystemSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SystemSettingMaintenancePreviewRequest extends FormRequest
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
            'path'     => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'path.string'    => 'The maintenance preview path must be text.',
            'path.max'       => 'The maintenance preview path may not exceed :max characters.',
            'settings.array' => 'Maintenance preview settings must be provided as a valid settings object.',
        ];
    }
}
