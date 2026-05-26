<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('template_section_designs');
        Schema::dropIfExists('template_sections');
        Schema::dropIfExists('template_designs');

        if (Schema::hasTable('website_types') && Schema::hasColumn('website_types', 'sort_order')) {
            Schema::table('website_types', function (Blueprint $table): void {
                $table->dropColumn('sort_order');
            });
        }

        if (Schema::hasTable('templates')) {
            // Existing tenant sites should point at the first complete template.
            DB::table('templates')
                ->whereNull('template_key')
                ->orWhere('template_key', 'business-classic')
                ->update(['template_key' => 'template-1']);
        }
    }

    public function down(): void
    {
        //
    }
};
