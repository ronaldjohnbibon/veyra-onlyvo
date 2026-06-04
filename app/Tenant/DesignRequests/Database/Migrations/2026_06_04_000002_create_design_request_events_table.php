<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_request_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('design_request_id')->constrained('design_requests')->cascadeOnDelete();
            $table->string('actor_type', 20)->default('system');
            $table->string('actor_name')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('event_type', 40);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->longText('message')->nullable();
            $table->timestamps();

            $table->index(['design_request_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_request_events');
    }
};
