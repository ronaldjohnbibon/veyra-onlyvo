<?php

use App\Modules\Templates\Database\TemplateCatalogMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TemplateCatalogMigration::upsert('restaurant-website', $this->item());
    }

    public function down(): void
    {
        TemplateCatalogMigration::delete('restaurant-website', 'template-1');
    }

    /**
     * @return array<string, mixed>
     */
    private function item(): array
    {
        return [
            'key'           => 'template-1',
            'name'          => 'Restaurant Showcase',
            'description'   => 'A warm restaurant website with menu highlights, hours, and booking prompts.',
            'preview_image' => null,
            'field_schema'  => [
                ['key' => 'business_name', 'label' => 'Restaurant Name', 'type' => 'text', 'required' => true],
                ['key' => 'hero_eyebrow', 'label' => 'Hero Eyebrow', 'type' => 'text'],
                ['key' => 'hero_title', 'label' => 'Hero Title', 'type' => 'textarea', 'required' => true],
                ['key' => 'hero_text', 'label' => 'Hero Text', 'type' => 'textarea'],
                ['key' => 'menu_intro', 'label' => 'Menu Intro', 'type' => 'textarea'],
                ['key' => 'menu_items', 'label' => 'Menu Items', 'type' => 'repeater', 'fields' => [
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['key' => 'price', 'label' => 'Price', 'type' => 'text'],
                ]],
                ['key' => 'hours', 'label' => 'Hours', 'type' => 'repeater', 'fields' => [
                    ['key' => 'day', 'label' => 'Day', 'type' => 'text'],
                    ['key' => 'time', 'label' => 'Time', 'type' => 'text'],
                ]],
                ['key' => 'location', 'label' => 'Location', 'type' => 'text'],
                ['key' => 'contact_email', 'label' => 'Contact Email', 'type' => 'email'],
                ['key' => 'contact_phone', 'label' => 'Contact Phone', 'type' => 'phone'],
                ['key' => 'reservation_link', 'label' => 'Reservation Link', 'type' => 'url'],
            ],
            'default_content' => [
                'business_name' => 'Onlyvo Kitchen',
                'hero_eyebrow'  => 'Fresh daily',
                'hero_title'    => 'A complete restaurant website for menus, bookings, and local discovery.',
                'hero_text'     => 'Showcase signature dishes, opening details, and contact information in one polished layout.',
                'menu_intro'    => 'Highlight a tight set of dishes and invite guests to book or call.',
                'menu_items'    => [
                    ['name' => 'Seasonal tasting plate', 'description' => 'Peak-season produce, bright sauces, and crisp textures.', 'price' => '$18'],
                    ['name' => 'House pasta', 'description' => 'Fresh pasta with slow-cooked sauce and shaved cheese.', 'price' => '$24'],
                    ['name' => 'Signature dessert', 'description' => 'A rotating sweet finish from the pastry team.', 'price' => '$12'],
                ],
                'hours' => [
                    ['day' => 'Tuesday - Thursday', 'time' => '5:00 PM - 10:00 PM'],
                    ['day' => 'Friday - Saturday', 'time' => '5:00 PM - 11:00 PM'],
                    ['day' => 'Sunday', 'time' => '4:00 PM - 9:00 PM'],
                ],
                'location'         => '123 Market Street',
                'contact_email'    => 'hello@example.com',
                'contact_phone'    => '+1 555 0100',
                'reservation_link' => 'https://example.com/reservations',
            ],
        ];
    }
};
