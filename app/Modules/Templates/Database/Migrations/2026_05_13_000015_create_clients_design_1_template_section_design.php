<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'clients',
            'section_label'      => 'Clients',
            'name'               => 'Clients Design 1',
            'design_key'         => 'clients-1',
            'default_enabled'    => false,
            'default_sort_order' => 15,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('items', 'Client Names', 'list', 'md:col-span-2'),
            ],
            'default_content' => [
                'title' => 'Trusted by',
                'items' => ['Northstar', 'Acme Co.', 'Brightline', 'Summit'],
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('clients', 'clients-1');
    }
};
