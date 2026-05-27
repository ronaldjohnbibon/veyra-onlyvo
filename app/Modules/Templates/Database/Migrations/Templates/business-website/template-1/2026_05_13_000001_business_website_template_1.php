<?php

use App\Modules\Templates\Database\TemplateCatalogMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TemplateCatalogMigration::upsert('business-website', $this->item());
    }

    public function down(): void
    {
        TemplateCatalogMigration::delete('business-website', 'template-1');
    }

    /**
     * @return array<string, mixed>
     */
    private function item(): array
    {
        return [
            'key'           => 'template-1',
            'name'          => 'Business Classic',
            'description'   => 'A polished company website with hero, services, proof, and contact areas.',
            'preview_image' => null,
            'field_schema'  => [
                ['key' => 'business_name', 'label' => 'Business Name', 'type' => 'text', 'required' => true],
                ['key' => 'hero_eyebrow', 'label' => 'Hero Eyebrow', 'type' => 'text'],
                ['key' => 'hero_title', 'label' => 'Hero Title', 'type' => 'textarea', 'required' => true],
                ['key' => 'hero_text', 'label' => 'Hero Text', 'type' => 'textarea'],
                ['key' => 'primary_cta_label', 'label' => 'Primary CTA Label', 'type' => 'text'],
                ['key' => 'secondary_cta_label', 'label' => 'Secondary CTA Label', 'type' => 'text'],
                ['key' => 'services', 'label' => 'Services', 'type' => 'repeater', 'fields' => [
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ]],
                ['key' => 'stats', 'label' => 'Stats', 'type' => 'repeater', 'fields' => [
                    ['key' => 'value', 'label' => 'Value', 'type' => 'text'],
                    ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
                ]],
                ['key' => 'proof_title', 'label' => 'Proof Title', 'type' => 'textarea'],
                ['key' => 'proof_text', 'label' => 'Proof Text', 'type' => 'textarea'],
                ['key' => 'contact_email', 'label' => 'Contact Email', 'type' => 'email'],
                ['key' => 'contact_phone', 'label' => 'Contact Phone', 'type' => 'phone'],
                ['key' => 'contact_address', 'label' => 'Contact Address', 'type' => 'text'],
                ['key' => 'website_url', 'label' => 'Website URL', 'type' => 'url'],
            ],
            'default_content' => [
                'business_name'       => 'Onlyvo Studio',
                'hero_eyebrow'        => 'Practical digital systems',
                'hero_title'          => 'A complete business website for teams ready to look sharper online.',
                'hero_text'           => 'We help customers understand what you do, why it matters, and how to start a conversation.',
                'primary_cta_label'   => 'Start a project',
                'secondary_cta_label' => 'View services',
                'services'            => [
                    ['title' => 'Brand strategy', 'description' => 'Sharpen positioning and turn it into clear website messaging.'],
                    ['title' => 'Website design', 'description' => 'Build a polished site structure with useful calls to action.'],
                    ['title' => 'Growth consulting', 'description' => 'Improve customer paths from first visit to qualified lead.'],
                ],
                'stats' => [
                    ['value' => '12+ years', 'label' => 'Measured delivery for growing companies.'],
                    ['value' => '240 projects', 'label' => 'Launches across service and product teams.'],
                    ['value' => '98% retention', 'label' => 'Long-term client partnerships.'],
                ],
                'proof_title'     => 'Built for quick scanning and confident decisions.',
                'proof_text'      => 'Focused messaging, service cards, credibility markers, and a direct contact area help visitors move with confidence.',
                'contact_email'   => 'hello@example.com',
                'contact_phone'   => '+1 555 0100',
                'contact_address' => '123 Market Street',
                'website_url'     => 'https://example.com',
            ],
        ];
    }
};
