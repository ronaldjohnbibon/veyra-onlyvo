<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('template_section_designs')) {
            return;
        }

        if (Schema::hasTable('template_sections')) {
            DB::table('template_sections')->where('section_type', 'navigation')->delete();
        }

        DB::table('template_section_designs')->where('section_type', 'navigation')->delete();

        foreach ($this->headerDesigns() as $design) {
            DB::table('template_section_designs')
                ->where('section_type', 'header')
                ->where('design_key', $design['design_key'])
                ->update([
                    'fields_json'          => $this->encode($design['fields']),
                    'default_content_json' => $this->encode($design['default_content']),
                    'updated_at'           => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('template_section_designs')) {
            return;
        }

        foreach ($this->legacyHeaderDesigns() as $design) {
            DB::table('template_section_designs')
                ->where('section_type', 'header')
                ->where('design_key', $design['design_key'])
                ->update([
                    'fields_json'          => $this->encode($design['fields']),
                    'default_content_json' => $this->encode($design['default_content']),
                    'updated_at'           => now(),
                ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function headerDesigns(): array
    {
        return [
            [
                'design_key' => 'header-1',
                'fields'     => [
                    $this->field('tagline', 'Tagline'),
                    $this->field('cta_label', 'Button Label'),
                    $this->field('button_link', 'Button Link', 'url'),
                ],
                'default_content' => [
                    'tagline'     => 'Business website',
                    'cta_label'   => '',
                    'button_link' => '#template-section-contact',
                ],
            ],
            [
                'design_key' => 'header-2',
                'fields'     => [
                    $this->field('tagline', 'Tagline'),
                    $this->field('cta_label', 'Button Label'),
                    $this->field('button_link', 'Button Link', 'url'),
                ],
                'default_content' => [
                    'tagline'     => 'Business website',
                    'cta_label'   => 'Contact us',
                    'button_link' => '#template-section-contact',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function legacyHeaderDesigns(): array
    {
        return [
            [
                'design_key' => 'header-1',
                'fields'     => [
                    $this->field('tagline', 'Tagline'),
                ],
                'default_content' => [
                    'tagline' => 'Business website',
                ],
            ],
            [
                'design_key' => 'header-2',
                'fields'     => [
                    $this->field('tagline', 'Tagline'),
                    $this->field('cta_label', 'Button Label'),
                ],
                'default_content' => [
                    'tagline'   => 'Business website',
                    'cta_label' => 'Contact us',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function field(string $key, string $label, string $type = 'text'): array
    {
        return [
            'key'   => $key,
            'label' => $label,
            'type'  => $type,
        ];
    }

    /**
     * @param  array<string|int, mixed>  $value
     */
    private function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
};
