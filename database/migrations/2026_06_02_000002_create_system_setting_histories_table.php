<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_setting_histories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('setting_key', 150)->index();
            $table->json('previous_value')->nullable();
            $table->json('new_value')->nullable();
            $table->unsignedBigInteger('changed_by_user_id')->nullable()->index();
            $table->string('changed_by_name')->nullable();
            $table->string('changed_by_email')->nullable();
            $table->timestamp('changed_at')->index();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_setting_histories');
    }
};
