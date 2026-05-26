<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'features',
            'section_label'      => 'Features',
            'name'               => 'Features Design 1',
            'design_key'         => 'features-1',
            'default_enabled'    => false,
            'default_sort_order' => 22,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('subtitle', 'Subtitle'),
                DesignMigration::field('items', 'Features', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title'    => 'Why choose us',
                'subtitle' => 'Practical strengths customers notice quickly.',
                'items'    => ['Clear communication', 'Reliable delivery', 'Flexible support'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('features', 'features-1');
    }
};
