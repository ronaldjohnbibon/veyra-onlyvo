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

                    $hasDashboard = collect($mainNav)->contains(fn ($item): bool => is_array($item) && ($item['url'] ?? '') === 'dashboard');

                    if (! $hasDashboard) {
                        array_unshift($mainNav, [
                            'title'       => 'Dashboard',
                            'url'         => 'dashboard',
                            'icon'        => 'LayoutDashboard',
                            'description' => '',
                            'is_active'   => true,
                        ]);
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

                    $data['main_nav'] = array_values(array_filter(
                        $mainNav,
                        fn ($item): bool => ! is_array($item) || ($item['url'] ?? '') !== 'dashboard'
                    ));

                    $sidebar->update(['data' => $data]);
                });
        });
    }
};
