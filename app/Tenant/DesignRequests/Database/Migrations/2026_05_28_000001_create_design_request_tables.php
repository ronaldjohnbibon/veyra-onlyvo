<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable();
            $table->string('title');
            $table->longText('description');
            $table->longText('notes')->nullable();
            $table->json('reference_links')->nullable();
            $table->longText('mockup_concept')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('priority', 20)->default('normal');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->longText('admin_remarks')->nullable();
            $table->longText('internal_notes')->nullable();
            $table->boolean('notification_requested')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            $table->string('conversion_type', 40)->nullable();
            $table->json('conversion_payload')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by')->nullable();
            $table->foreignUuid('linked_template_id')->nullable();
            $table->text('linked_site_url')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to', 'dr_assigned_to_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('converted_by', 'dr_converted_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('linked_template_id', 'dr_template_fk')->references('id')->on('templates')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['assigned_to', 'status'], 'dr_assigned_status_idx');
            $table->index(['priority', 'due_at'], 'dr_priority_due_idx');
        });

        Schema::create('design_request_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('design_request_id')->constrained('design_requests')->cascadeOnDelete();
            $table->string('name');
            $table->text('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index('design_request_id');
        });

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

        $this->addSidebarLinks();
    }

    public function down(): void
    {
        $this->removeSidebarLinks();

        Schema::dropIfExists('design_request_events');
        Schema::dropIfExists('design_request_files');
        Schema::dropIfExists('design_requests');
    }

    private function addSidebarLinks(): void
    {
        $this->updateSidebar(false, 'Workspace', [
            'title'     => 'Design Requests',
            'url'       => 'design-requests',
            'is_active' => true,
        ]);

        $this->updateSidebar(true, 'Platform', [
            'title'     => 'Design Requests',
            'url'       => 'design-requests',
            'is_active' => true,
        ]);
    }

    private function removeSidebarLinks(): void
    {
        $this->updateSidebar(false, 'Workspace', null);
        $this->updateSidebar(true, 'Platform', null);
    }

    /**
     * @param  array<string, mixed>|null  $link
     */
    private function updateSidebar(bool $isAdmin, string $groupTitle, ?array $link): void
    {
        DB::table('sidebars')
            ->where('is_admin', $isAdmin)
            ->orderBy('id')
            ->each(function (object $sidebar) use ($groupTitle, $link): void {
                $data = json_decode((string) $sidebar->data, true);

                if (! is_array($data) || ! is_array($data['main_nav'] ?? null)) {
                    return;
                }

                foreach ($data['main_nav'] as &$group) {
                    if (($group['title'] ?? null) !== $groupTitle || ! is_array($group['items'] ?? null)) {
                        continue;
                    }

                    // Keep sidebar JSON in sync with the new route.
                    $group['items'] = array_values(array_filter(
                        $group['items'],
                        fn ($item): bool => ! is_array($item) || ($item['url'] ?? null) !== 'design-requests',
                    ));

                    if ($link) {
                        $group['items'][] = $link;
                    }
                }

                DB::table('sidebars')
                    ->where('id', $sidebar->id)
                    ->update(['data' => json_encode($data)]);
            });
    }
};
