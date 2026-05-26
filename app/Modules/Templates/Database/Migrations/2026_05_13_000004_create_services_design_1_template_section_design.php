<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'services',
            'section_label'      => 'Services',
            'name'               => 'Services Design 1',
            'design_key'         => 'services-1',
            'default_enabled'    => true,
            'default_sort_order' => 4,
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('subtitle', 'Subtitle'),
                DesignMigration::field('items', 'Content Items', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title'    => 'Services',
                'subtitle' => 'What customers can hire you to do.',
                'items'    => ['Strategy', 'Implementation', 'Support'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('services', 'services-1');
    }
};
