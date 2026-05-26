<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'newsletter',
            'section_label'      => 'Newsletter',
            'name'               => 'Newsletter Design 1',
            'design_key'         => 'newsletter-1',
            'default_enabled'    => false,
            'default_sort_order' => 19,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('body', 'Description', 'textarea', 'md:col-span-2'),
                DesignMigration::field('email_placeholder', 'Email Placeholder'),
                DesignMigration::field('cta_label', 'Button Label'),
            ],
            'default_content' => [
                'title'             => 'Stay in the loop',
                'body'              => 'Share updates, offers, and useful notes with subscribers.',
                'email_placeholder' => 'Email address',
                'cta_label'         => 'Subscribe',
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('newsletter', 'newsletter-1');
    }
};
