<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Return the website types available to templates.
     *
     * @return array<int, array<string, mixed>>
     */
    private function websiteTypes(): array
    {
        return [
            ['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'Business Website', 'slug' => 'business-website'],
            ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Portfolio Website', 'slug' => 'portfolio-website'],
            ['id' => '33333333-3333-4333-8333-333333333333', 'name' => 'Landing Page', 'slug' => 'landing-page'],
            ['id' => '44444444-4444-4444-8444-444444444444', 'name' => 'Agency Website', 'slug' => 'agency-website'],
            ['id' => '55555555-5555-4555-8555-555555555555', 'name' => 'Personal Brand Website', 'slug' => 'personal-brand-website'],
            ['id' => '66666666-6666-4666-8666-666666666666', 'name' => 'Restaurant Website', 'slug' => 'restaurant-website'],
            ['id' => '77777777-7777-4777-8777-777777777777', 'name' => 'Event Website', 'slug' => 'event-website'],
            ['id' => '88888888-8888-4888-8888-888888888888', 'name' => 'Church / Nonprofit Website', 'slug' => 'church-nonprofit-website'],
            ['id' => '99999999-9999-4999-8999-999999999999', 'name' => 'Blog / Content Website', 'slug' => 'blog-content-website'],
            ['id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'name' => 'Resume / CV Website', 'slug' => 'resume-cv-website'],
            ['id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'name' => 'Real Estate Showcase Website', 'slug' => 'real-estate-showcase-website'],
        ];
    }

    /**
     * Return the initial catalog records for Vue templates.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function catalogItems(): array
    {
        return [
            'business-website' => [
                [
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
                ],
            ],
            'portfolio-website' => [
                [
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
                ],
            ],
            'restaurant-website' => [
                [
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
                ],
            ],
        ];
    }

    public function up(): void
    {
        Schema::create('website_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignUuid('website_type_id')->nullable()->constrained('website_types')->restrictOnDelete();
            $table->string('name');
            $table->string('slug', 120)->nullable();
            $table->string('template_key', 80)->default('template-1');
            $table->string('business_name');
            $table->text('logo');
            $table->json('contact_info');
            $table->json('social_links');
            $table->json('content')->nullable();
            $table->string('font_family')->default('Inter');
            $table->string('primary_color', 20)->default('#14b8a6');
            $table->string('secondary_color', 20)->default('#0f766e');
            $table->string('background_color', 20)->default('#ffffff');
            $table->string('text_color', 20)->default('#111827');
            $table->string('status', 30)->default('draft');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'is_default']);
            $table->index(['tenant_id', 'website_type_id']);
            $table->index('template_key');
        });

        Schema::create('template_catalog_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('website_type_id')->constrained('website_types')->restrictOnDelete();
            $table->string('key', 80);
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('preview_image')->nullable();
            $table->json('field_schema')->nullable();
            $table->json('default_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['website_type_id', 'key']);
            $table->index(['website_type_id', 'is_active']);
        });

        $this->seedInitialCatalog();
    }

    public function down(): void
    {
        Schema::dropIfExists('template_catalog_items');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('website_types');
    }

    private function seedInitialCatalog(): void
    {
        $now = now();

        // Seed website types used by the template picker.
        $websiteTypes = array_map(fn (array $type): array => array_merge($type, [
            'description' => null,
            'is_active'   => true,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]), $this->websiteTypes());

        DB::table('website_types')->insert($websiteTypes);

        $websiteTypeIds = collect($websiteTypes)->pluck('id', 'slug');
        $catalogItems   = [];

        // Seed the Vue template catalog records shown to admins and tenants.
        foreach ($this->catalogItems() as $websiteTypeSlug => $items) {
            $websiteTypeId = $websiteTypeIds[$websiteTypeSlug] ?? null;

            if (! $websiteTypeId) {
                continue;
            }

            foreach ($items as $item) {
                $catalogItems[] = [
                    'id'              => (string) Str::uuid(),
                    'website_type_id' => $websiteTypeId,
                    'key'             => $item['key'],
                    'name'            => $item['name'],
                    'description'     => $item['description'],
                    'preview_image'   => $item['preview_image'],
                    'field_schema'    => json_encode($item['field_schema']),
                    'default_content' => json_encode($item['default_content']),
                    'is_active'       => true,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
        }

        DB::table('template_catalog_items')->insert($catalogItems);
    }
};
