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
            $table->string('template_key', 80)->default('template-1');
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
            $table->index(['template_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
