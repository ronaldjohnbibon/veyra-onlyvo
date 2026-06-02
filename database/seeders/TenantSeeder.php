<?php

namespace Database\Seeders;

use App\Shared\Enums\UserType;
use App\Tenant\Tenants\Models\Tenant;
use App\Tenant\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['subdomain' => env('SEED_TENANT_SUBDOMAIN', 'onlyvo')],
            [
                'name'     => env('SEED_TENANT_NAME', 'Onlyvo Demo'),
                'timezone' => env('SEED_TENANT_TIMEZONE', 'UTC'),
                'status'   => 'active',
                'settings' => [],
            ],
        );

        User::query()->updateOrCreate(
            ['email' => env('SEED_TENANT_EMAIL', 'tenant@example.com')],
            [
                'tenant_id'  => $tenant->id,
                'name'       => env('SEED_TENANT_USER_NAME', 'Onlyvo Tenant'),
                'first_name' => env('SEED_TENANT_FIRST_NAME', 'Onlyvo'),
                'last_name'  => env('SEED_TENANT_LAST_NAME', 'Tenant'),
                'phone'      => env('SEED_TENANT_PHONE', '09123456789'),
                'password'   => Hash::make(env('SEED_TENANT_PASSWORD', 'password')),
                'is_active'  => true,
                'user_type'  => UserType::TENANT,
            ],
        );

        app(SidebarSeeder::class)->runForTenant($tenant);
    }
}
