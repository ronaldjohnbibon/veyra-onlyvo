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
            'preview_image'   => ['nullable', 'string', 'max:2048', 'not_regex:/^data:/i'],
            'field_schema'    => ['nullable', 'array'],
            'default_content' => ['nullable', 'array'],
            'is_active'       => ['required', 'boolean'],
            'changelog'       => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'website_type_id.required' => 'Select a website type.',
            'website_type_id.uuid'     => 'The website type identifier is invalid.',
            'website_type_id.exists'   => 'The selected website type does not exist.',
            'key.required'             => 'Enter the template catalog key.',
            'key.string'               => 'The template catalog key must be text.',
            'key.max'                  => 'The template catalog key may not exceed :max characters.',
            'key.regex'                => 'The template catalog key may contain only lowercase letters, numbers, and single hyphens.',
            'key.unique'               => 'This template catalog key is already in use for the selected website type.',
            'name.required'            => 'Enter the template catalog name.',
            'name.string'              => 'The template catalog name must be text.',
            'name.max'                 => 'The template catalog name may not exceed :max characters.',
            'description.string'       => 'The template catalog description must be text.',
            'preview_image.string'     => 'The preview image path must be text.',
            'preview_image.max'        => 'The preview image path may not exceed :max characters.',
            'preview_image.not_regex'  => 'Upload the preview image instead of using embedded image data.',
            'field_schema.array'       => 'The field schema must be a valid array.',
            'default_content.array'    => 'The default content must be a valid object.',
            'is_active.required'       => 'Choose whether the template catalog item is active.',
            'is_active.boolean'        => 'The active setting must be true or false.',
            'changelog.string'         => 'The changelog must be text.',
            'changelog.max'            => 'The changelog may not exceed :max characters.',
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
