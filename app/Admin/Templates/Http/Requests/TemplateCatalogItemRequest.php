<?php

namespace App\Admin\Templates\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TemplateCatalogItemRequest extends FormRequest
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
        $routeParam = $this->route('templateCatalogItem');
        $itemId     = is_object($routeParam) ? $routeParam->id : $routeParam;

        return [
            'website_type_id' => [
                'required',
                'uuid',
                Rule::exists('website_types', 'id'),
            ],
            'key' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('template_catalog_items', 'key')
                    ->ignore($itemId)
                    ->where(fn ($query) => $query->where('website_type_id', $this->input('website_type_id'))),
            ],
            'name'            => ['required', 'string', 'max:150'],
            'description'     => ['nullable', 'string'],
            'preview_image'   => ['nullable', 'string'],
            'field_schema'    => ['nullable', 'array'],
            'default_content' => ['nullable', 'array'],
            'is_active'       => ['required', 'boolean'],
            'changelog'       => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function prepareForValidation(): void
    {
        foreach (['name', 'description', 'preview_image'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field)) ?: null]);
            }
        }

        if ($this->has('key')) {
            $this->merge(['key' => Str::slug((string) $this->input('key'))]);
        }

        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }
}
