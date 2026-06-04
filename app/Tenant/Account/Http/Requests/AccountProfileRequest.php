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
}
