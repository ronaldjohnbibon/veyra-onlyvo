<?php

namespace Database\Seeders;

use App\Tenant\Sidebar\Models\Sidebar;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Database\Seeder;

class SidebarSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->each(fn (Tenant $tenant) => $this->runForTenant($tenant));
    }

    public function runForTenant(object $tenant): void
    {
        Sidebar::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name'      => 'default',
                'is_admin'  => false,
            ],
            [
                'description' => 'Default tenant sidebar.',
                'data'        => [
                    'teams' => [
                        [
                            'name' => $tenant->name,
                            'logo' => 'Sparkles',
                            'plan' => 'Workspace',
                        ],
                    ],
                    'main_nav' => [
                        [
                            'title'       => 'Site Builder',
                            'url'         => '#',
                            'icon'        => 'Sparkles',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Templates', 'url' => 'templates', 'is_active' => true],
                                ['title' => 'Posts', 'url' => 'posts', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Creative',
                            'url'         => '#',
                            'icon'        => 'Palette',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Design Requests', 'url' => 'design-requests', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Insights',
                            'url'         => '#',
                            'icon'        => 'BarChart3',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Analytics', 'url' => 'analytics', 'is_active' => true],
                                ['title' => 'Tracking Logs', 'url' => 'tracking-logs', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Maintenance',
                            'url'         => '#',
                            'icon'        => 'Wrench',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Sidebar', 'url' => 'sidebar', 'is_active' => true],
                                ['title' => 'System Settings', 'url' => 'system-settings', 'is_active' => true],
                            ],
                        ],
                    ],
                    'projects' => [],
                ],
            ],
        );
    }
}
