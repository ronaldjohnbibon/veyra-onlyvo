<?php

namespace App\Modules\Templates\Services;

use App\Modules\Templates\Models\Template;
use App\Modules\Templates\Models\TemplateSectionDesign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TemplateService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Template
    {
        return DB::transaction(function () use ($data): Template {
            $sections = $data['sections'] ?? [];
            unset($data['sections']);

            $data         = $this->normalizeTemplate($data);
            $data['slug'] = $this->slugForTemplate($data['tenant_id'], $data['slug'] ?? $data['name']);

            if (! empty($data['is_default'])) {
                $this->clearTenantDefaults((string) $data['tenant_id']);
            }

            $template = Template::create($data);
            $this->syncSections($template, $sections);

            return $template->fresh(['sections']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Template $template, array $data): Template
    {
        return DB::transaction(function () use ($template, $data): Template {
            $sections = $data['sections'] ?? [];
            unset($data['sections']);

            $data = $this->normalizeTemplate($data);

            if (! array_key_exists('slug', $data) || ! $data['slug']) {
                $data['slug'] = $data['name'];
            }

            $data['slug'] = $this->slugForTemplate($data['tenant_id'], $data['slug'], $template->id);

            if (! empty($data['is_default'])) {
                $this->clearTenantDefaults((string) $data['tenant_id'], $template->id);
            }

            $template->update($data);
            $this->syncSections($template, $sections);

            return $template->fresh(['sections']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeTemplate(array $data): array
    {
        foreach (['name', 'slug', 'business_name', 'logo', 'font_family'] as $field) {
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

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    private function syncSections(Template $template, array $sections): void
    {
        // Load active designs once so invalid design keys can fall back safely.
        $activeDesigns = TemplateSectionDesign::query()
            ->where('is_active', true)
            ->get()
            ->groupBy('section_type');
        $sectionTypes = collect($sections)->pluck('section_type')->map(fn ($type) => (string) $type)->all();

        foreach ($sections as $section) {
            $sectionType = (string) $section['section_type'];
            $designKey   = (string) $section['design_key'];
            $designs     = $activeDesigns->get($sectionType);

            if ($designs && ! $designs->contains('design_key', $designKey)) {
                $designKey = (string) $designs->first()->design_key;
            }

            $template->sections()->updateOrCreate(
                ['section_type' => $sectionType],
                [
                    'design_key'   => $designKey,
                    'sort_order'   => (int) $section['sort_order'],
                    'is_enabled'   => (bool) $section['is_enabled'],
                    'content_json' => $this->normalizeSectionContent($section, $sectionTypes),
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $section
     * @param  array<int, string>  $sectionTypes
     * @return array<string, mixed>
     */
    private function normalizeSectionContent(array $section, array $sectionTypes): array
    {
        $content = is_array($section['content_json'] ?? null) ? $section['content_json'] : [];

        if (($section['section_type'] ?? '') !== 'header') {
            return $content;
        }

        unset($content['cta_label'], $content['button_link']);

        $allowedSectionTypes = collect($sectionTypes)
            ->reject(fn (string $type) => in_array($type, ['header', 'footer'], true))
            ->values()
            ->all();

        $content['navigation_items'] = collect($content['navigation_items'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): array {
                return [
                    'label'        => trim((string) ($item['label'] ?? '')),
                    'section_type' => trim((string) ($item['section_type'] ?? '')),
                ];
            })
            ->filter(function (array $item) use ($allowedSectionTypes): bool {
                return $item['label'] !== ''
                    && in_array($item['section_type'], $allowedSectionTypes, true);
            })
            ->values()
            ->all();

        return $content;
    }
}
