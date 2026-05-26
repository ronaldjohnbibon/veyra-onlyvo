<?php

namespace App\Modules\Templates\Database\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TemplateSectionDesignMigration
{
    /**
     * @param  array<string, mixed>  $design
     */
    public static function insert(array $design): void
    {
        $now = now();

        $values = [
            'section_label'        => $design['section_label'],
            'name'                 => $design['name'],
            'preview_image'        => self::previewImage($design['name'], $design['preview_variant'] ?? 'default'),
            'fields_json'          => self::encode($design['fields']),
            'default_content_json' => self::encode($design['default_content']),
            'default_enabled'      => $design['default_enabled'],
            'default_sort_order'   => $design['default_sort_order'],
            'is_active'            => true,
            'updated_at'           => $now,
        ];

        $query = DB::table('template_section_designs')
            ->where('section_type', $design['section_type'])
            ->where('design_key', $design['design_key']);

        if ($query->exists()) {
            $query->update($values);

            return;
        }

        DB::table('template_section_designs')->insert(array_merge($values, [
            'id'           => (string) Str::uuid(),
            'section_type' => $design['section_type'],
            'design_key'   => $design['design_key'],
            'created_at'   => $now,
        ]));
    }

    public static function delete(string $sectionType, string $designKey): void
    {
        DB::table('template_section_designs')
            ->where('section_type', $sectionType)
            ->where('design_key', $designKey)
            ->delete();
    }

    /**
     * @return array<string, string>
     */
    public static function field(
        string $key,
        string $label,
        string $type = 'text',
        ?string $className = null,
    ): array {
        return array_filter([
            'key'   => $key,
            'label' => $label,
            'type'  => $type,
            'class' => $className,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function defaultNavigationItems(): array
    {
        return [
            ['label' => 'Services', 'section_type' => 'services'],
            ['label' => 'FAQ', 'section_type' => 'faq'],
            ['label' => 'Contact', 'section_type' => 'contact'],
        ];
    }

    private static function previewImage(string $label, string $variant = 'default'): string
    {
        $button = $variant === 'extended'
            ? '<rect x="44" y="116" width="80" height="18" rx="4" fill="#0f766e"/>'
            : '<rect x="224" y="112" width="46" height="22" rx="5" fill="#0f766e"/>';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180" viewBox="0 0 320 180">'
            .'<rect width="320" height="180" fill="#f8fafc"/>'
            .'<rect x="22" y="22" width="276" height="136" rx="8" fill="#ffffff" stroke="#cbd5e1"/>'
            .'<rect x="44" y="48" width="112" height="14" rx="3" fill="#14b8a6"/>'
            .'<rect x="44" y="76" width="226" height="8" rx="3" fill="#94a3b8"/>'
            .'<rect x="44" y="96" width="178" height="8" rx="3" fill="#cbd5e1"/>'
            .$button
            .'<text x="44" y="142" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#0f172a">'
            .htmlspecialchars($label, ENT_QUOTES)
            .'</text></svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }

    /**
     * @param  array<string|int, mixed>  $value
     */
    private static function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
