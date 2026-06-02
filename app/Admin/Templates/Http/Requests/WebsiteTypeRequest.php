<?php

namespace App\Admin\Templates\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebsiteTypeRequest extends FormRequest
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
        $routeParam    = $this->route('websiteType');
        $websiteTypeId = is_object($routeParam) ? $routeParam->id : $routeParam;

        return [
            'name'        => ['required', 'string', 'max:150'],
            'slug'        => ['required', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('website_types', 'slug')->ignore($websiteTypeId)],
            'description' => ['nullable', 'string'],
            'is_active'   => ['required', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        foreach (['name', 'description'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field)) ?: null]);
            }
        }

        $slugSource = (string) $this->input('slug', $this->input('name', 'website-type'));

        $this->merge([
            'slug'      => Str::slug($slugSource),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
