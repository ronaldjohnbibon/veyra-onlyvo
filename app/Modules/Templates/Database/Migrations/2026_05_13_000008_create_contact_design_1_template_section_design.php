<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'contact',
            'section_label'      => 'Contact',
            'name'               => 'Contact Design 1',
            'design_key'         => 'contact-1',
            'default_enabled'    => true,
            'default_sort_order' => 8,
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('body', 'Description', 'textarea', 'md:col-span-2'),
            ],
            'default_content' => [
                'title' => 'Contact us',
                'body'  => 'Tell visitors how to reach you and what to expect when they get in touch.',
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('contact', 'contact-1');
    }
};
