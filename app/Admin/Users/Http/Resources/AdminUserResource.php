<?php

namespace App\Admin\Users\Http\Resources;

use App\Admin\Users\Services\AdminUserService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $warnings = app(AdminUserService::class)->securityWarnings($this->resource);
        $tokens   = $this->whenLoaded('tokens', fn () => $this->tokens
            ->sortByDesc(fn ($token) => $token->last_used_at ?? $token->created_at)
            ->values()
            ->take(10)
            ->map(fn ($token): array => [
                'id'           => $token->id,
                'name'         => $token->name,
                'abilities'    => $token->abilities ?? [],
                'created_at'   => $token->created_at?->toISOString(),
                'last_used_at' => $token->last_used_at?->toISOString(),
                'expires_at'   => $token->expires_at?->toISOString(),
                'ip_address'   => null,
                'user_agent'   => null,
                'source'       => 'Sanctum token activity',
            ])->all(), []);

        return [
            'id'                     => $this->id,
            'tenant_id'              => $this->tenant_id,
            'name'                   => $this->name,
            'first_name'             => $this->first_name,
            'last_name'              => $this->last_name,
            'email'                  => $this->email,
            'phone'                  => $this->phone,
            'roles'                  => ['Platform Administrator'],
            'permissions'            => AdminUserService::ADMIN_PERMISSIONS,
            'user_type'              => $this->user_type?->value ?? $this->user_type,
            'is_active'              => (bool) $this->is_active,
            'email_verified'         => (bool) $this->email_verified_at,
            'two_factor_enabled'     => false,
            'ip_allow_list_enforced' => ! collect($warnings)->contains(fn (array $warning): bool => $warning['type'] === 'ip_allow_list_missing'),
            'security_warnings'      => $warnings,
            'login_history'          => $tokens,
            'access_logs'            => $tokens,
            'tokens_count'           => $this->whenCounted('tokens'),
            'last_login_at'          => collect($tokens)->pluck('last_used_at')->filter()->first()
                ?? collect($tokens)->pluck('created_at')->filter()->first(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
