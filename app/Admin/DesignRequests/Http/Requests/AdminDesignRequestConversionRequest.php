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

    public function messages(): array
    {
        return [
            'summary.string'    => 'The conversion summary must be text.',
            'summary.max'       => 'The conversion summary may not exceed :max characters.',
            'changelog.string'  => 'The conversion changelog must be text.',
            'changelog.max'     => 'The conversion changelog may not exceed :max characters.',
            'target_key.string' => 'The conversion target key must be text.',
            'target_key.max'    => 'The conversion target key may not exceed :max characters.',
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
