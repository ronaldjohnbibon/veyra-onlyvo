<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'portfolio',
            'section_label'      => 'Portfolio',
            'name'               => 'Portfolio Design 1',
            'design_key'         => 'portfolio-1',
            'default_enabled'    => false,
            'default_sort_order' => 6,
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('items', 'Content Items', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title' => 'Portfolio',
                'items' => ['Client Launch', 'Brand Refresh', 'Growth Campaign'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('portfolio', 'portfolio-1');
    }
};
