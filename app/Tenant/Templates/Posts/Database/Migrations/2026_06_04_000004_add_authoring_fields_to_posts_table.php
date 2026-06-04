<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->text('excerpt')->nullable()->after('content');
            $table->string('seo_title', 180)->nullable()->after('featured_image');
            $table->text('meta_description')->nullable()->after('seo_title');
            $table->json('tags')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn(['excerpt', 'seo_title', 'meta_description', 'tags']);
        });
    }
};
