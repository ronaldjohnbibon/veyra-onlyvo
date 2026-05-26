<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'social_links',
            'section_label'      => 'Social Links',
            'name'               => 'Social Links Design 1',
            'design_key'         => 'social-links-1',
            'default_enabled'    => false,
            'default_sort_order' => 21,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('items', 'Social Links', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title' => 'Connect with us',
                'items' => ['LinkedIn', 'Instagram', 'Facebook'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('social_links', 'social-links-1');
    }
};
