<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('template_section_designs')) {
            return;
        }

        $now = now();

        foreach ($this->designs() as $design) {
            $query = DB::table('template_section_designs')
                ->where('section_type', $design['section_type'])
                ->where('design_key', $design['design_key']);

            $values = [
                'name'          => $design['name'],
                'preview_image' => $this->previewImage($design['name']),
                'is_active'     => true,
                'updated_at'    => $now,
            ];

            if ($query->exists()) {
                $query->update($values);

                continue;
            }

            DB::table('template_section_designs')->insert(array_merge($values, [
                'id'           => (string) Str::uuid(),
                'section_type' => $design['section_type'],
                'design_key'   => $design['design_key'],
                'created_at'   => $now,
            ]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('template_section_designs')) {
            return;
        }

        DB::table('template_section_designs')
            ->where('section_type', 'contact')
            ->delete();
    }

    /**
     * @return array<int, array{section_type: string, name: string, design_key: string}>
     */
    private function designs(): array
    {
        return [
            ['section_type' => 'contact', 'name' => 'Contact Design 1', 'design_key' => 'contact-1'],
            ['section_type' => 'contact', 'name' => 'Contact Design 2', 'design_key' => 'contact-2'],
        ];
    }

    private function previewImage(string $label): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180" viewBox="0 0 320 180">'
            .'<rect width="320" height="180" fill="#f8fafc"/>'
            .'<rect x="22" y="22" width="276" height="136" rx="8" fill="#ffffff" stroke="#cbd5e1"/>'
            .'<rect x="44" y="48" width="112" height="14" rx="3" fill="#14b8a6"/>'
            .'<rect x="44" y="76" width="226" height="8" rx="3" fill="#94a3b8"/>'
            .'<rect x="44" y="96" width="178" height="8" rx="3" fill="#cbd5e1"/>'
            .'<rect x="224" y="112" width="46" height="22" rx="5" fill="#0f766e"/>'
            .'<text x="44" y="142" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#0f172a">'
            .htmlspecialchars($label, ENT_QUOTES)
            .'</text></svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }
};
