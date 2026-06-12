<?php

namespace App\Admin\DesignRequests\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminDesignRequestLinkRequest extends FormRequest
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
            'linked_template_id' => ['nullable', 'uuid', Rule::exists('templates', 'id')],
            'linked_site_url'    => ['nullable', 'url', 'max:2000'],
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('linked_template_id')) {
            $this->merge(['linked_template_id' => trim((string) $this->input('linked_template_id')) ?: null]);
        }

        if ($this->has('linked_site_url')) {
            $this->merge(['linked_site_url' => trim((string) $this->input('linked_site_url')) ?: null]);
        }
    }
}
