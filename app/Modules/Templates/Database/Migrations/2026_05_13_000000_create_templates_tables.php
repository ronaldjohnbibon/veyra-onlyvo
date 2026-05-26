<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
    }

    public function down(): void
    {
        Schema::dropIfExists('template_section_designs');
        Schema::dropIfExists('template_sections');
        Schema::dropIfExists('templates');
    }
};
