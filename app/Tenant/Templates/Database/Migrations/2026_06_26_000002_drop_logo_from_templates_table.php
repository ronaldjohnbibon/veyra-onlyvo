<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('templates', 'logo')) {
            return;
        }

        Schema::table('templates', function (Blueprint $table): void {
            $table->dropColumn('logo');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('templates', 'logo')) {
            return;
        }

        Schema::table('templates', function (Blueprint $table): void {
            $table->text('logo')->nullable()->after('business_name');
        });
    }
};
