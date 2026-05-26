<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @return array<string, array<int, array<string, string|null>>>
     */
    private function defaultTemplates(): array
    {
        return [
            'business-website' => [
                [
                    'key'           => 'template-1',
                    'name'          => 'Business Classic',
                    'description'   => 'A polished company website with hero, services, proof, and contact areas.',
                    'preview_image' => null,
                ],
            ],
            'portfolio-website' => [
                [
                    'key'           => 'template-1',
                    'name'          => 'Portfolio Studio',
                    'description'   => 'A clean personal portfolio with project highlights and contact details.',
                    'preview_image' => null,
                ],
            ],
            'restaurant-website' => [
                [
                    'key'           => 'template-1',
                    'name'          => 'Restaurant Showcase',
                    'description'   => 'A warm restaurant website with menu highlights, hours, and booking prompts.',
                    'preview_image' => null,
                ],
            ],
        ];
    }

    public function up(): void
    {
        Schema::create('template_catalog_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('website_type_id')->constrained('website_types')->restrictOnDelete();
            $table->string('key', 80);
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('preview_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['website_type_id', 'key']);
            $table->index(['website_type_id', 'is_active']);
        });

        $this->seedDefaultTemplates();
    }

    public function down(): void
    {
        Schema::dropIfExists('template_catalog_items');
    }

    private function seedDefaultTemplates(): void
    {
        $now = now();

        // Seed the catalog records for the Vue templates that already exist.
        foreach ($this->defaultTemplates() as $websiteTypeSlug => $templates) {
            $websiteTypeId = DB::table('website_types')->where('slug', $websiteTypeSlug)->value('id');

            if (! $websiteTypeId) {
                continue;
            }

            foreach ($templates as $template) {
                DB::table('template_catalog_items')->updateOrInsert(
                    [
                        'website_type_id' => $websiteTypeId,
                        'key'             => $template['key'],
                    ],
                    [
                        'id'            => (string) Str::uuid(),
                        'name'          => $template['name'],
                        'description'   => $template['description'],
                        'preview_image' => $template['preview_image'],
                        'is_active'     => true,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ],
                );
            }
        }
    }
};
