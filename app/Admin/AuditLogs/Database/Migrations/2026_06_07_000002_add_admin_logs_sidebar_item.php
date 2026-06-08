<?php

use App\Admin\Sidebar\Models\Sidebar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sidebars')) {
            return;
        }

        Sidebar::query()
            ->where('is_admin', true)
            ->get()
            ->each(function (Sidebar $sidebar): void {
                $data    = $sidebar->data    ?? [];
                $mainNav = $data['main_nav'] ?? [];

                foreach ($mainNav as &$group) {
                    if (($group['title'] ?? '') !== 'Platform' || ! is_array($group['items'] ?? null)) {
                        continue;
                    }

                    $group['items'] = array_values(array_filter(
                        $group['items'],
                        fn ($item): bool => ! is_array($item) || ($item['url'] ?? '') !== 'audit-logs'
                    ));

                    if (! collect($group['items'])->contains(fn ($item): bool => is_array($item) && ($item['url'] ?? '') === 'logs')) {
                        $group['items'][] = ['title' => 'Audit Logs', 'url' => 'logs', 'icon' => 'ShieldCheck', 'is_active' => true];
                    }
                }

                $data['main_nav'] = $mainNav;
                $sidebar->update(['data' => $data]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sidebars')) {
            return;
        }

        Sidebar::query()
            ->where('is_admin', true)
            ->get()
            ->each(function (Sidebar $sidebar): void {
                $data    = $sidebar->data    ?? [];
                $mainNav = $data['main_nav'] ?? [];

                foreach ($mainNav as &$group) {
                    if (($group['title'] ?? '') !== 'Platform' || ! is_array($group['items'] ?? null)) {
                        continue;
                    }

                    $group['items'] = array_values(array_filter(
                        $group['items'],
                        fn ($item): bool => ! is_array($item) || ($item['url'] ?? '') !== 'logs'
                    ));
                }

                $data['main_nav'] = $mainNav;
                $sidebar->update(['data' => $data]);
            });
    }
};
