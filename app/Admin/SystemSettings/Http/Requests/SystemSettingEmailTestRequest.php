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

    public function messages(): array
    {
        return [
            'recipient.required' => 'Enter a recipient for the test email.',
            'recipient.email'    => 'Enter a valid test email recipient.',
            'recipient.max'      => 'The test email recipient may not exceed :max characters.',
        ];
    }
}
