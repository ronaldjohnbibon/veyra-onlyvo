<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('name');
            $table->string('slug', 120)->nullable();
            $table->string('business_name');
            $table->text('logo');
            $table->json('contact_info');
            $table->json('social_links');
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
        });

        Schema::create('template_sections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('section_type', 40);
            $table->string('design_key', 80);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->json('content_json');
            $table->timestamps();

            $table->unique(['template_id', 'section_type']);
            $table->index(['template_id', 'is_enabled', 'sort_order']);
        });

        Schema::create('template_section_designs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('section_type', 40);
            $table->string('section_label')->nullable();
            $table->string('name');
            $table->string('design_key', 80);
            $table->text('preview_image');
            $table->json('fields_json')->nullable();
            $table->json('default_content_json')->nullable();
            $table->boolean('default_enabled')->default(true);
            $table->unsignedSmallInteger('default_sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['section_type', 'design_key']);
            $table->index(['section_type', 'is_active']);
        });

        $now  = now();
        $rows = [];

        foreach ($this->designs() as $design) {
            $row = array_merge($design, [
                'id'                   => (string) Str::uuid(),
                'preview_image'        => $this->previewImage($design['name'], $design['preview_variant'] ?? 'default'),
                'fields_json'          => $this->encode($design['fields']),
                'default_content_json' => $this->encode($design['default_content']),
                'is_active'            => true,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);

            unset($row['fields'], $row['default_content'], $row['preview_variant']);

            $rows[] = $row;
        }

        // Seed built-in section designs for fresh installs.
        DB::table('template_section_designs')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('template_section_designs');
        Schema::dropIfExists('template_sections');
        Schema::dropIfExists('templates');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function designs(): array
    {
        return array_merge($this->coreDesigns(), $this->extendedDesigns());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function coreDesigns(): array
    {
        return [
            $this->design('header', 'Header', 'Header Design 1', 'header-1', 1, true, [
                $this->field('tagline', 'Tagline'),
                $this->field('cta_label', 'Button Label'),
                $this->field('button_link', 'Button Link', 'url'),
            ], [
                'tagline'     => 'Business website',
                'cta_label'   => '',
                'button_link' => '#template-section-contact',
            ]),
            $this->design('header', 'Header', 'Header Design 2', 'header-2', 1, true, [
                $this->field('tagline', 'Tagline'),
                $this->field('cta_label', 'Button Label'),
                $this->field('button_link', 'Button Link', 'url'),
            ], [
                'tagline'     => 'Business website',
                'cta_label'   => 'Contact us',
                'button_link' => '#template-section-contact',
            ]),
            $this->design('hero', 'Hero', 'Hero Design 1', 'hero-1', 2, true, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('image', 'Image URL', 'url', 'md:col-span-2'),
                $this->field('cta_label', 'Button Label'),
            ], [
                'title'     => 'Build something customers remember',
                'subtitle'  => 'A clear, polished intro for your business and offer.',
                'image'     => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=80',
                'cta_label' => 'Start today',
            ]),
            $this->design('hero', 'Hero', 'Hero Design 2', 'hero-2', 2, true, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('image', 'Image URL', 'url', 'md:col-span-2'),
                $this->field('cta_label', 'Button Label'),
            ], [
                'title'     => 'Build something customers remember',
                'subtitle'  => 'A clear, polished intro for your business and offer.',
                'image'     => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=80',
                'cta_label' => 'Start today',
            ]),
            $this->design('about', 'About', 'About Design 1', 'about-1', 3, true, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
                $this->field('image', 'Image URL', 'url', 'md:col-span-2'),
                $this->field('cta_label', 'Button Label'),
                $this->field('button_link', 'Button Link', 'url'),
            ], [
                'title'       => 'About our business',
                'body'        => 'Share the story, values, and strengths that make your company easy to trust.',
                'image'       => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=900&q=80',
                'cta_label'   => 'Learn more',
                'button_link' => '#contact',
            ]),
            $this->design('about', 'About', 'About Design 2', 'about-2', 3, true, [
                $this->field('subtitle', 'Subtitle'),
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
                $this->field('left_image', 'Left Image URL', 'url'),
                $this->field('right_image', 'Right Image URL', 'url'),
                $this->field('stats', 'Stats', 'list', 'md:col-span-2'),
            ], [
                'subtitle'    => 'About',
                'title'       => 'About our business',
                'body'        => 'Share the story, values, and strengths that make your company easy to trust.',
                'left_image'  => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=900&q=80',
                'right_image' => 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=900&q=80',
                'stats'       => ['10+ years', '120 projects', '24/7 support'],
            ]),
            $this->design('services', 'Services', 'Services Design 1', 'services-1', 4, true, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Content Items', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'Services',
                'subtitle' => 'What customers can hire you to do.',
                'items'    => ['Strategy', 'Implementation', 'Support'],
            ]),
            $this->design('services', 'Services', 'Services Design 2', 'services-2', 4, true, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Content Items', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'Services',
                'subtitle' => 'What customers can hire you to do.',
                'items'    => ['Strategy', 'Implementation', 'Support'],
            ]),
            $this->design('products', 'Products', 'Products Design 1', 'products-1', 5, false, [
                $this->field('title', 'Title'),
                $this->field('items', 'Content Items', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Products',
                'items' => ['Product One', 'Product Two', 'Product Three'],
            ]),
            $this->design('products', 'Products', 'Products Design 2', 'products-2', 5, false, [
                $this->field('title', 'Title'),
                $this->field('items', 'Content Items', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Products',
                'items' => ['Product One', 'Product Two', 'Product Three'],
            ]),
            $this->design('portfolio', 'Portfolio', 'Portfolio Design 1', 'portfolio-1', 6, false, [
                $this->field('title', 'Title'),
                $this->field('items', 'Content Items', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Portfolio',
                'items' => ['Client Launch', 'Brand Refresh', 'Growth Campaign'],
            ]),
            $this->design('portfolio', 'Portfolio', 'Portfolio Design 2', 'portfolio-2', 6, false, [
                $this->field('title', 'Title'),
                $this->field('items', 'Content Items', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Portfolio',
                'items' => ['Client Launch', 'Brand Refresh', 'Growth Campaign'],
            ]),
            $this->design('faq', 'FAQ', 'FAQ Design 1', 'faq-1', 7, true, [
                $this->field('title', 'Title'),
                $this->field('body', 'Answer', 'textarea', 'md:col-span-2'),
                $this->field('items', 'Questions', 'list', 'md:col-span-2'),
            ], [
                'title' => 'FAQ',
                'body'  => 'Add a short answer here.',
                'items' => ['How do we get started?', 'What does the process include?', 'How soon can we launch?'],
            ]),
            $this->design('faq', 'FAQ', 'FAQ Design 2', 'faq-2', 7, true, [
                $this->field('title', 'Title'),
                $this->field('body', 'Answer', 'textarea', 'md:col-span-2'),
                $this->field('items', 'Questions', 'list', 'md:col-span-2'),
            ], [
                'title' => 'FAQ',
                'body'  => 'Add a short answer here.',
                'items' => ['How do we get started?', 'What does the process include?', 'How soon can we launch?'],
            ]),
            $this->design('contact', 'Contact', 'Contact Design 1', 'contact-1', 8, true, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
            ], [
                'title' => 'Contact us',
                'body'  => 'Tell visitors how to reach you and what to expect when they get in touch.',
            ]),
            $this->design('contact', 'Contact', 'Contact Design 2', 'contact-2', 8, true, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
            ], [
                'title' => 'Contact us',
                'body'  => 'Tell visitors how to reach you and what to expect when they get in touch.',
            ]),
            $this->design('footer', 'Footer', 'Footer Design 1', 'footer-1', 9, true, [
                $this->field('body', 'Body', 'textarea', 'md:col-span-2'),
            ], [
                'body' => 'Ready to work together?',
            ]),
            $this->design('footer', 'Footer', 'Footer Design 2', 'footer-2', 9, true, [
                $this->field('body', 'Body', 'textarea', 'md:col-span-2'),
            ], [
                'body' => 'Ready to work together?',
            ]),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extendedDesigns(): array
    {
        return [
            $this->design('cta', 'Call To Action', 'CTA Design 1', 'cta-1', 10, false, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
                $this->field('cta_label', 'Button Label'),
                $this->field('button_link', 'Button Link', 'url'),
            ], [
                'title'       => 'Ready to get started?',
                'body'        => 'Invite visitors to take the next step with a clear, focused offer.',
                'cta_label'   => 'Contact us',
                'button_link' => '#contact',
            ], 'extended'),
            $this->design('testimonials', 'Testimonials', 'Testimonials Design 1', 'testimonials-1', 11, false, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Reviews', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'What clients say',
                'subtitle' => 'A few words from people we have helped.',
                'items'    => ['Reliable, clear, and easy to work with.', 'The team understood exactly what we needed.', 'A polished experience from start to finish.'],
            ], 'extended'),
            $this->design('team', 'Team', 'Team Design 1', 'team-1', 12, false, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Team Members', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'Our team',
                'subtitle' => 'The people behind the work.',
                'items'    => ['Alex Carter - Founder', 'Maya Lee - Operations', 'Jordan Smith - Customer Success'],
            ], 'extended'),
            $this->design('pricing', 'Pricing', 'Pricing Design 1', 'pricing-1', 13, false, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Plans', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'Plans',
                'subtitle' => 'Simple options for different needs.',
                'items'    => ['Starter - $99', 'Growth - $249', 'Premium - Custom'],
            ], 'extended'),
            $this->design('booking', 'Booking', 'Booking Design 1', 'booking-1', 14, false, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
                $this->field('schedule', 'Availability'),
                $this->field('cta_label', 'Button Label'),
            ], [
                'title'     => 'Book an appointment',
                'body'      => 'Choose a time to talk with our team about your goals.',
                'schedule'  => 'Monday to Friday, 9 AM - 5 PM',
                'cta_label' => 'Request a time',
            ], 'extended'),
            $this->design('clients', 'Clients', 'Clients Design 1', 'clients-1', 15, false, [
                $this->field('title', 'Title'),
                $this->field('items', 'Client Names', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Trusted by',
                'items' => ['Northstar', 'Acme Co.', 'Brightline', 'Summit'],
            ], 'extended'),
            $this->design('statistics', 'Statistics', 'Statistics Design 1', 'statistics-1', 16, false, [
                $this->field('title', 'Title'),
                $this->field('stats', 'Statistics', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Achievements',
                'stats' => ['10+ years', '250+ projects', '98% satisfaction'],
            ], 'extended'),
            $this->design('process', 'Process', 'Process Design 1', 'process-1', 17, false, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Steps', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'How it works',
                'subtitle' => 'A simple path from first call to finished work.',
                'items'    => ['Discover', 'Plan', 'Build', 'Launch'],
            ], 'extended'),
            $this->design('gallery', 'Gallery', 'Gallery Design 1', 'gallery-1', 18, false, [
                $this->field('title', 'Title'),
                $this->field('items', 'Image URLs', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Gallery',
                'items' => [
                    'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=900&q=80',
                ],
            ], 'extended'),
            $this->design('newsletter', 'Newsletter', 'Newsletter Design 1', 'newsletter-1', 19, false, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
                $this->field('email_placeholder', 'Email Placeholder'),
                $this->field('cta_label', 'Button Label'),
            ], [
                'title'             => 'Stay in the loop',
                'body'              => 'Share updates, offers, and useful notes with subscribers.',
                'email_placeholder' => 'Email address',
                'cta_label'         => 'Subscribe',
            ], 'extended'),
            $this->design('location', 'Location', 'Location Design 1', 'location-1', 20, false, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
                $this->field('map_url', 'Map Link', 'url'),
            ], [
                'title'   => 'Visit us',
                'body'    => 'Find our office or reach out before you arrive.',
                'map_url' => 'https://maps.google.com',
            ], 'extended'),
            $this->design('social_links', 'Social Links', 'Social Links Design 1', 'social-links-1', 21, false, [
                $this->field('title', 'Title'),
                $this->field('items', 'Social Links', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Connect with us',
                'items' => ['LinkedIn', 'Instagram', 'Facebook'],
            ], 'extended'),
            $this->design('features', 'Features', 'Features Design 1', 'features-1', 22, false, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Features', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'Why choose us',
                'subtitle' => 'Practical strengths customers notice quickly.',
                'items'    => ['Clear communication', 'Reliable delivery', 'Flexible support'],
            ], 'extended'),
            $this->design('mission_vision', 'Mission / Vision', 'Mission Vision Design 1', 'mission-vision-1', 23, false, [
                $this->field('title', 'Title'),
                $this->field('body', 'Mission', 'textarea', 'md:col-span-2'),
                $this->field('subtitle', 'Vision'),
            ], [
                'title'    => 'Mission and vision',
                'body'     => 'Our mission is to make quality service simple, useful, and dependable.',
                'subtitle' => 'Our vision is to become the trusted partner customers return to year after year.',
            ], 'extended'),
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $fields
     * @param  array<string, mixed>  $defaultContent
     * @return array<string, mixed>
     */
    private function design(
        string $sectionType,
        string $sectionLabel,
        string $name,
        string $designKey,
        int $defaultSortOrder,
        bool $defaultEnabled,
        array $fields,
        array $defaultContent,
        ?string $previewVariant = null,
    ): array {
        return array_filter([
            'section_type'       => $sectionType,
            'section_label'      => $sectionLabel,
            'name'               => $name,
            'design_key'         => $designKey,
            'fields'             => $fields,
            'default_content'    => $defaultContent,
            'default_enabled'    => $defaultEnabled,
            'default_sort_order' => $defaultSortOrder,
            'preview_variant'    => $previewVariant,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, string>
     */
    private function field(
        string $key,
        string $label,
        string $type = 'text',
        ?string $className = null,
    ): array {
        return array_filter([
            'key'   => $key,
            'label' => $label,
            'type'  => $type,
            'class' => $className,
        ], fn ($value) => $value !== null);
    }

    private function previewImage(string $label, string $variant = 'default'): string
    {
        $button = $variant === 'extended'
            ? '<rect x="44" y="116" width="80" height="18" rx="4" fill="#0f766e"/>'
            : '<rect x="224" y="112" width="46" height="22" rx="5" fill="#0f766e"/>';

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180" viewBox="0 0 320 180">'
            .'<rect width="320" height="180" fill="#f8fafc"/>'
            .'<rect x="22" y="22" width="276" height="136" rx="8" fill="#ffffff" stroke="#cbd5e1"/>'
            .'<rect x="44" y="48" width="112" height="14" rx="3" fill="#14b8a6"/>'
            .'<rect x="44" y="76" width="226" height="8" rx="3" fill="#94a3b8"/>'
            .'<rect x="44" y="96" width="178" height="8" rx="3" fill="#cbd5e1"/>'
            .$button
            .'<text x="44" y="142" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#0f172a">'
            .htmlspecialchars($label, ENT_QUOTES)
            .'</text></svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }

    /**
     * @param  array<string|int, mixed>  $value
     */
    private function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
};
