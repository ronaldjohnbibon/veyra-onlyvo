<?php

namespace App\Tenant\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use App\Tenant\AuditLogs\Services\TenantLogService;
use App\Tenant\Auth\Http\Requests\ForgotPasswordRequest;
use App\Tenant\Auth\Http\Requests\LoginRequest;
use App\Tenant\Auth\Http\Requests\RegisterRequest;
use App\Tenant\Auth\Http\Requests\ResetPasswordRequest;
use App\Tenant\Auth\Http\Resources\TenantResource;
use App\Tenant\Auth\Http\Resources\UserResource;
use App\Tenant\Auth\Services\AuthService;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $service,
        private readonly SystemSettingService $settings,
        private readonly TenantLogService $logs,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        if (! $this->settings->boolean('authentication.allow_tenant_registration', true)) {
            return $this->error('Tenant registration is currently disabled.', 403);
        }

        $tenant = $this->service->register($request->validated());
        $this->logs->auth('tenant.registered', [
            'tenant_id'     => $tenant->id,
            'entity_type'   => 'tenant',
            'entity_id'     => $tenant->id,
            'entity_label'  => $tenant->name,
            'new_value'     => ['name' => $tenant->name, 'subdomain' => $tenant->subdomain, 'status' => $tenant->status],
            'actor_type'    => 'tenant',
            'actor_id'      => data_get($tenant, 'owner.id'),
            'actor_name'    => data_get($tenant, 'owner.name'),
            'actor_email'   => data_get($tenant, 'owner.email'),
        ], null, $request);

        return $this->success(new TenantResource($tenant), __('auth.registered'), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $rateLimitKey = $this->loginRateLimitKey($request);
        $maxAttempts  = $this->settings->integer('security.login_rate_limit_attempts', 5);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $this->logs->permission('tenant.login.rate_limited', [
                'tenant_id'     => $this->requestTenantId(),
                'entity_type'   => 'tenant_session',
                'entity_label'  => (string) $request->input('email'),
                'metadata'      => ['max_attempts' => $maxAttempts],
            ], null, $request);

            return $this->error('Too many login attempts. Please try again later.', 429);
        }

        $user = $this->attemptLogin($request);

        if (! $user) {
            RateLimiter::hit($rateLimitKey, $this->settings->integer('security.login_rate_limit_window_minutes', 1) * 60);
            $this->logs->auth('tenant.login.failed', [
                'tenant_id'     => $this->requestTenantId(),
                'severity'      => 'warning',
                'entity_type'   => 'tenant_session',
                'entity_label'  => (string) $request->input('email'),
                'metadata'      => ['reason' => 'invalid_credentials'],
            ], null, $request);

            return $this->error(__('auth.invalid'), 401);
        }

        if ($user->user_type === UserType::ADMIN) {
            $this->logs->permission('tenant.login.blocked', [
                'tenant_id'     => $this->requestTenantId() ?? $user->tenant_id,
                'entity_type'   => 'tenant_session',
                'entity_id'     => $user->id,
                'entity_label'  => $user->email,
                'metadata'      => ['reason' => 'admin_user_not_allowed'],
            ], $user, $request);

            return $this->error(__('auth.unauthorized'), 403);
        }

        if ($user->tenant?->status !== 'active') {
            $this->logs->permission('tenant.login.blocked', [
                'tenant_id'     => $user->tenant_id,
                'entity_type'   => 'tenant_session',
                'entity_id'     => $user->id,
                'entity_label'  => $user->email,
                'metadata'      => ['reason' => 'tenant_inactive'],
            ], $user, $request);

            return $this->error(__('auth.unauthorized'), 403);
        }

        if ($this->settings->boolean('authentication.require_email_verification') && ! $user->email_verified_at) {
            $this->logs->permission('tenant.login.blocked', [
                'tenant_id'     => $user->tenant_id,
                'entity_type'   => 'tenant_session',
                'entity_id'     => $user->id,
                'entity_label'  => $user->email,
                'metadata'      => ['reason' => 'email_not_verified'],
            ], $user, $request);

            return $this->error('Please verify your email address before signing in.', 403);
        }

        RateLimiter::clear($rateLimitKey);
        $this->logs->auth('tenant.login', [
            'tenant_id'    => $user->tenant_id,
            'entity_type'  => 'tenant_session',
            'entity_id'    => $user->id,
            'entity_label' => $user->email,
        ], $user, $request);

        return $this->loginResponse($user, 'tenant_token');
    }

    public function logout(Request $request): JsonResponse
    {
        $user = Auth::user();
        $this->logs->auth('tenant.logout', [
            'tenant_id'    => data_get($user, 'tenant_id'),
            'entity_type'  => 'tenant_session',
            'entity_id'    => $user?->getAuthIdentifier(),
            'entity_label' => data_get($user, 'email'),
        ], $user, $request);

        Auth::user()?->tokens()->delete();

        return $this->success(null, __('auth.logged_out'));
    }

    public function me(): JsonResponse
    {
        return $this->success(new UserResource(Auth::user()), __('auth.retrieved'));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->validated());

        if ($status !== Password::RESET_LINK_SENT) {
            $this->logs->auth('tenant.password_reset_link_failed', [
                'tenant_id'    => $this->requestTenantId(),
                'severity'     => 'warning',
                'entity_type'  => 'tenant_user',
                'entity_label' => (string) $request->validated('email'),
                'metadata'     => ['status' => $status],
            ], null, $request);

            return $this->error(__($status), 422, [
                'email' => [__($status)],
            ]);
        }

        $this->logs->auth('tenant.password_reset_link_sent', [
            'tenant_id'    => $this->requestTenantId(),
            'entity_type'  => 'tenant_user',
            'entity_label' => (string) $request->validated('email'),
        ], null, $request);

        return $this->success(null, __($status));
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->logs->auth('tenant.password_reset_failed', [
                'tenant_id'    => $this->requestTenantId(),
                'severity'     => 'warning',
                'entity_type'  => 'tenant_user',
                'entity_label' => (string) $request->validated('email'),
                'metadata'     => ['status' => $status],
            ], null, $request);

            return $this->error(__($status), 422, [
                'email' => [__($status)],
            ]);
        }

        $this->logs->auth('tenant.password_reset', [
            'tenant_id'    => $this->requestTenantId(),
            'entity_type'  => 'tenant_user',
            'entity_label' => (string) $request->validated('email'),
        ], null, $request);

        return $this->success(null, __($status));
    }

    private function attemptLogin(LoginRequest $request): ?User
    {
        $credentials = $request->validated();
        $user        = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        return $user;
    }

    private function loginResponse(User $user, string $tokenKey): JsonResponse
    {
        $expiresAt = now()->addMinutes($this->settings->integer('security.session_lifetime_minutes', 120));
        $token     = $user->createToken($tokenKey, ['*'], $expiresAt)->plainTextToken;

        return $this->success([
            'user'    => new UserResource($user),
            $tokenKey => $token,
        ], __('auth.logged_in'));
    }

    private function loginRateLimitKey(LoginRequest $request): string
    {
        return Str::lower((string) $request->input('email')).'|'.$request->ip();
    }

    private function requestTenantId(): ?string
    {
        try {
            return (string) app(\Sprout\Contracts\Tenant::class)->getTenantKey();
        } catch (\Throwable) {
            return data_get(Auth::user(), 'tenant_id');
        }
    }
}
