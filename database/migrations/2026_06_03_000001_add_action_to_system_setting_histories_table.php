<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_setting_histories') || Schema::hasColumn('system_setting_histories', 'action')) {
            return;
        }

        Schema::table('system_setting_histories', function (Blueprint $table): void {
            $table->string('action', 50)->default('updated')->after('setting_key')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_setting_histories') || ! Schema::hasColumn('system_setting_histories', 'action')) {
            return;
        }

        Schema::table('system_setting_histories', function (Blueprint $table): void {
            $table->dropColumn('action');
        });
    }
};
