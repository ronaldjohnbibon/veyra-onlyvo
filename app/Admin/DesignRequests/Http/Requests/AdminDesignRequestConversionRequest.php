<?php

namespace App\Admin\DesignRequests\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminDesignRequestConversionRequest extends FormRequest
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
            'summary'    => ['nullable', 'string', 'max:1000'],
            'changelog'  => ['nullable', 'string', 'max:1000'],
            'target_key' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function prepareForValidation(): void
    {
        foreach (['summary', 'changelog', 'target_key'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field)) ?: null]);
            }
        }
    }
}
