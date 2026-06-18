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
                            'icon'        => 'Home',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'LayoutDashboard', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Platform',
                            'url'         => '#',
                            'icon'        => 'Building2',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Tenants', 'url' => 'tenants', 'icon' => 'Building2', 'is_active' => true],
                                ['title' => 'Admin Users', 'url' => 'admin-users', 'icon' => 'UserCog', 'is_active' => true],
                                ['title' => 'Operations', 'url' => 'operations', 'icon' => 'Wrench', 'is_active' => true],
                                ['title' => 'Audit Logs', 'url' => 'logs', 'icon' => 'ScrollText', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Website',
                            'url'         => '#',
                            'icon'        => 'Frame',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Template Library', 'url' => 'templates', 'icon' => 'LayoutTemplate', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Collaboration',
                            'url'         => '#',
                            'icon'        => 'Palette',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Design Requests', 'url' => 'design-requests', 'icon' => 'ClipboardList', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Insights',
                            'url'         => '#',
                            'icon'        => 'BarChart3',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Leads / Submissions', 'url' => 'leads', 'icon' => 'Inbox', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Workspace',
                            'url'         => '#',
                            'icon'        => 'Settings',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Navigation Builder', 'url' => 'navigation-builder', 'icon' => 'PanelLeft', 'is_active' => true],
                                ['title' => 'System Settings', 'url' => 'system-settings', 'icon' => 'SlidersHorizontal', 'is_active' => true],
                            ],
                        ],
                    ],
                    'projects' => [
                        ['name' => 'Platform Ops', 'url' => '#', 'icon' => 'Activity'],
                    ],
                ],
            ],
        );
    }
}
