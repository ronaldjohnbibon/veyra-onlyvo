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

    public function messages(): array
    {
        return [
            'name.required'      => 'Enter the website type name.',
            'name.string'        => 'The website type name must be text.',
            'name.max'           => 'The website type name may not exceed :max characters.',
            'slug.required'      => 'Enter the website type slug.',
            'slug.string'        => 'The website type slug must be text.',
            'slug.max'           => 'The website type slug may not exceed :max characters.',
            'slug.regex'         => 'The website type slug may contain only lowercase letters, numbers, and single hyphens.',
            'slug.unique'        => 'This website type slug is already in use.',
            'description.string' => 'The website type description must be text.',
            'is_active.required' => 'Choose whether the website type is active.',
            'is_active.boolean'  => 'The active setting must be true or false.',
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

        $payload = ['slug' => Str::slug($slugSource)];

        if ($this->has('is_active')) {
            $payload['is_active'] = $this->boolean('is_active');
        }

        $this->merge($payload);
    }
}
