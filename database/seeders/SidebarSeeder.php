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
                            'title'       => 'Home',
                            'url'         => '#',
                            'icon'        => 'LayoutDashboard',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Dashboard', 'url' => 'dashboard', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Website',
                            'url'         => '#',
                            'icon'        => 'Frame',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Templates', 'url' => 'templates', 'is_active' => true],
                                ['title' => 'Template Builder', 'url' => 'template-builder', 'is_active' => true],
                                ['title' => 'Posts', 'url' => 'posts', 'is_active' => true],
                                ['title' => 'Navigation Builder', 'url' => 'navigation-builder', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Growth',
                            'url'         => '#',
                            'icon'        => 'BarChart3',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Leads/Submissions', 'url' => 'leads', 'is_active' => true],
                                ['title' => 'Analytics', 'url' => 'analytics', 'is_active' => true],
                                ['title' => 'Tracking Activity', 'url' => 'tracking-logs', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Collaboration',
                            'url'         => '#',
                            'icon'        => 'Palette',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Design Requests', 'url' => 'design-requests', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Workspace',
                            'url'         => '#',
                            'icon'        => 'Settings',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'System Settings', 'url' => 'system-settings', 'is_active' => true],
                                ['title' => 'Audit Logs', 'url' => 'logs', 'icon' => 'ShieldCheck', 'is_active' => true],
                                ['title' => 'Account/Profile', 'url' => 'account', 'is_active' => true],
                                ['title' => 'Team Management', 'url' => 'team-management', 'is_active' => true],
                            ],
                        ],
                    ],
                    'projects' => [],
                ],
            ],
        );
    }
}
