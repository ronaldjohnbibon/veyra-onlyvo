<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('template_catalog_item_versions')) {
            $this->finishPartialMigration();

            return;
        }

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
    }

    public function down(): void
    {
        Schema::dropIfExists('template_catalog_item_versions');
    }

    private function finishPartialMigration(): void
    {
        Schema::table('template_catalog_item_versions', function (Blueprint $table): void {
            if (! Schema::hasIndex('template_catalog_item_versions', 'tciv_item_version_unique')) {
                $table->unique(['template_catalog_item_id', 'version'], 'tciv_item_version_unique');
            }

            if (! Schema::hasIndex('template_catalog_item_versions', 'tciv_item_created_idx')) {
                $table->index(['template_catalog_item_id', 'created_at'], 'tciv_item_created_idx');
            }
        });
    }
};
