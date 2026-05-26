<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'pricing',
            'section_label'      => 'Pricing',
            'name'               => 'Pricing Design 1',
            'design_key'         => 'pricing-1',
            'default_enabled'    => false,
            'default_sort_order' => 13,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('subtitle', 'Subtitle'),
                DesignMigration::field('items', 'Plans', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title'    => 'Plans',
                'subtitle' => 'Simple options for different needs.',
                'items'    => ['Starter - $99', 'Growth - $249', 'Premium - Custom'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('pricing', 'pricing-1');
    }
};
