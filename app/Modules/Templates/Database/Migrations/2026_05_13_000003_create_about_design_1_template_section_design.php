<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'about',
            'section_label'      => 'About',
            'name'               => 'About Design 1',
            'design_key'         => 'about-1',
            'default_enabled'    => true,
            'default_sort_order' => 3,
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('body', 'Description', 'textarea', 'md:col-span-2'),
                DesignMigration::field('image', 'Image URL', 'url', 'md:col-span-2'),
                DesignMigration::field('cta_label', 'Button Label'),
                DesignMigration::field('button_link', 'Button Link', 'url'),
            ],
            'default_content' => [
                'title'       => 'About our business',
                'body'        => 'Share the story, values, and strengths that make your company easy to trust.',
                'image'       => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=900&q=80',
                'cta_label'   => 'Learn more',
                'button_link' => '#contact',
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('about', 'about-1');
    }
};
