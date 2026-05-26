<?php

namespace App\Modules\Templates\Services;

use App\Modules\Templates\Models\Template;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TemplateService
{
    public function __construct(private readonly TemplateCatalogService $catalog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Template
    {
        return DB::transaction(function () use ($data): Template {
            $data         = $this->normalizeTemplate($data);
            $data['slug'] = $this->slugForTemplate($data['tenant_id'], $data['slug'] ?? $data['name']);

            if (! empty($data['is_default'])) {
                $this->clearTenantDefaults((string) $data['tenant_id']);
            }

            return Template::create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Template $template, array $data): Template
    {
        return DB::transaction(function () use ($template, $data): Template {
            $data = $this->normalizeTemplate($data);

            if (! array_key_exists('slug', $data) || ! $data['slug']) {
                $data['slug'] = $data['name'];
            }

            $data['slug'] = $this->slugForTemplate($data['tenant_id'], $data['slug'], $template->id);

            if (! empty($data['is_default'])) {
                $this->clearTenantDefaults((string) $data['tenant_id'], $template->id);
            }

            $template->update($data);

            return $template->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeTemplate(array $data): array
    {
        foreach (['name', 'slug', 'template_key', 'business_name', 'logo', 'font_family'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim((string) $data[$field]);
            }
        }

        if (isset($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }

        if (isset($data['is_default'])) {
            $data['is_default'] = (bool) $data['is_default'];
        }

        $templateKey = (string) ($data['template_key'] ?? '');

        if ($templateKey === '' || ! $this->catalog->exists($templateKey, $data['website_type_id'] ?? null)) {
            throw ValidationException::withMessages([
                'template_key' => 'Select an available website template.',
            ]);
        }

        return $data;
    }

    private function slugForTemplate(string $tenantId, string $value, ?string $ignoreId = null): string
    {
        $baseSlug  = Str::slug($value) ?: 'site';
        $slug      = $baseSlug;
        $nextIndex = 2;

        while ($this->slugExists($tenantId, $slug, $ignoreId)) {
            $slug = $baseSlug.'-'.$nextIndex++;
        }

        return $slug;
    }

    private function slugExists(string $tenantId, string $slug, ?string $ignoreId = null): bool
    {
        return Template::withoutTenantRestrictions(function () use ($tenantId, $slug, $ignoreId): bool {
            return Template::query()
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists();
        });
    }

    private function clearTenantDefaults(string $tenantId, ?string $ignoreId = null): void
    {
        // Keep one public default per tenant by clearing older defaults first.
        Template::withoutTenantRestrictions(function () use ($tenantId, $ignoreId): void {
            Template::query()
                ->where('tenant_id', $tenantId)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->update(['is_default' => false]);
        });
    }
}
