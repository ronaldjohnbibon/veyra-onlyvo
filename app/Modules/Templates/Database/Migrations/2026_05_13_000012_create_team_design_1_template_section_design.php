<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'team',
            'section_label'      => 'Team',
            'name'               => 'Team Design 1',
            'design_key'         => 'team-1',
            'default_enabled'    => false,
            'default_sort_order' => 12,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('subtitle', 'Subtitle'),
                DesignMigration::field('items', 'Team Members', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title'    => 'Our team',
                'subtitle' => 'The people behind the work.',
                'items'    => ['Alex Carter - Founder', 'Maya Lee - Operations', 'Jordan Smith - Customer Success'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('team', 'team-1');
    }
};
