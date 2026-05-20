<?php

namespace App\Modules\Templates\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateSectionDesignRequest extends FormRequest
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
        $routeParam = $this->route('template_section_design');
        $designId   = is_object($routeParam) ? $routeParam->id : $routeParam;

        return [
            'section_type'  => ['required', 'string', 'max:40'],
            'section_label' => ['nullable', 'string', 'max:100'],
            'name'          => ['required', 'string', 'max:255'],
            'design_key'    => [
                'required',
                'string',
                'max:80',
                Rule::unique('template_section_designs', 'design_key')
                    ->ignore($designId)
                    ->where(fn ($query) => $query->where('section_type', $this->input('section_type'))),
            ],
            'preview_image'        => ['required', 'string'],
            'fields_json'          => ['nullable', 'array'],
            'default_content_json' => ['nullable', 'array'],
            'default_enabled'      => ['required', 'boolean'],
            'default_sort_order'   => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active'            => ['required', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        foreach (['section_type', 'section_label', 'name', 'design_key', 'preview_image'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }
}
