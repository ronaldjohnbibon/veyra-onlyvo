<?php

use App\Tenant\Sidebar\Models\Sidebar;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncTrackingLogsSidebarItem();
    }

    public function down(): void
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

                        $group['items'] = array_values(array_filter(
                            $group['items'],
                            fn ($item): bool => ! is_array($item) || ($item['url'] ?? '') !== 'tracking-logs'
                        ));
                    }

                    $data['main_nav'] = $mainNav;
                    $sidebar->update(['data' => $data]);
                });
        });
    }

    private function syncTrackingLogsSidebarItem(): void
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

                        $hasTrackingLogs = collect($group['items'])->contains(fn ($item): bool => is_array($item) && ($item['url'] ?? '') === 'tracking-logs');

                        if (! $hasTrackingLogs) {
                            $group['items'][] = ['title' => 'Tracking Logs', 'url' => 'tracking-logs', 'is_active' => true];
                        }
                    }

                    $data['main_nav'] = $mainNav;
                    $sidebar->update(['data' => $data]);
                });
        });
    }
};
