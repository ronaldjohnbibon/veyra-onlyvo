<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function templateContent(): array
    {
        return [
            'business-website' => [
                'field_schema' => [
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
            ],
            'portfolio-website' => [
                'field_schema' => [
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
            ],
            'restaurant-website' => [
                'field_schema' => [
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
            ],
        ];
    }

    public function up(): void
    {
        Schema::table('template_catalog_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('template_catalog_items', 'field_schema')) {
                $table->json('field_schema')->nullable()->after('preview_image');
            }

            if (! Schema::hasColumn('template_catalog_items', 'default_content')) {
                $table->json('default_content')->nullable()->after('field_schema');
            }
        });

        Schema::table('templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('templates', 'content')) {
                $table->json('content')->nullable()->after('social_links');
            }
        });

        $this->seedTemplateContent();
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            if (Schema::hasColumn('templates', 'content')) {
                $table->dropColumn('content');
            }
        });

        Schema::table('template_catalog_items', function (Blueprint $table): void {
            if (Schema::hasColumn('template_catalog_items', 'default_content')) {
                $table->dropColumn('default_content');
            }

            if (Schema::hasColumn('template_catalog_items', 'field_schema')) {
                $table->dropColumn('field_schema');
            }
        });
    }

    private function seedTemplateContent(): void
    {
        foreach ($this->templateContent() as $websiteTypeSlug => $content) {
            $websiteTypeId = DB::table('website_types')->where('slug', $websiteTypeSlug)->value('id');

            if (! $websiteTypeId) {
                continue;
            }

            DB::table('template_catalog_items')
                ->where('website_type_id', $websiteTypeId)
                ->where('key', 'template-1')
                ->update([
                    'field_schema'    => json_encode($content['field_schema']),
                    'default_content' => json_encode($content['default_content']),
                    'updated_at'      => now(),
                ]);
        }
    }
};
