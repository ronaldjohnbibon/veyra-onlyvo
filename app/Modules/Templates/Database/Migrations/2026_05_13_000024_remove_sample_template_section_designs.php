<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->sampleSectionTypes() as $sectionType) {
            $oldKey = sprintf('%s-%d', $sectionType, 2);
            $newKey = sprintf('%s-%d', $sectionType, 1);

            DB::table('template_sections')
                ->where('section_type', $sectionType)
                ->where('design_key', $oldKey)
                ->update([
                    'design_key' => $newKey,
                    'updated_at' => now(),
                ]);

            DB::table('template_section_designs')
                ->where('section_type', $sectionType)
                ->where('design_key', $oldKey)
                ->delete();
        }
    }

    public function down(): void {}

    /**
     * @return array<int, string>
     */
    private function sampleSectionTypes(): array
    {
        return [
            'header',
            'hero',
            'about',
            'services',
            'products',
            'portfolio',
            'faq',
            'contact',
            'footer',
        ];
    }
};
