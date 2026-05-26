<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'cta',
            'section_label'      => 'Call To Action',
            'name'               => 'CTA Design 1',
            'design_key'         => 'cta-1',
            'default_enabled'    => false,
            'default_sort_order' => 10,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('body', 'Description', 'textarea', 'md:col-span-2'),
                DesignMigration::field('cta_label', 'Button Label'),
                DesignMigration::field('button_link', 'Button Link', 'url'),
            ],
            'default_content' => [
                'title'       => 'Ready to get started?',
                'body'        => 'Invite visitors to take the next step with a clear, focused offer.',
                'cta_label'   => 'Contact us',
                'button_link' => '#contact',
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('cta', 'cta-1');
    }
};
