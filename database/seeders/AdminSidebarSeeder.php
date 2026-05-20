<?php

namespace Database\Seeders;

use App\Modules\Sidebar\Models\Sidebar;
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
                            'icon'        => 'Settings',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Dashboard', 'url' => 'dashboard', 'is_active' => true],
                                ['title' => 'Sidebars', 'url' => 'sidebar', 'is_active' => true],
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
