<?php

namespace App\Tenant\Templates\Http\Requests;

use App\Tenant\Templates\Models\Template;
use App\Tenant\Templates\Services\TemplateCatalogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TemplateRequest extends FormRequest
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
        $routeParam   = $this->route('template');
        $templateId   = is_object($routeParam) ? $routeParam->id : $routeParam;
        $tenantId     = $this->input('tenant_id');
        $templateKeys = app(TemplateCatalogService::class)->keys($this->input('website_type_id'));

        return [
            'tenant_id'       => ['nullable', 'uuid', Rule::exists('tenants', 'id')],
            'website_type_id' => [
                'required',
                'uuid',
                Rule::exists('website_types', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('templates', 'name')
                    ->ignore($templateId)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'slug' => [
                'required',
                'string',
                'lowercase',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('templates', 'slug')
                    ->ignore($templateId)
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'template_key'           => ['required', 'string', 'max:80', Rule::in($templateKeys)],
            'business_name'          => ['required', 'string', 'max:150'],
            'contact_info'           => ['required', 'array'],
            'contact_info.email'     => ['nullable', 'string', 'max:150'],
            'contact_info.phone'     => ['nullable', 'string', 'max:50'],
            'contact_info.address'   => ['nullable', 'string', 'max:255'],
            'social_links'           => ['required', 'array'],
            'social_links.website'   => ['nullable', 'string', 'max:2048'],
            'social_links.linkedin'  => ['nullable', 'string', 'max:2048'],
            'social_links.instagram' => ['nullable', 'string', 'max:2048'],
            'social_links.facebook'  => ['nullable', 'string', 'max:2048'],
            'content'                => ['nullable', 'array'],
            'font_family'            => ['required', 'string', 'max:100'],
            'primary_color'          => ['required', 'string', 'max:20', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'secondary_color'        => ['required', 'string', 'max:20', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'background_color'       => ['required', 'string', 'max:20', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'text_color'             => ['required', 'string', 'max:20', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'status'                 => ['required', Rule::in(['draft', 'published'])],
            'is_default'             => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.uuid'                => 'The tenant identifier is invalid.',
            'tenant_id.exists'              => 'The selected tenant does not exist.',
            'website_type_id.required'      => 'Select a website type.',
            'website_type_id.uuid'          => 'The website type identifier is invalid.',
            'website_type_id.exists'        => 'Select an active website type.',
            'name.required'                 => 'Enter the template name.',
            'name.string'                   => 'The template name must be text.',
            'name.max'                      => 'The template name may not exceed :max characters.',
            'name.unique'                   => 'A template with this name already exists in this workspace.',
            'slug.required'                 => 'Enter the template slug.',
            'slug.string'                   => 'The template slug must be text.',
            'slug.lowercase'                => 'The template slug must use lowercase letters.',
            'slug.max'                      => 'The template slug may not exceed :max characters.',
            'slug.regex'                    => 'The template slug may contain only lowercase letters, numbers, and single hyphens.',
            'slug.unique'                   => 'A template with this slug already exists in this workspace.',
            'template_key.required'         => 'Select a template design.',
            'template_key.string'           => 'The template design key must be text.',
            'template_key.max'              => 'The template design key may not exceed :max characters.',
            'template_key.in'               => 'Select a template design available for this website type.',
            'business_name.required'        => 'Enter the business name.',
            'business_name.string'          => 'The business name must be text.',
            'business_name.max'             => 'The business name may not exceed :max characters.',
            'contact_info.required'         => 'Provide the template contact information.',
            'contact_info.array'            => 'The template contact information must be a valid object.',
            'contact_info.email.string'     => 'The contact email must be text.',
            'contact_info.email.max'        => 'The contact email may not exceed :max characters.',
            'contact_info.phone.string'     => 'The contact phone number must be text.',
            'contact_info.phone.max'        => 'The contact phone number may not exceed :max characters.',
            'contact_info.address.string'   => 'The contact address must be text.',
            'contact_info.address.max'      => 'The contact address may not exceed :max characters.',
            'social_links.required'         => 'Provide the template social links.',
            'social_links.array'            => 'The template social links must be a valid object.',
            'social_links.website.string'   => 'The website link must be text.',
            'social_links.website.max'      => 'The website link may not exceed :max characters.',
            'social_links.linkedin.string'  => 'The LinkedIn link must be text.',
            'social_links.linkedin.max'     => 'The LinkedIn link may not exceed :max characters.',
            'social_links.instagram.string' => 'The Instagram link must be text.',
            'social_links.instagram.max'    => 'The Instagram link may not exceed :max characters.',
            'social_links.facebook.string'  => 'The Facebook link must be text.',
            'social_links.facebook.max'     => 'The Facebook link may not exceed :max characters.',
            'content.array'                 => 'The template content must be a valid object.',
            'font_family.required'          => 'Select a font family.',
            'font_family.string'            => 'The font family must be text.',
            'font_family.max'               => 'The font family may not exceed :max characters.',
            'primary_color.required'        => 'Select a primary color.',
            'primary_color.string'          => 'The primary color must be text.',
            'primary_color.max'             => 'The primary color may not exceed :max characters.',
            'primary_color.regex'           => 'Enter a valid hexadecimal primary color.',
            'secondary_color.required'      => 'Select a secondary color.',
            'secondary_color.string'        => 'The secondary color must be text.',
            'secondary_color.max'           => 'The secondary color may not exceed :max characters.',
            'secondary_color.regex'         => 'Enter a valid hexadecimal secondary color.',
            'background_color.required'     => 'Select a background color.',
            'background_color.string'       => 'The background color must be text.',
            'background_color.max'          => 'The background color may not exceed :max characters.',
            'background_color.regex'        => 'Enter a valid hexadecimal background color.',
            'text_color.required'           => 'Select a text color.',
            'text_color.string'             => 'The text color must be text.',
            'text_color.max'                => 'The text color may not exceed :max characters.',
            'text_color.regex'              => 'Enter a valid hexadecimal text color.',
            'status.required'               => 'Select a template status.',
            'status.in'                     => 'Select a valid template status.',
            'is_default.required'           => 'Choose whether this is the default template.',
            'is_default.boolean'            => 'The default template setting must be true or false.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== 'published' || count($validator->errors()) > 0) {
                return;
            }

            $missingFields = $this->missingRequiredContentFields();

            if ($missingFields === []) {
                return;
            }

            $validator->errors()->add('content', 'Complete required template content before publishing.');

            foreach ($missingFields as $field) {
                $validator->errors()->add(
                    'content.'.(string) ($field['key'] ?? 'field'),
                    ((string) ($field['label'] ?? 'This field')).' is required to publish.'
                );
            }
        });
    }

    public function prepareForValidation(): void
    {
        $tenantId = Auth::user()?->tenant_id;

        // Templates are always saved against the authenticated tenant.
        if ($tenantId) {
            $this->merge(['tenant_id' => $tenantId]);
        }

        foreach (['name', 'template_key', 'business_name', 'font_family'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }

        $providedSlug = $this->has('slug') && trim((string) $this->input('slug')) !== '';
        $slugSource   = $providedSlug ? (string) $this->input('slug') : (string) $this->input('name', 'site');

        $this->merge([
            'slug'       => $providedSlug ? Str::slug($slugSource) : $this->uniqueSlug($tenantId, $slugSource),
            'is_default' => $this->boolean('is_default'),
        ]);
    }

    private function uniqueSlug(?string $tenantId, string $value): string
    {
        $routeParam = $this->route('template');
        $templateId = is_object($routeParam) ? $routeParam->id : $routeParam;
        $baseSlug   = Str::slug($value) ?: 'site';
        $slug       = $baseSlug;
        $nextIndex  = 2;

        while ($this->slugExists($tenantId, $slug, $templateId)) {
            $slug = $baseSlug.'-'.$nextIndex++;
        }

        return $slug;
    }

    private function slugExists(?string $tenantId, string $slug, mixed $templateId): bool
    {
        return Template::query()
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('slug', $slug)
            ->when($templateId, fn ($query) => $query->whereKeyNot($templateId))
            ->exists();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function missingRequiredContentFields(): array
    {
        $catalogItem = collect(app(TemplateCatalogService::class)->forWebsiteType($this->input('website_type_id')))
            ->firstWhere('key', $this->input('template_key'));
        $schema  = is_array($catalogItem['field_schema'] ?? null) ? $catalogItem['field_schema'] : [];
        $content = is_array($this->input('content')) ? $this->input('content') : [];

        return collect($schema)
            ->filter(fn (array $field): bool => (bool) ($field['required'] ?? false))
            ->filter(fn (array $field): bool => $this->blankContentValue($this->contentValue($content, (string) ($field['key'] ?? ''))))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function contentValue(array $content, string $key): mixed
    {
        return match ($key) {
            'business_name', 'display_name'                              => $this->input('business_name', $content[$key] ?? null),
            'contact_email', 'email'                                     => data_get($this->input('contact_info', []), 'email', $content[$key] ?? null),
            'contact_phone', 'phone'                                     => data_get($this->input('contact_info', []), 'phone', $content[$key] ?? null),
            'contact_address', 'contact_location', 'location', 'address' => data_get($this->input('contact_info', []), 'address', $content[$key] ?? null),
            default                                                      => $content[$key] ?? null,
        };
    }

    private function blankContentValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value === [];
        }

        return $value === null || (is_string($value) && trim($value) === '');
    }
}
