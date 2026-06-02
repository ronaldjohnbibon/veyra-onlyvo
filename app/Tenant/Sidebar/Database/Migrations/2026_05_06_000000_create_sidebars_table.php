<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sidebars', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->boolean('is_admin')->default(false);
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->restrictOnDelete();
            $table->string('name')->default('default');
            $table->text('description')->nullable();
            $table->json('data');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_admin', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebars');
    }
};
