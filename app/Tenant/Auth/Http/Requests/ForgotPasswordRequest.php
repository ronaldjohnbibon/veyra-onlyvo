<?php

namespace App\Tenant\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => trim((string) $this->input('email')) ?: null,
            ]);
        }
    }
}
