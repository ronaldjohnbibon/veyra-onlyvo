<?php

use App\Modules\Templates\Database\TemplateCatalogMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TemplateCatalogMigration::upsert('landing-page', $this->item());
    }

    public function down(): void
    {
        TemplateCatalogMigration::delete('landing-page', 'template-1');
    }

    /**
     * @return array<string, mixed>
     */
    private function item(): array
    {
        return [
            'key'           => 'template-1',
            'name'          => 'Infinite Loop',
            'description'   => 'A parallax landing page with service highlights, testimonials, gallery, and contact sections.',
            'preview_image' => '/template-assets/infinite-loop/infinite-loop-01.jpg',
            'field_schema'  => [
                ['key' => 'business_name', 'label' => 'Brand Name', 'type' => 'text', 'required' => true],
                ['key' => 'nav_home_label', 'label' => 'Home Nav Label', 'type' => 'text'],
                ['key' => 'nav_about_label', 'label' => 'About Nav Label', 'type' => 'text'],
                ['key' => 'nav_testimonials_label', 'label' => 'Testimonials Nav Label', 'type' => 'text'],
                ['key' => 'nav_gallery_label', 'label' => 'Gallery Nav Label', 'type' => 'text'],
                ['key' => 'nav_contact_label', 'label' => 'Contact Nav Label', 'type' => 'text'],
                ['key' => 'hero_title', 'label' => 'Hero Title', 'type' => 'text', 'required' => true],
                ['key' => 'hero_subtitle', 'label' => 'Hero Subtitle', 'type' => 'textarea'],
                ['key' => 'hero_image', 'label' => 'Hero Background Image', 'type' => 'image'],
                ['key' => 'intro_title', 'label' => 'Intro Title', 'type' => 'text'],
                ['key' => 'intro_text', 'label' => 'Intro Text', 'type' => 'textarea'],
                ['key' => 'features', 'label' => 'Feature Blocks', 'type' => 'repeater', 'fields' => [
                    ['key' => 'icon', 'label' => 'Icon', 'type' => 'select', 'options' => [
                        ['label' => 'Analytics', 'value' => 'analytics'],
                        ['label' => 'Support', 'value' => 'support'],
                        ['label' => 'Security', 'value' => 'security'],
                        ['label' => 'Community', 'value' => 'community'],
                    ]],
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['key' => 'cta_label', 'label' => 'CTA Label', 'type' => 'text'],
                    ['key' => 'cta_link', 'label' => 'CTA Link', 'type' => 'text'],
                ]],
                ['key' => 'testimonials_title', 'label' => 'Testimonials Title', 'type' => 'text'],
                ['key' => 'testimonials_intro', 'label' => 'Testimonials Intro', 'type' => 'textarea'],
                ['key' => 'testimonials_background_image', 'label' => 'Testimonials Background Image', 'type' => 'image'],
                ['key' => 'testimonials', 'label' => 'Testimonials', 'type' => 'repeater', 'fields' => [
                    ['key' => 'image', 'label' => 'Avatar Image', 'type' => 'image'],
                    ['key' => 'quote', 'label' => 'Quote', 'type' => 'textarea'],
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
                    ['key' => 'role', 'label' => 'Role', 'type' => 'text'],
                ]],
                ['key' => 'gallery_title', 'label' => 'Gallery Title', 'type' => 'text'],
                ['key' => 'gallery_intro', 'label' => 'Gallery Intro', 'type' => 'textarea'],
                ['key' => 'gallery_items', 'label' => 'Gallery Items', 'type' => 'repeater', 'fields' => [
                    ['key' => 'image', 'label' => 'Thumbnail Image', 'type' => 'image'],
                    ['key' => 'full_image', 'label' => 'Full Image Link', 'type' => 'image'],
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'highlight', 'label' => 'Highlight', 'type' => 'text'],
                ]],
                ['key' => 'contact_title', 'label' => 'Contact Title', 'type' => 'text'],
                ['key' => 'contact_text', 'label' => 'Contact Text', 'type' => 'textarea'],
                ['key' => 'contact_background_image', 'label' => 'Contact Background Image', 'type' => 'image'],
                ['key' => 'submit_label', 'label' => 'Submit Button Label', 'type' => 'text'],
                ['key' => 'chat_label', 'label' => 'Chat Label', 'type' => 'text'],
                ['key' => 'chat_url', 'label' => 'Chat URL', 'type' => 'url'],
                ['key' => 'contact_email', 'label' => 'Contact Email', 'type' => 'email'],
                ['key' => 'contact_phone', 'label' => 'Contact Phone', 'type' => 'phone'],
                ['key' => 'contact_location', 'label' => 'Contact Location', 'type' => 'text'],
                ['key' => 'footer_text', 'label' => 'Footer Text', 'type' => 'text'],
            ],
            'default_content' => [
                'business_name'          => 'Infinite Loop',
                'nav_home_label'         => 'Home',
                'nav_about_label'        => 'What We Do',
                'nav_testimonials_label' => 'Testimonials',
                'nav_gallery_label'      => 'Gallery',
                'nav_contact_label'      => 'Contact',
                'hero_title'             => 'Infinite Loop',
                'hero_subtitle'          => 'Parallax landing page for a focused digital campaign.',
                'hero_image'             => '/template-assets/infinite-loop/infinite-loop-01.jpg',
                'intro_title'            => 'What We Do',
                'intro_text'             => 'A flexible landing page with a full-screen visual hero, service highlights, customer quotes, gallery images, and a direct contact area.',
                'features'               => [
                    ['icon' => 'analytics', 'title' => 'Market Analysis', 'description' => 'Study your audience, sharpen the offer, and turn campaign data into clear next steps.', 'cta_label' => '', 'cta_link' => '#testimonials'],
                    ['icon' => 'support', 'title' => 'Fast Support', 'description' => 'Give visitors a direct path to ask questions, request help, and keep momentum moving.', 'cta_label' => '', 'cta_link' => '#testimonials'],
                    ['icon' => 'security', 'title' => 'Top Security', 'description' => 'Present privacy, reliability, and trust signals with a confident landing-page structure.', 'cta_label' => 'Learn More', 'cta_link' => '#testimonials'],
                    ['icon' => 'community', 'title' => 'Social Work', 'description' => 'Show community programs, partnerships, and social proof in a polished public layout.', 'cta_label' => 'Details', 'cta_link' => '#testimonials'],
                ],
                'testimonials_title'            => 'Testimonials',
                'testimonials_intro'            => 'Share customer quotes, team praise, or campaign proof in a smooth horizontal section.',
                'testimonials_background_image' => '/template-assets/infinite-loop/infinite-loop-02.jpg',
                'testimonials'                  => [
                    ['image' => '/template-assets/infinite-loop/testimonial-img-01.jpg', 'quote' => 'The landing page feels modern, moves smoothly, and gives our visitors a clear reason to continue.', 'name' => 'Catherine Win', 'role' => 'Designer'],
                    ['image' => '/template-assets/infinite-loop/testimonial-img-02.jpg', 'quote' => 'The sections are easy to scan and the parallax moments make the page feel memorable.', 'name' => 'Dual Rocker', 'role' => 'CEO'],
                    ['image' => '/template-assets/infinite-loop/testimonial-img-03.jpg', 'quote' => 'We launched quickly with strong copy, useful contact paths, and a gallery that shows the work.', 'name' => 'Sandar Soft', 'role' => 'Marketing'],
                    ['image' => '/template-assets/infinite-loop/testimonial-img-04.jpg', 'quote' => 'A focused layout that keeps the message simple while still feeling rich and complete.', 'name' => 'Oliva Htoo', 'role' => 'Designer'],
                ],
                'gallery_title' => 'Gallery',
                'gallery_intro' => 'Use this area for product images, campaign shots, office scenes, event highlights, or visual proof.',
                'gallery_items' => [
                    ['image' => '/template-assets/infinite-loop/gallery-tn-01.jpg', 'full_image' => '/template-assets/infinite-loop/gallery-img-01.jpg', 'title' => 'Physical Health', 'highlight' => 'Exercise!'],
                    ['image' => '/template-assets/infinite-loop/gallery-tn-02.jpg', 'full_image' => '/template-assets/infinite-loop/gallery-img-02.jpg', 'title' => 'Rain on Glass', 'highlight' => 'Second Image'],
                    ['image' => '/template-assets/infinite-loop/gallery-tn-03.jpg', 'full_image' => '/template-assets/infinite-loop/gallery-img-03.jpg', 'title' => 'Sea View', 'highlight' => 'Mega City'],
                    ['image' => '/template-assets/infinite-loop/gallery-tn-04.jpg', 'full_image' => '/template-assets/infinite-loop/gallery-img-04.jpg', 'title' => 'Dream Girl', 'highlight' => 'Thoughts'],
                    ['image' => '/template-assets/infinite-loop/gallery-tn-05.jpg', 'full_image' => '/template-assets/infinite-loop/gallery-img-05.jpg', 'title' => 'Workstation', 'highlight' => 'Offices'],
                    ['image' => '/template-assets/infinite-loop/gallery-tn-06.jpg', 'full_image' => '/template-assets/infinite-loop/gallery-img-06.jpg', 'title' => 'Just Above', 'highlight' => 'The City'],
                ],
                'contact_title'            => 'Contact Us',
                'contact_text'             => 'Invite visitors to start a conversation, request details, or send a message from the page.',
                'contact_background_image' => '/template-assets/infinite-loop/infinite-loop-03.jpg',
                'submit_label'             => 'Submit',
                'chat_label'               => 'Chat Online',
                'chat_url'                 => 'https://example.com',
                'contact_email'            => 'mail@company.com',
                'contact_phone'            => '255-662-5566',
                'contact_location'         => 'Our Location',
                'footer_text'              => 'Copyright 2026 Infinite Loop',
            ],
        ];
    }
};
