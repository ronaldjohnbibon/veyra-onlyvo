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
                            'title'       => 'Platform',
                            'url'         => '#',
                            'icon'        => 'LayoutDashboard',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Dashboard', 'url' => 'dashboard', 'is_active' => true],
                                ['title' => 'Tenants', 'url' => 'tenants', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Operations',
                            'url'         => '#',
                            'icon'        => 'Palette',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Design Requests', 'url' => 'design-requests', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Template Library',
                            'url'         => '#',
                            'icon'        => 'Package',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Templates', 'url' => 'templates', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Maintenance',
                            'url'         => '#',
                            'icon'        => 'Wrench',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Sidebars', 'url' => 'sidebar', 'is_active' => true],
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
