<?php

use App\Modules\Templates\Database\Support\TemplateSectionDesignMigration as DesignMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DesignMigration::insert([
            'section_type'       => 'booking',
            'section_label'      => 'Booking',
            'name'               => 'Booking Design 1',
            'design_key'         => 'booking-1',
            'default_enabled'    => false,
            'default_sort_order' => 14,
            'preview_variant'    => 'extended',
            'fields'             => [
                DesignMigration::field('title', 'Title'),
                DesignMigration::field('body', 'Description', 'textarea', 'md:col-span-2'),
                DesignMigration::field('schedule', 'Availability'),
                DesignMigration::field('cta_label', 'Button Label'),
            ],
            'default_content' => [
                'title'     => 'Book an appointment',
                'body'      => 'Choose a time to talk with our team about your goals.',
                'schedule'  => 'Monday to Friday, 9 AM - 5 PM',
                'cta_label' => 'Request a time',
            ],
        ]);
    }

    public function down(): void
    {
        DesignMigration::delete('booking', 'booking-1');
    }
};
