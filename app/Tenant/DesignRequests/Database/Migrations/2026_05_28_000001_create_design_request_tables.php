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
            $table->string('title');
            $table->longText('description');
            $table->longText('notes')->nullable();
            $table->json('reference_links')->nullable();
            $table->longText('mockup_concept')->nullable();
            $table->string('status', 30)->default('pending');
            $table->longText('admin_remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'created_at']);
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

        $this->addSidebarLinks();
    }

    public function down(): void
    {
        $this->removeSidebarLinks();

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
