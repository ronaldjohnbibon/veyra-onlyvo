<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'testimonials',
            'section_label'      => 'Testimonials',
            'name'               => 'Testimonials Design 1',
            'design_key'         => 'testimonials-1',
            'default_enabled'    => false,
            'default_sort_order' => 11,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('subtitle', 'Subtitle'),
                DesignMigration::field('items', 'Reviews', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title'    => 'What clients say',
                'subtitle' => 'A few words from people we have helped.',
                'items'    => ['Reliable, clear, and easy to work with.', 'The team understood exactly what we needed.', 'A polished experience from start to finish.'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('testimonials', 'testimonials-1');
    }
};
