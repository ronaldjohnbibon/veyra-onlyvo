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
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
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
            $table->string('name');
            $table->string('design_key', 80);
            $table->text('preview_image');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['section_type', 'design_key']);
            $table->index(['section_type', 'is_active']);
        });

        $now  = now();
        $rows = [];

        foreach ($this->designs() as $design) {
            $rows[] = array_merge($design, [
                'id'            => (string) Str::uuid(),
                'preview_image' => $this->previewImage($design['name']),
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        DB::table('template_section_designs')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('template_section_designs');
        Schema::dropIfExists('template_sections');
        Schema::dropIfExists('templates');
    }

    /**
     * @return array<int, array{section_type: string, name: string, design_key: string}>
     */
    private function designs(): array
    {
        return [
            ['section_type' => 'header', 'name' => 'Header Design 1', 'design_key' => 'header-1'],
            ['section_type' => 'header', 'name' => 'Header Design 2', 'design_key' => 'header-2'],
            ['section_type' => 'hero', 'name' => 'Hero Design 1', 'design_key' => 'hero-1'],
            ['section_type' => 'hero', 'name' => 'Hero Design 2', 'design_key' => 'hero-2'],
            ['section_type' => 'about', 'name' => 'About Design 1', 'design_key' => 'about-1'],
            ['section_type' => 'about', 'name' => 'About Design 2', 'design_key' => 'about-2'],
            ['section_type' => 'services', 'name' => 'Services Design 1', 'design_key' => 'services-1'],
            ['section_type' => 'services', 'name' => 'Services Design 2', 'design_key' => 'services-2'],
            ['section_type' => 'products', 'name' => 'Products Design 1', 'design_key' => 'products-1'],
            ['section_type' => 'products', 'name' => 'Products Design 2', 'design_key' => 'products-2'],
            ['section_type' => 'portfolio', 'name' => 'Portfolio Design 1', 'design_key' => 'portfolio-1'],
            ['section_type' => 'portfolio', 'name' => 'Portfolio Design 2', 'design_key' => 'portfolio-2'],
            ['section_type' => 'faq', 'name' => 'FAQ Design 1', 'design_key' => 'faq-1'],
            ['section_type' => 'faq', 'name' => 'FAQ Design 2', 'design_key' => 'faq-2'],
            ['section_type' => 'contact', 'name' => 'Contact Design 1', 'design_key' => 'contact-1'],
            ['section_type' => 'contact', 'name' => 'Contact Design 2', 'design_key' => 'contact-2'],
            ['section_type' => 'footer', 'name' => 'Footer Design 1', 'design_key' => 'footer-1'],
            ['section_type' => 'footer', 'name' => 'Footer Design 2', 'design_key' => 'footer-2'],
        ];
    }

    private function previewImage(string $label): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180" viewBox="0 0 320 180">'
            .'<rect width="320" height="180" fill="#f8fafc"/>'
            .'<rect x="22" y="22" width="276" height="136" rx="8" fill="#ffffff" stroke="#cbd5e1"/>'
            .'<rect x="44" y="48" width="112" height="14" rx="3" fill="#14b8a6"/>'
            .'<rect x="44" y="76" width="226" height="8" rx="3" fill="#94a3b8"/>'
            .'<rect x="44" y="96" width="178" height="8" rx="3" fill="#cbd5e1"/>'
            .'<rect x="224" y="112" width="46" height="22" rx="5" fill="#0f766e"/>'
            .'<text x="44" y="142" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#0f172a">'
            .htmlspecialchars($label, ENT_QUOTES)
            .'</text></svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }
};
