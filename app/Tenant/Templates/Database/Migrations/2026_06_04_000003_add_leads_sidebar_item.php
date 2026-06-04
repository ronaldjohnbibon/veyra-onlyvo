<?php

use App\Tenant\Sidebar\Models\Sidebar;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Sidebar::withoutTenantRestrictions(function (): void {
            Sidebar::query()
                ->where('is_admin', false)
                ->get()
                ->each(function (Sidebar $sidebar): void {
                    $data    = $sidebar->data    ?? [];
                    $mainNav = $data['main_nav'] ?? [];

                    foreach ($mainNav as &$group) {
                        if (($group['title'] ?? '') !== 'Insights' || ! is_array($group['items'] ?? null)) {
                            continue;
                        }

                        $hasLeads = collect($group['items'])->contains(fn ($item): bool => is_array($item) && ($item['url'] ?? '') === 'leads');

                        if (! $hasLeads) {
                            $analyticsIndex = collect($group['items'])->search(fn ($item): bool => is_array($item) && ($item['url'] ?? '') === 'analytics');
                            $insertAt       = $analyticsIndex === false ? count($group['items']) : ((int) $analyticsIndex + 1);

                            array_splice($group['items'], $insertAt, 0, [[
                                'title'     => 'Leads',
                                'url'       => 'leads',
                                'is_active' => true,
                            ]]);
                        }
                    }

                    $data['main_nav'] = $mainNav;
                    $sidebar->update(['data' => $data]);
                });
        });
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
                        if (($group['title'] ?? '') !== 'Insights' || ! is_array($group['items'] ?? null)) {
                            continue;
                        }

                        $group['items'] = array_values(array_filter(
                            $group['items'],
                            fn ($item): bool => ! is_array($item) || ($item['url'] ?? '') !== 'leads'
                        ));
                    }

                    $data['main_nav'] = $mainNav;
                    $sidebar->update(['data' => $data]);
                });
        });
    }
};
