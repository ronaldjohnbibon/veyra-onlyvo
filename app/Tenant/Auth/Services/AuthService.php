<?php

namespace App\Tenant\Auth\Services;

use App\Shared\Enums\UserType;
use App\Tenant\Tenants\Models\Tenant;
use App\Tenant\Users\Models\User;
use Database\Seeders\SidebarSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $tenant = Tenant::create([
                'name'      => $data['name'],
                'subdomain' => $this->generateSubdomain($data['name']),
            ]);

            User::create([
                'name'      => $data['name'],
                'tenant_id' => $tenant->id,
                'email'     => $data['email'],
                'phone'     => $data['phone'],
                'password'  => Hash::make($data['password']),
                'user_type' => UserType::TENANT,
            ]);

            app(SidebarSeeder::class)->runForTenant($tenant);

            return $tenant;
        });
    }

    private function generateSubdomain(string $companyName): string
    {
        $subdomain = strtolower($companyName);
        $subdomain = preg_replace('/[^a-z0-9]+/i', '-', $subdomain) ?? '';
        $subdomain = preg_replace('/-+/', '-', $subdomain)          ?? '';

        return trim($subdomain, '-');
    }
}
