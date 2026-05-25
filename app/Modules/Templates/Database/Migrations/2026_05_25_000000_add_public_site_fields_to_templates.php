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
        Schema::table('templates', function (Blueprint $table): void {
            $table->string('slug', 120)->nullable()->after('name');
            $table->boolean('is_default')->default(false)->after('status');

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_default']);
        });

        $usedSlugs = [];

        foreach (DB::table('templates')->orderBy('tenant_id')->orderBy('created_at')->get(['id', 'tenant_id', 'name']) as $template) {
            $tenantId = (string) $template->tenant_id;
            $baseSlug = Str::slug((string) $template->name) ?: 'site';
            $slug     = $baseSlug;
            $index    = 2;

            while (isset($usedSlugs[$tenantId][$slug])) {
                $slug = $baseSlug.'-'.$index++;
            }

            $usedSlugs[$tenantId][$slug] = true;

            DB::table('templates')
                ->where('id', $template->id)
                ->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'slug']);
            $table->dropIndex(['tenant_id', 'is_default']);
            $table->dropColumn(['slug', 'is_default']);
        });
    }
};
