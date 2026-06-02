<?php

use App\Tenant\Templates\Database\TemplateCatalogMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TemplateCatalogMigration::upsert('portfolio-website', $this->item());
    }

    public function down(): void
    {
        TemplateCatalogMigration::delete('portfolio-website', 'template-1');
    }

    /**
     * @return array<string, mixed>
     */
    private function item(): array
    {
        return [
            'key'           => 'template-1',
            'name'          => 'Portfolio Studio',
            'description'   => 'A clean personal portfolio with project highlights and contact details.',
            'preview_image' => null,
            'field_schema'  => [
                ['key' => 'business_name', 'label' => 'Display Name', 'type' => 'text', 'required' => true],
                ['key' => 'headline', 'label' => 'Headline', 'type' => 'textarea', 'required' => true],
                ['key' => 'intro_text', 'label' => 'Intro Text', 'type' => 'textarea'],
                ['key' => 'bio_title', 'label' => 'Bio Title', 'type' => 'text'],
                ['key' => 'bio', 'label' => 'Bio', 'type' => 'textarea'],
                ['key' => 'projects', 'label' => 'Projects', 'type' => 'repeater', 'fields' => [
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ]],
                ['key' => 'skills', 'label' => 'Skills', 'type' => 'repeater', 'fields' => [
                    ['key' => 'name', 'label' => 'Skill', 'type' => 'text'],
                ]],
                ['key' => 'contact_cta', 'label' => 'Contact CTA', 'type' => 'text'],
                ['key' => 'contact_email', 'label' => 'Contact Email', 'type' => 'email'],
                ['key' => 'contact_phone', 'label' => 'Contact Phone', 'type' => 'phone'],
                ['key' => 'location', 'label' => 'Location', 'type' => 'text'],
                ['key' => 'portfolio_url', 'label' => 'Portfolio URL', 'type' => 'url'],
            ],
            'default_content' => [
                'business_name' => 'Onlyvo Studio',
                'headline'      => 'Selected work, clear thinking, and a direct path to hire Onlyvo Studio.',
                'intro_text'    => 'A focused portfolio for showcasing projects, process, background, and contact information.',
                'bio_title'     => 'Independent portfolio',
                'bio'           => 'A focused creative practice with room for work samples, capabilities, background, and a simple contact path.',
                'projects'      => [
                    ['title' => 'Identity system', 'description' => 'A concise case-study area for outcomes, visuals, and client context.'],
                    ['title' => 'Editorial website', 'description' => 'A flexible story-driven website for publishing and lead capture.'],
                    ['title' => 'Product launch', 'description' => 'A launch page system built around sharp messaging and conversion.'],
                ],
                'skills' => [
                    ['name' => 'Brand systems'],
                    ['name' => 'Art direction'],
                    ['name' => 'Web design'],
                ],
                'contact_cta'   => 'Available for selected projects.',
                'contact_email' => 'hello@example.com',
                'contact_phone' => '+1 555 0100',
                'location'      => 'Remote and New York',
                'portfolio_url' => 'https://example.com',
            ],
        ];
    }
};
