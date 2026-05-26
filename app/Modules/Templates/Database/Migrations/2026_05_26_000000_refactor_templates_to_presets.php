<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('templates') && ! Schema::hasColumn('templates', 'template_key')) {
            Schema::table('templates', function (Blueprint $table): void {
                $table->string('template_key', 80)->default('business-classic')->after('slug');
                $table->index('template_key');
            });
        }

        Schema::dropIfExists('template_section_designs');
        Schema::dropIfExists('template_sections');
    }

    public function down(): void
    {
        //
    }
};
