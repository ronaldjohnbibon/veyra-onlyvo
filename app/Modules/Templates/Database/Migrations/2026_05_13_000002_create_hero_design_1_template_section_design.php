<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'hero',
            'section_label'      => 'Hero',
            'name'               => 'Hero Design 1',
            'design_key'         => 'hero-1',
            'default_enabled'    => true,
            'default_sort_order' => 2,
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('subtitle', 'Subtitle'),
                DesignMigration::field('image', 'Image URL', 'url', 'md:col-span-2'),
                DesignMigration::field('cta_label', 'Button Label'),
            ],
            'default_content' => [
                'title'     => 'Build something customers remember',
                'subtitle'  => 'A clear, polished intro for your business and offer.',
                'image'     => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=80',
                'cta_label' => 'Start today',
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('hero', 'hero-1');
    }
};
