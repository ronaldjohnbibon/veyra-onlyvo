<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BUSINESS_WEBSITE_ID = '11111111-1111-4111-8111-111111111111';

    /**
     * @return array<int, array<string, mixed>>
     */
    private function websiteTypes(): array
    {
        return [
            ['id' => self::BUSINESS_WEBSITE_ID, 'name' => 'Business Website', 'slug' => 'business-website', 'sort_order' => 1],
            ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Portfolio Website', 'slug' => 'portfolio-website', 'sort_order' => 2],
            ['id' => '33333333-3333-4333-8333-333333333333', 'name' => 'Landing Page', 'slug' => 'landing-page', 'sort_order' => 3],
            ['id' => '44444444-4444-4444-8444-444444444444', 'name' => 'Agency Website', 'slug' => 'agency-website', 'sort_order' => 4],
            ['id' => '55555555-5555-4555-8555-555555555555', 'name' => 'Personal Brand Website', 'slug' => 'personal-brand-website', 'sort_order' => 5],
            ['id' => '66666666-6666-4666-8666-666666666666', 'name' => 'Restaurant Website', 'slug' => 'restaurant-website', 'sort_order' => 6],
            ['id' => '77777777-7777-4777-8777-777777777777', 'name' => 'Event Website', 'slug' => 'event-website', 'sort_order' => 7],
            ['id' => '88888888-8888-4888-8888-888888888888', 'name' => 'Church / Nonprofit Website', 'slug' => 'church-nonprofit-website', 'sort_order' => 8],
            ['id' => '99999999-9999-4999-8999-999999999999', 'name' => 'Blog / Content Website', 'slug' => 'blog-content-website', 'sort_order' => 9],
            ['id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'name' => 'Resume / CV Website', 'slug' => 'resume-cv-website', 'sort_order' => 10],
            ['id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'name' => 'Real Estate Showcase Website', 'slug' => 'real-estate-showcase-website', 'sort_order' => 11],
        ];
    }

    public function up(): void
    {
        Schema::create('website_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('template_designs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('website_type_id')->constrained('website_types')->restrictOnDelete();
            $table->string('key', 80)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('preview_image')->nullable();
            $table->json('sections')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['website_type_id', 'is_active']);
        });

        Schema::table('templates', function (Blueprint $table): void {
            $table->foreignUuid('website_type_id')->nullable()->after('tenant_id')->constrained('website_types')->restrictOnDelete();
            $table->string('template_key', 80)->nullable()->default(null)->change();
            $table->index(['tenant_id', 'website_type_id']);
        });

        $now   = now();
        $types = array_map(fn (array $type): array => array_merge($type, [
            'description' => null,
            'is_active'   => true,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]), $this->websiteTypes());

        DB::table('website_types')->insert($types);

        // Existing templates are grouped under the business type during migration.
        DB::table('templates')
            ->whereNull('website_type_id')
            ->update(['website_type_id' => self::BUSINESS_WEBSITE_ID]);
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'website_type_id']);
            $table->dropForeign(['website_type_id']);
            $table->dropColumn('website_type_id');
            $table->string('template_key', 80)->nullable(false)->default('business-classic')->change();
        });

        Schema::dropIfExists('template_designs');
        Schema::dropIfExists('website_types');
    }
};
