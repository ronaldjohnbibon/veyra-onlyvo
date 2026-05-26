<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'products',
            'section_label'      => 'Products',
            'name'               => 'Products Design 1',
            'design_key'         => 'products-1',
            'default_enabled'    => false,
            'default_sort_order' => 5,
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('items', 'Content Items', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title' => 'Products',
                'items' => ['Product One', 'Product Two', 'Product Three'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('products', 'products-1');
    }
};
