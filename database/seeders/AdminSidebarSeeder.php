<?php

namespace Database\Seeders;

use App\Admin\Sidebar\Models\Sidebar;
use Illuminate\Database\Seeder;

class AdminSidebarSeeder extends Seeder
{
    public function run(): void
    {
        Sidebar::query()->updateOrCreate(
            [
                'tenant_id' => null,
                'name'      => 'admin',
                'is_admin'  => true,
            ],
            [
                'description' => 'Default platform admin sidebar.',
                'data'        => [
                    'teams' => [
                        [
                            'name' => 'Onlyvo',
                            'logo' => 'ShieldCheck',
                            'plan' => 'Admin',
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
                            'title'       => 'Platform',
                            'url'         => '#',
                            'icon'        => 'ShieldCheck',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Tenants', 'url' => 'tenants', 'is_active' => true],
                                ['title' => 'Admin Users', 'url' => 'admin-users', 'is_active' => true],
                                ['title' => 'Operations', 'url' => 'operations', 'icon' => 'Wrench', 'is_active' => true],
                                ['title' => 'Audit Logs', 'url' => 'logs', 'icon' => 'ShieldCheck', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Website',
                            'url'         => '#',
                            'icon'        => 'Package',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Template Library', 'url' => 'templates', 'is_active' => true],
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
                            'title'       => 'Insights',
                            'url'         => '#',
                            'icon'        => 'BarChart3',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Leads / Submissions', 'url' => 'leads', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Workspace',
                            'url'         => '#',
                            'icon'        => 'Settings',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Navigation Builder', 'url' => 'navigation-builder', 'is_active' => true],
                                ['title' => 'System Settings', 'url' => 'system-settings', 'is_active' => true],
                            ],
                        ],
                    ],
                    'projects' => [
                        ['name' => 'Platform Ops', 'url' => '#', 'icon' => 'Frame'],
                    ],
                ],
            ],
        );
    }
}
