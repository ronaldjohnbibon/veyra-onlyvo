<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'mission_vision',
            'section_label'      => 'Mission / Vision',
            'name'               => 'Mission Vision Design 1',
            'design_key'         => 'mission-vision-1',
            'default_enabled'    => false,
            'default_sort_order' => 23,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('body', 'Mission', 'textarea', 'md:col-span-2'),
                DesignMigration::field('subtitle', 'Vision'),
            ],
            'default_content' => [
                'title'    => 'Mission and vision',
                'body'     => 'Our mission is to make quality service simple, useful, and dependable.',
                'subtitle' => 'Our vision is to become the trusted partner customers return to year after year.',
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('mission_vision', 'mission-vision-1');
    }
};
