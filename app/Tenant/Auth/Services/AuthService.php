<?php

namespace App\Tenant\Auth\Services;

use App\Shared\Enums\UserType;
use App\Shared\SystemSettings\Services\SystemSettingService;
use App\Tenant\Tenants\Models\Tenant;
use App\Tenant\Users\Models\User;
use Database\Seeders\SidebarSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function register(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $tenant = Tenant::create([
                'name'      => $data['name'],
                'subdomain' => $this->generateSubdomain($data['name']),
                'timezone'  => $this->settings->string('tenant_defaults.default_tenant_timezone', 'UTC'),
                'status'    => $this->settings->string('tenant_defaults.default_tenant_status', 'active'),
                'settings'  => [
                    'trial_days'            => $this->settings->integer('tenant_defaults.default_tenant_trial_days', $this->settings->integer('authentication.default_trial_days', 14)),
                    'default_template_type' => $this->settings->string('tenant_defaults.default_tenant_template_type'),
                    'default_template_key'  => $this->settings->string('tenant_defaults.default_tenant_template_key'),
                ],
            ]);

            User::create([
                'name'              => $data['name'],
                'tenant_id'         => $tenant->id,
                'email'             => $data['email'],
                'phone'             => $data['phone'],
                'password'          => Hash::make($data['password']),
                'email_verified_at' => $this->settings->boolean('authentication.require_email_verification') ? null : now(),
                'user_type'         => UserType::TENANT,
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
