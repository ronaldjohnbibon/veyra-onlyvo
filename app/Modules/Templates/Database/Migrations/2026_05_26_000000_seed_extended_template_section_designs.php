<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->designs() as $design) {
            DB::table('template_section_designs')->updateOrInsert(
                [
                    'section_type' => $design['section_type'],
                    'design_key'   => $design['design_key'],
                ],
                [
                    'id'                   => (string) Str::uuid(),
                    'section_label'        => $design['section_label'],
                    'name'                 => $design['name'],
                    'preview_image'        => $this->previewImage($design['name']),
                    'fields_json'          => $this->encode($design['fields']),
                    'default_content_json' => $this->encode($design['default_content']),
                    'default_enabled'      => $design['default_enabled'],
                    'default_sort_order'   => $design['default_sort_order'],
                    'is_active'            => true,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        foreach ($this->designs() as $design) {
            DB::table('template_section_designs')
                ->where('section_type', $design['section_type'])
                ->where('design_key', $design['design_key'])
                ->delete();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function designs(): array
    {
        return [
            $this->design('cta', 'Call To Action', 'CTA Design 1', 'cta-1', 10, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
                $this->field('cta_label', 'Button Label'),
                $this->field('button_link', 'Button Link', 'url'),
            ], [
                'title'       => 'Ready to get started?',
                'body'        => 'Invite visitors to take the next step with a clear, focused offer.',
                'cta_label'   => 'Contact us',
                'button_link' => '#contact',
            ]),
            $this->design('testimonials', 'Testimonials', 'Testimonials Design 1', 'testimonials-1', 11, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Reviews', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'What clients say',
                'subtitle' => 'A few words from people we have helped.',
                'items'    => ['Reliable, clear, and easy to work with.', 'The team understood exactly what we needed.', 'A polished experience from start to finish.'],
            ]),
            $this->design('team', 'Team', 'Team Design 1', 'team-1', 12, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Team Members', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'Our team',
                'subtitle' => 'The people behind the work.',
                'items'    => ['Alex Carter - Founder', 'Maya Lee - Operations', 'Jordan Smith - Customer Success'],
            ]),
            $this->design('pricing', 'Pricing', 'Pricing Design 1', 'pricing-1', 13, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Plans', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'Plans',
                'subtitle' => 'Simple options for different needs.',
                'items'    => ['Starter - $99', 'Growth - $249', 'Premium - Custom'],
            ]),
            $this->design('booking', 'Booking', 'Booking Design 1', 'booking-1', 14, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
                $this->field('schedule', 'Availability'),
                $this->field('cta_label', 'Button Label'),
            ], [
                'title'     => 'Book an appointment',
                'body'      => 'Choose a time to talk with our team about your goals.',
                'schedule'  => 'Monday to Friday, 9 AM - 5 PM',
                'cta_label' => 'Request a time',
            ]),
            $this->design('clients', 'Clients', 'Clients Design 1', 'clients-1', 15, [
                $this->field('title', 'Title'),
                $this->field('items', 'Client Names', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Trusted by',
                'items' => ['Northstar', 'Acme Co.', 'Brightline', 'Summit'],
            ]),
            $this->design('statistics', 'Statistics', 'Statistics Design 1', 'statistics-1', 16, [
                $this->field('title', 'Title'),
                $this->field('stats', 'Statistics', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Achievements',
                'stats' => ['10+ years', '250+ projects', '98% satisfaction'],
            ]),
            $this->design('process', 'Process', 'Process Design 1', 'process-1', 17, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Steps', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'How it works',
                'subtitle' => 'A simple path from first call to finished work.',
                'items'    => ['Discover', 'Plan', 'Build', 'Launch'],
            ]),
            $this->design('gallery', 'Gallery', 'Gallery Design 1', 'gallery-1', 18, [
                $this->field('title', 'Title'),
                $this->field('items', 'Image URLs', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Gallery',
                'items' => [
                    'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=900&q=80',
                ],
            ]),
            $this->design('newsletter', 'Newsletter', 'Newsletter Design 1', 'newsletter-1', 19, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
                $this->field('email_placeholder', 'Email Placeholder'),
                $this->field('cta_label', 'Button Label'),
            ], [
                'title'             => 'Stay in the loop',
                'body'              => 'Share updates, offers, and useful notes with subscribers.',
                'email_placeholder' => 'Email address',
                'cta_label'         => 'Subscribe',
            ]),
            $this->design('location', 'Location', 'Location Design 1', 'location-1', 20, [
                $this->field('title', 'Title'),
                $this->field('body', 'Description', 'textarea', 'md:col-span-2'),
                $this->field('map_url', 'Map Link', 'url'),
            ], [
                'title'   => 'Visit us',
                'body'    => 'Find our office or reach out before you arrive.',
                'map_url' => 'https://maps.google.com',
            ]),
            $this->design('social_links', 'Social Links', 'Social Links Design 1', 'social-links-1', 21, [
                $this->field('title', 'Title'),
                $this->field('items', 'Social Links', 'list', 'md:col-span-2'),
            ], [
                'title' => 'Connect with us',
                'items' => ['LinkedIn', 'Instagram', 'Facebook'],
            ]),
            $this->design('features', 'Features', 'Features Design 1', 'features-1', 22, [
                $this->field('title', 'Title'),
                $this->field('subtitle', 'Subtitle'),
                $this->field('items', 'Features', 'list', 'md:col-span-2'),
            ], [
                'title'    => 'Why choose us',
                'subtitle' => 'Practical strengths customers notice quickly.',
                'items'    => ['Clear communication', 'Reliable delivery', 'Flexible support'],
            ]),
            $this->design('mission_vision', 'Mission / Vision', 'Mission Vision Design 1', 'mission-vision-1', 23, [
                $this->field('title', 'Title'),
                $this->field('body', 'Mission', 'textarea', 'md:col-span-2'),
                $this->field('subtitle', 'Vision'),
            ], [
                'title'    => 'Mission and vision',
                'body'     => 'Our mission is to make quality service simple, useful, and dependable.',
                'subtitle' => 'Our vision is to become the trusted partner customers return to year after year.',
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
            'default_enabled'    => false,
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

    private function previewImage(string $label): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180" viewBox="0 0 320 180">'
            .'<rect width="320" height="180" fill="#f8fafc"/>'
            .'<rect x="22" y="22" width="276" height="136" rx="8" fill="#ffffff" stroke="#cbd5e1"/>'
            .'<rect x="44" y="48" width="112" height="14" rx="3" fill="#14b8a6"/>'
            .'<rect x="44" y="76" width="226" height="8" rx="3" fill="#94a3b8"/>'
            .'<rect x="44" y="96" width="178" height="8" rx="3" fill="#cbd5e1"/>'
            .'<rect x="44" y="116" width="80" height="18" rx="4" fill="#0f766e"/>'
            .'<text x="44" y="146" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#0f172a">'
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
