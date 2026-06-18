<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        Schema::create('template_catalog_item_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('template_catalog_item_id');
            $table->unsignedInteger('version');
            $table->string('action', 40)->default('updated');
            $table->text('changelog')->nullable();
            $table->json('snapshot');
            $table->foreignId('created_by_user_id')->nullable();
            $table->string('created_by_name')->nullable();
            $table->string('created_by_email')->nullable();
            $table->timestamps();

            $table->foreign('template_catalog_item_id', 'tciv_item_fk')
                ->references('id')
                ->on('template_catalog_items')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'tciv_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->unique(['template_catalog_item_id', 'version'], 'tciv_item_version_unique');
            $table->index(['template_catalog_item_id', 'created_at'], 'tciv_item_created_idx');
        });

        Schema::create('template_cta_submissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('cta_type', 60);
            $table->json('payload');
            $table->string('status', 30)->default('new');
            $table->timestamps();

            $table->index(['template_id', 'cta_type']);
            $table->index(['template_id', 'status']);
        });

        $this->seedWebsiteTypes();
    }

    public function down(): void
    {
        Schema::dropIfExists('template_cta_submissions');
        Schema::dropIfExists('template_catalog_item_versions');
        Schema::dropIfExists('template_catalog_items');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('website_types');
    }

    private function seedWebsiteTypes(): void
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
    }
};
