<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cta_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('cta_identifier', 191);
            $table->string('cta_label')->nullable();
            $table->string('cta_type', 100);
            $table->string('event_type', 100);
            $table->string('url', 500);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('referrer', 500)->nullable();
            $table->char('visitor_hash', 64);
            $table->date('event_date');
            $table->timestamp('triggered_at');
            $table->timestamps();

            $table->index(['tenant_id', 'event_date']);
            $table->index(['tenant_id', 'template_id', 'event_date']);
            $table->index(['tenant_id', 'event_type', 'event_date']);
            $table->index(['tenant_id', 'cta_identifier', 'event_date']);
        });

        Schema::create('cta_unique_visitors', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('cta_identifier', 191);
            $table->string('cta_label')->nullable();
            $table->string('cta_type', 100);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->char('visitor_hash', 64);
            $table->date('event_date');
            $table->timestamp('first_seen_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'template_id', 'cta_identifier', 'visitor_hash', 'event_date'], 'cta_unique_per_day');
            $table->index(['tenant_id', 'event_date']);
            $table->index(['tenant_id', 'template_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cta_unique_visitors');
        Schema::dropIfExists('cta_events');
    }
};
