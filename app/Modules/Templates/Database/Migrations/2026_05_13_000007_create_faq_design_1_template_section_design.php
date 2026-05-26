<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'faq',
            'section_label'      => 'FAQ',
            'name'               => 'FAQ Design 1',
            'design_key'         => 'faq-1',
            'default_enabled'    => true,
            'default_sort_order' => 7,
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('body', 'Answer', 'textarea', 'md:col-span-2'),
                DesignMigration::field('items', 'Questions', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title' => 'FAQ',
                'body'  => 'Add a short answer here.',
                'items' => ['How do we get started?', 'What does the process include?', 'How soon can we launch?'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('faq', 'faq-1');
    }
};
