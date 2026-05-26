<?php

namespace Database\Seeders;

use App\Modules\Sidebar\Models\Sidebar;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

class SidebarSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->each(fn (Tenant $tenant) => $this->runForTenant($tenant));
    }

    public function runForTenant(Tenant $tenant): void
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
                            'title'       => 'Workspace',
                            'url'         => '#',
                            'icon'        => 'LayoutDashboard',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Templates', 'url' => 'templates', 'is_active' => true],
                                ['title' => 'Posts', 'url' => 'posts', 'is_active' => true],
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
                            ],
                        ],
                    ],
                    'projects' => [],
                ],
            ],
        );
    }
}
