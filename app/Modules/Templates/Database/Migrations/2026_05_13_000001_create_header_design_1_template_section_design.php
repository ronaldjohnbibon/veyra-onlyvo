<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'header',
            'section_label'      => 'Header',
            'name'               => 'Header Design 1',
            'design_key'         => 'header-1',
            'default_enabled'    => true,
            'default_sort_order' => 1,
            'fields'             => [
                DesignMigration::field('tagline', 'Tagline'),
                DesignMigration::field('navigation_items', 'Navigation Items', 'navigation', 'md:col-span-2'),
            ],
            'default_content' => [
                'tagline'          => 'Business website',
                'navigation_items' => DesignMigration::defaultNavigationItems(),
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('header', 'header-1');
    }
};
