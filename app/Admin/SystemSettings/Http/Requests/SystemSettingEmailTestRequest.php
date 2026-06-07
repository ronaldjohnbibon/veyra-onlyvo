<?php

namespace App\Admin\SystemSettings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SystemSettingEmailTestRequest extends FormRequest
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
            'recipient' => ['required', 'email', 'max:255'],
        ];
    }
}
