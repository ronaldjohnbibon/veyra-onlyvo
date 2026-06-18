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
                            'icon'        => 'Home',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'LayoutDashboard', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Website',
                            'url'         => '#',
                            'icon'        => 'Frame',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Template Builder', 'url' => 'template-builder', 'icon' => 'LayoutTemplate', 'is_active' => true],
                                ['title' => 'Posts', 'url' => 'posts', 'icon' => 'FileText', 'is_active' => true],
                                ['title' => 'Navigation Builder', 'url' => 'navigation-builder', 'icon' => 'PanelLeft', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Growth',
                            'url'         => '#',
                            'icon'        => 'BarChart3',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Leads/Submissions', 'url' => 'leads', 'icon' => 'Inbox', 'is_active' => true],
                                ['title' => 'Analytics', 'url' => 'analytics', 'icon' => 'LineChart', 'is_active' => true],
                                ['title' => 'Tracking Activity', 'url' => 'tracking-logs', 'icon' => 'MousePointerClick', 'is_active' => true],
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
                            'title'       => 'Workspace',
                            'url'         => '#',
                            'icon'        => 'Settings',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'System Settings', 'url' => 'system-settings', 'icon' => 'SlidersHorizontal', 'is_active' => true],
                                ['title' => 'Audit Logs', 'url' => 'logs', 'icon' => 'ScrollText', 'is_active' => true],
                                ['title' => 'Account/Profile', 'url' => 'account', 'icon' => 'UserRound', 'is_active' => true],
                                ['title' => 'Team Management', 'url' => 'team-management', 'icon' => 'Users', 'is_active' => true],
                            ],
                        ],
                    ],
                    'projects' => [],
                ],
            ],
        );
    }
}
