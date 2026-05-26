<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'gallery',
            'section_label'      => 'Gallery',
            'name'               => 'Gallery Design 1',
            'design_key'         => 'gallery-1',
            'default_enabled'    => false,
            'default_sort_order' => 18,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('items', 'Image URLs', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title' => 'Gallery',
                'items' => [
                    'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=900&q=80',
                ],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('gallery', 'gallery-1');
    }
};
