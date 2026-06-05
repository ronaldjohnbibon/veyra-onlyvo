<?php

namespace App\Admin\Users\Services;

use App\Admin\SystemSettings\Services\SystemSettingService;
use App\Admin\Users\Models\User;
use App\Shared\Enums\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserService
{
    /**
     * @var array<int, string>
     */
    public const ADMIN_PERMISSIONS = [
        'platform.dashboard',
        'tenants.manage',
        'templates.manage',
        'design_requests.review',
        'settings.manage',
        'admin_users.manage',
    ];

    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return User::query()->create($this->payload($data, true));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($this->payload($data, false));

        return $user->fresh();
    }

    public function deactivate(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $user->tokens()->delete();
            $user->update(['is_active' => false]);

            return $user->fresh();
        });
    }

    public function reactivate(User $user): User
    {
        $user->update(['is_active' => true]);

        return $user->fresh();
    }

    /**
     * @return array{email: string, reset_token: string, expires_at: string}
     */
    public function createPasswordReset(User $user): array
    {
        $token     = Str::random(64);
        $expiresAt = now()->addMinutes((int) config('auth.passwords.users.expire', 60));

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token'      => Hash::make($token),
                'created_at' => now(),
            ],
        );

        $user->tokens()->delete();

        return [
            'email'       => $user->email,
            'reset_token' => $token,
            'expires_at'  => $expiresAt->toISOString(),
        ];
    }

    /**
     * @return array<int, array{type: string, level: string, message: string}>
     */
    public function securityWarnings(User $user): array
    {
        $warnings = [];

        if (! $user->is_active) {
            $warnings[] = [
                'type'    => 'inactive',
                'level'   => 'critical',
                'message' => 'This admin user is inactive and cannot access admin operations.',
            ];
        }

        if (! $user->email_verified_at) {
            $warnings[] = [
                'type'    => 'email_unverified',
                'level'   => 'warning',
                'message' => 'Email verification is not recorded for this admin user.',
            ];
        }

        if ($this->settings->string('security.allowed_admin_ips') === '') {
            $warnings[] = [
                'type'    => 'ip_allow_list_missing',
                'level'   => 'warning',
                'message' => 'No admin IP allow-list is configured.',
            ];
        }

        $warnings[] = [
            'type'    => 'two_factor_missing',
            'level'   => 'info',
            'message' => 'Two-factor authentication is not available in the current auth model.',
        ];

        return $warnings;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, bool $creating): array
    {
        $payload = [
            'tenant_id'         => null,
            'name'              => $data['name'],
            'first_name'        => $data['first_name'] ?? null,
            'last_name'         => $data['last_name']  ?? null,
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'is_active'         => (bool) ($data['is_active'] ?? true),
            'user_type'         => UserType::ADMIN,
            'email_verified_at' => ($data['email_verified'] ?? true) ? now() : null,
        ];

        if ($creating || ! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        return $payload;
    }
}
