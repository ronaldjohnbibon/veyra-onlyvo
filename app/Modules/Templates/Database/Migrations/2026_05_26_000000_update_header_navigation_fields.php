<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $headerFields = [
            ['key' => 'tagline', 'label' => 'Tagline', 'type' => 'text'],
            [
                'key'   => 'navigation_items',
                'label' => 'Navigation Items',
                'type'  => 'navigation',
                'class' => 'md:col-span-2',
            ],
        ];

        $defaultContent = [
            'tagline'          => 'Business website',
            'navigation_items' => $this->defaultNavigationItems(),
        ];

        DB::table('template_section_designs')
            ->where('section_type', 'header')
            ->update([
                'fields_json'          => json_encode($headerFields, JSON_THROW_ON_ERROR),
                'default_content_json' => json_encode($defaultContent, JSON_THROW_ON_ERROR),
                'updated_at'           => now(),
            ]);

        DB::table('template_sections')
            ->where('section_type', 'header')
            ->orderBy('id')
            ->each(function (object $section): void {
                $content = $this->decode($section->content_json);

                unset($content['cta_label'], $content['button_link']);
                $content['navigation_items'] = $this->validNavigationItems(
                    $content['navigation_items'] ?? $this->defaultNavigationItems(),
                );

                DB::table('template_sections')
                    ->where('id', $section->id)
                    ->update([
                        'content_json' => json_encode($content, JSON_THROW_ON_ERROR),
                        'updated_at'   => now(),
                    ]);
            });
    }

    public function down(): void
    {
        $headerFields = [
            ['key' => 'tagline', 'label' => 'Tagline', 'type' => 'text'],
        ];

        $defaultContent = [
            'tagline' => 'Business website',
        ];

        DB::table('template_section_designs')
            ->where('section_type', 'header')
            ->update([
                'fields_json'          => json_encode($headerFields, JSON_THROW_ON_ERROR),
                'default_content_json' => json_encode($defaultContent, JSON_THROW_ON_ERROR),
                'updated_at'           => now(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function defaultNavigationItems(): array
    {
        return [
            ['label' => 'Services', 'section_type' => 'services'],
            ['label' => 'FAQ', 'section_type' => 'faq'],
            ['label' => 'Contact', 'section_type' => 'contact'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function validNavigationItems(mixed $items): array
    {
        if (! is_array($items)) {
            return $this->defaultNavigationItems();
        }

        return array_values(array_filter(array_map(function (mixed $item): ?array {
            if (! is_array($item)) {
                return null;
            }

            $label       = trim((string) ($item['label'] ?? ''));
            $sectionType = trim((string) ($item['section_type'] ?? ''));

            if ($label === '' || $sectionType === '') {
                return null;
            }

            return ['label' => $label, 'section_type' => $sectionType];
        }, $items)));
    }
};
