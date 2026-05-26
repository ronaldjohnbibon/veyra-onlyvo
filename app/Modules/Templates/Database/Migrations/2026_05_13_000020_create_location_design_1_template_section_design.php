<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'location',
            'section_label'      => 'Location',
            'name'               => 'Location Design 1',
            'design_key'         => 'location-1',
            'default_enabled'    => false,
            'default_sort_order' => 20,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('body', 'Description', 'textarea', 'md:col-span-2'),
                DesignMigration::field('map_url', 'Map Link', 'url'),
            ],
            'default_content' => [
                'title'   => 'Visit us',
                'body'    => 'Find our office or reach out before you arrive.',
                'map_url' => 'https://maps.google.com',
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('location', 'location-1');
    }
};
