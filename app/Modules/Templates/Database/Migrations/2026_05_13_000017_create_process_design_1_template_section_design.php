<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'process',
            'section_label'      => 'Process',
            'name'               => 'Process Design 1',
            'design_key'         => 'process-1',
            'default_enabled'    => false,
            'default_sort_order' => 17,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('subtitle', 'Subtitle'),
                DesignMigration::field('items', 'Steps', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title'    => 'How it works',
                'subtitle' => 'A simple path from first call to finished work.',
                'items'    => ['Discover', 'Plan', 'Build', 'Launch'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('process', 'process-1');
    }
};
