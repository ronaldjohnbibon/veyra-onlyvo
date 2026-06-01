<?php

use App\Modules\Sidebar\Models\Sidebar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_visits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->string('url', 500);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('referrer', 500)->nullable();
            $table->char('visitor_hash', 64);
            $table->date('visit_date');
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->index(['tenant_id', 'visit_date']);
            $table->index(['tenant_id', 'template_id', 'visit_date']);
            $table->index(['tenant_id', 'visitor_hash', 'visit_date']);
        });

        Schema::create('visitor_unique_visitors', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->char('visitor_hash', 64);
            $table->date('visit_date');
            $table->timestamp('first_seen_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'visitor_hash', 'visit_date'], 'visitor_unique_per_day');
            $table->index(['tenant_id', 'visit_date']);
            $table->index(['tenant_id', 'template_id', 'visit_date']);
        });

        $this->addAnalyticsSidebarItem();
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_unique_visitors');
        Schema::dropIfExists('visitor_visits');
    }

    private function addAnalyticsSidebarItem(): void
    {
        Sidebar::withoutTenantRestrictions(function (): void {
            Sidebar::query()
                ->where('is_admin', false)
                ->get()
                ->each(function (Sidebar $sidebar): void {
                    $data    = $sidebar->data    ?? [];
                    $mainNav = $data['main_nav'] ?? [];

                    foreach ($mainNav as &$group) {
                        if (($group['title'] ?? '') !== 'Workspace' || ! is_array($group['items'] ?? null)) {
                            continue;
                        }

                        $hasAnalytics = collect($group['items'])->contains(fn ($item): bool => is_array($item) && ($item['url'] ?? '') === 'analytics');

                        if (! $hasAnalytics) {
                            $group['items'][] = ['title' => 'Analytics', 'url' => 'analytics', 'is_active' => true];
                        }
                    }

                    $data['main_nav'] = $mainNav;
                    $sidebar->update(['data' => $data]);
                });
        });
    }
};
