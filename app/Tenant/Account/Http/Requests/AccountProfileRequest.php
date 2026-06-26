<?php

namespace App\Tenant\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name'       => trim((string) $this->input('name', '')),
            'first_name' => $this->filled('first_name') ? trim((string) $this->input('first_name')) : null,
            'last_name'  => $this->filled('last_name') ? trim((string) $this->input('last_name')) : null,
            'email'      => trim((string) $this->input('email', '')),
            'phone'      => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->getAuthIdentifier();

        return [
            'name'       => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone'      => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'Enter your name.',
            'name.string'       => 'Your name must be text.',
            'name.max'          => 'Your name may not exceed :max characters.',
            'first_name.string' => 'Your first name must be text.',
            'first_name.max'    => 'Your first name may not exceed :max characters.',
            'last_name.string'  => 'Your last name must be text.',
            'last_name.max'     => 'Your last name may not exceed :max characters.',
            'email.required'    => 'Enter your email address.',
            'email.email'       => 'Enter a valid email address.',
            'email.max'         => 'Your email address may not exceed :max characters.',
            'email.unique'      => 'This email address is already in use.',
            'phone.string'      => 'Your phone number must be text.',
            'phone.max'         => 'Your phone number may not exceed :max characters.',
        ];
    }
}
