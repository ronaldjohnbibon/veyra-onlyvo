<?php

namespace App\Modules\Templates\Services;

use App\Modules\Templates\Models\Template;
use App\Modules\Templates\Models\TemplateSectionDesign;
use Illuminate\Support\Facades\DB;

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

            $template = Template::create($this->normalizeTemplate($data));
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

            $template->update($this->normalizeTemplate($data));
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
        foreach (['name', 'business_name', 'logo', 'font_family'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim((string) $data[$field]);
            }
        }

        return $data;
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
                    'content_json' => $section['content_json'] ?? [],
                ],
            );
        }
    }
}
