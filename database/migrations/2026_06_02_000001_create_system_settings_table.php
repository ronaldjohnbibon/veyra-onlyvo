<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('group', 100)->index();
            $table->string('key', 150)->unique();
            $table->string('label', 150);
            $table->string('type', 50)->default('string');
            $table->json('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('system_setting_histories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('setting_key', 150)->index();
            $table->string('action', 50)->default('updated')->index();
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
        Schema::dropIfExists('system_settings');
    }
};
