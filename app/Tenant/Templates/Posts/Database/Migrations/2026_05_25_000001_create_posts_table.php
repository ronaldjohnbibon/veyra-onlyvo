<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug', 160);
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->text('featured_image')->nullable();
            $table->string('seo_title', 180)->nullable();
            $table->text('meta_description')->nullable();
            $table->json('tags')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['template_id', 'slug']);
            $table->index(['template_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
