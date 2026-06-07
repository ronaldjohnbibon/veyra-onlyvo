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
}
