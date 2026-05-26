<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'footer',
            'section_label'      => 'Footer',
            'name'               => 'Footer Design 1',
            'design_key'         => 'footer-1',
            'default_enabled'    => true,
            'default_sort_order' => 9,
            'fields'             => [
                DesignMigration::field('body', 'Body', 'textarea', 'md:col-span-2'),
            ],
            'default_content' => [
                'body' => 'Ready to work together?',
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('footer', 'footer-1');
    }
};
