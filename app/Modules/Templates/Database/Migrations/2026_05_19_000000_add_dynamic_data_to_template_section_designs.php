<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_section_designs', function (Blueprint $table): void {
            if (! Schema::hasColumn('template_section_designs', 'section_label')) {
                $table->string('section_label')->nullable()->after('section_type');
            }

            if (! Schema::hasColumn('template_section_designs', 'fields_json')) {
                $table->json('fields_json')->nullable()->after('preview_image');
            }

            if (! Schema::hasColumn('template_section_designs', 'default_content_json')) {
                $table->json('default_content_json')->nullable()->after('fields_json');
            }

            if (! Schema::hasColumn('template_section_designs', 'default_enabled')) {
                $table->boolean('default_enabled')->default(true)->after('default_content_json');
            }

            if (! Schema::hasColumn('template_section_designs', 'default_sort_order')) {
                $table->unsignedSmallInteger('default_sort_order')->default(0)->after('default_enabled');
            }
        });

        foreach ($this->designs() as $design) {
            DB::table('template_section_designs')
                ->where('section_type', $design['section_type'])
                ->where('design_key', $design['design_key'])
                ->update([
                    'section_label'        => $design['section_label'],
                    'fields_json'          => $this->encode($design['fields']),
                    'default_content_json' => $this->encode($design['default_content']),
                    'default_enabled'      => $design['default_enabled'],
                    'default_sort_order'   => $design['default_sort_order'],
                    'updated_at'           => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('template_section_designs', function (Blueprint $table): void {
            foreach ([
                'section_label',
                'fields_json',
                'default_content_json',
                'default_enabled',
                'default_sort_order',
            ] as $column) {
                if (Schema::hasColumn('template_section_designs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function designs(): array
    {
        return [
            $this->design('header', 'Header', 'Header Design 1', 'header-1', 1, true, [
                $this->field('tagline', 'Tagline'),
            ], [
                'tagline' => 'Business website',
            ]),
            $this->design('header', 'Header', 'Header Design 2', 'header-2', 1, true, [
                $this->field('tagline', 'Tagline'),
                $this->field('cta_label', 'Button Label'),
            ], [
                'tagline'   => 'Business website',
                'cta_label' => 'Contact us',
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
    ): array {
        return [
            'section_type'       => $sectionType,
            'section_label'      => $sectionLabel,
            'name'               => $name,
            'design_key'         => $designKey,
            'fields'             => $fields,
            'default_content'    => $defaultContent,
            'default_enabled'    => $defaultEnabled,
            'default_sort_order' => $defaultSortOrder,
        ];
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

    /**
     * @param  array<string|int, mixed>  $value
     */
    private function encode(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
};
