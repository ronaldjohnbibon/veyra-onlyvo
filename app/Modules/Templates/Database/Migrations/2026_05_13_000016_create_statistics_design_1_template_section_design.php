<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'statistics',
            'section_label'      => 'Statistics',
            'name'               => 'Statistics Design 1',
            'design_key'         => 'statistics-1',
            'default_enabled'    => false,
            'default_sort_order' => 16,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('stats', 'Statistics', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title' => 'Achievements',
                'stats' => ['10+ years', '250+ projects', '98% satisfaction'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('statistics', 'statistics-1');
    }
};
