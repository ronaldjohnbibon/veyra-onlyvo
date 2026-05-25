<?php

namespace App\Modules\Templates\Http\Requests;

use App\Modules\Templates\Models\Template;
use App\Modules\Templates\Models\TemplateSectionDesign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        $routeParam = $this->route('template');
        $templateId = is_object($routeParam) ? $routeParam->id : $routeParam;
        $tenantId   = $this->input('tenant_id');
        $sections   = implode(',', $this->sectionTypes());

        return [
            'tenant_id' => ['nullable', 'uuid', Rule::exists('tenants', 'id')],
            'name'      => [
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
            'business_name'    => ['required', 'string', 'max:150'],
            'logo'             => ['required', 'string'],
            'contact_info'     => ['required', 'array'],
            'social_links'     => ['required', 'array'],
            'font_family'      => ['required', 'string', 'max:100'],
            'primary_color'    => ['required', 'string', 'max:20'],
            'secondary_color'  => ['required', 'string', 'max:20'],
            'background_color' => ['required', 'string', 'max:20'],
            'text_color'       => ['required', 'string', 'max:20'],
            'status'           => ['required', Rule::in(['draft', 'published'])],
            'is_default'       => ['required', 'boolean'],

            'sections'                => ['required', 'array', 'min:1'],
            'sections.*.section_type' => ['required', 'string', "in:$sections"],
            'sections.*.design_key'   => ['required', 'string', 'max:80'],
            'sections.*.sort_order'   => ['required', 'integer', 'min:0'],
            'sections.*.is_enabled'   => ['required', 'boolean'],
            'sections.*.content_json' => ['required', 'array'],
        ];
    }

    public function prepareForValidation(): void
    {
        $tenantId = Auth::user()?->tenant_id;

        // Templates are always saved against the authenticated tenant.
        if (! $this->has('tenant_id') && $tenantId) {
            $this->merge(['tenant_id' => $tenantId]);
        }

        foreach (['name', 'business_name', 'logo', 'font_family'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }

        $providedSlug = $this->has('slug') && trim((string) $this->input('slug')) !== '';
        $slugSource   = $providedSlug ? (string) $this->input('slug') : (string) $this->input('name', 'site');

        $this->merge([
            'slug'       => $providedSlug ? Str::slug($slugSource) : $this->uniqueSlug($slugSource),
            'is_default' => $this->boolean('is_default'),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function sectionTypes(): array
    {
        $sectionTypes = TemplateSectionDesign::query()
            ->where('is_active', true)
            ->distinct()
            ->pluck('section_type')
            ->all();

        return $sectionTypes ?: ['header', 'hero', 'about', 'services', 'products', 'portfolio', 'faq', 'contact', 'footer'];
    }

    private function uniqueSlug(string $value): string
    {
        $routeParam = $this->route('template');
        $templateId = is_object($routeParam) ? $routeParam->id : $routeParam;
        $tenantId   = (string) $this->input('tenant_id');
        $baseSlug   = Str::slug($value) ?: 'site';
        $slug       = $baseSlug;
        $nextIndex  = 2;

        while ($this->slugExists($tenantId, $slug, $templateId)) {
            $slug = $baseSlug.'-'.$nextIndex++;
        }

        return $slug;
    }

    private function slugExists(string $tenantId, string $slug, mixed $templateId): bool
    {
        return Template::withoutTenantRestrictions(function () use ($tenantId, $slug, $templateId): bool {
            return Template::query()
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->when($templateId, fn ($query) => $query->whereKeyNot($templateId))
                ->exists();
        });
    }
}
