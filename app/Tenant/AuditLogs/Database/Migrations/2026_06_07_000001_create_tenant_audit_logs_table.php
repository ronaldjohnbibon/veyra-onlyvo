<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('scope', 20)->default('tenant')->index();
            $table->uuid('tenant_id')->index();
            $table->string('category', 40)->default('activity')->index();
            $table->string('severity', 20)->default('info')->index();
            $table->string('actor_type', 40)->default('tenant');
            $table->string('actor_id')->nullable()->index();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('entity_type', 80)->default('system')->index();
            $table->string('entity_id')->nullable()->index();
            $table->string('entity_label')->nullable();
            $table->string('action', 120)->index();
            $table->json('previous_value')->nullable();
            $table->json('new_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'occurred_at']);
            $table->index(['tenant_id', 'category', 'severity']);
            $table->index(['tenant_id', 'entity_type', 'entity_id']);
            $table->index(['tenant_id', 'action', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_audit_logs');
    }
};
