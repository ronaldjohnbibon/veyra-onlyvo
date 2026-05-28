<?php

namespace App\Modules\Tenant\Services;

use App\Modules\Auth\Enums\UserType;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\User\Models\User;
use Database\Seeders\SidebarSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $tenant = Tenant::query()->create($this->payload($data));

            $this->createOwner($tenant, $data);

            // New tenants get the same default sidebar as seeded tenants.
            app(SidebarSeeder::class)->runForTenant($tenant);

            return $tenant;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data): Tenant {
            $tenant->update($this->payload($data));

            $this->updateOwner($tenant, $data);

            return $tenant->fresh();
        });
    }

    public function setStatus(Tenant $tenant, string $status): Tenant
    {
        $tenant->update(['status' => $status]);

        return $tenant->fresh();
    }

    public function delete(Tenant $tenant): void
    {
        // Delete through the Eloquent builder to keep soft deletes consistent.
        Tenant::query()->whereKey($tenant->getKey())->delete();
    }

    /**
     * Keep tenant persistence limited to fields admins can manage.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'name'      => $data['name'],
            'subdomain' => $data['subdomain'],
            'timezone'  => $data['timezone'],
            'status'    => $data['status'] ?? 'active',
            'settings'  => $data['settings'] ?? [],
        ];
    }

    /**
     * Create the first tenant user so the tenant can sign in.
     *
     * @param  array<string, mixed>  $data
     */
    private function createOwner(Tenant $tenant, array $data): void
    {
        User::query()->create([
            'tenant_id'   => $tenant->id,
            'name'        => $data['owner_name'],
            'first_name'  => $data['owner_first_name'] ?? null,
            'last_name'   => $data['owner_last_name'] ?? null,
            'email'       => $data['owner_email'],
            'phone'       => $data['owner_phone'] ?? null,
            'password'    => Hash::make($data['owner_password']),
            'is_active'   => true,
            'user_type'   => UserType::TENANT,
        ]);
    }

    /**
     * Update the linked tenant user used for tenant login.
     *
     * @param  array<string, mixed>  $data
     */
    private function updateOwner(Tenant $tenant, array $data): void
    {
        $owner = $tenant->owner()->first();

        if (! $owner) {
            $this->createOwner($tenant, $data);

            return;
        }

        $payload = [
            'name'       => $data['owner_name'],
            'first_name' => $data['owner_first_name'] ?? null,
            'last_name'  => $data['owner_last_name'] ?? null,
            'email'      => $data['owner_email'],
            'phone'      => $data['owner_phone'] ?? null,
            'is_active'  => true,
            'user_type'  => UserType::TENANT,
        ];

        if (! empty($data['owner_password'])) {
            $payload['password'] = Hash::make($data['owner_password']);
        }

        $owner->update($payload);
    }
}
