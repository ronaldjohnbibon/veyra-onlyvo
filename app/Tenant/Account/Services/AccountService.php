<?php

namespace App\Tenant\Account\Services;

use App\Shared\Enums\UserType;
use App\Tenant\Auth\Http\Resources\TenantResource;
use App\Tenant\Auth\Http\Resources\UserResource;
use App\Tenant\Users\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->forceFill($data)->save();

        return $user->fresh();
    }

    public function updatePassword(User $user, string $currentPassword, string $password): User
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($password),
        ])->save();

        return $user->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function accountPayload(User $user): array
    {
        $user->loadMissing('tenant');
        $tenant  = $user->tenant;
        $ownerId = $tenant
            ? User::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_type', UserType::TENANT)
                ->orderBy('created_at')
                ->orderBy('id')
                ->value('id')
            : null;
        $members = $tenant
            ? User::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->get()
                ->map(fn (User $member): array => [
                    'id'          => $member->id,
                    'name'        => $member->name,
                    'email'       => $member->email,
                    'phone'       => $member->phone,
                    'role'        => (int) $member->id === (int) $ownerId ? 'Owner' : $this->roleLabel($member),
                    'user_type'   => $member->user_type?->value ?? $member->user_type,
                    'is_active'   => (bool) $member->is_active,
                    'is_owner'    => (int) $member->id === (int) $ownerId,
                    'permissions' => [],
                ])
                ->values()
            : collect();

        return [
            'user'      => new UserResource($user),
            'workspace' => [
                'tenant'              => $tenant ? new TenantResource($tenant) : null,
                'owner_user_id'       => $ownerId,
                'current_user_owner'  => $ownerId !== null && (int) $ownerId === (int) $user->id,
                'members'             => $members,
                'member_count'        => $members->count(),
                'invites_supported'   => false,
                'roles_supported'     => false,
                'billing_supported'   => false,
                'permissions_summary' => 'Role and permission management is not configured for tenant workspaces yet.',
            ],
        ];
    }

    private function roleLabel(User $user): string
    {
        return match ($user->user_type) {
            UserType::TENANT   => 'Tenant user',
            UserType::EMPLOYEE => 'Team member',
            UserType::CUSTOMER => 'Customer',
            default            => 'User',
        };
    }
}
