<?php

namespace App\Admin\Auth\Http\Controllers;

use App\Admin\AuditLogs\Services\AuditLogService;
use App\Admin\Auth\Http\Requests\LoginRequest;
use App\Admin\Auth\Http\Resources\UserResource;
use App\Admin\SystemSettings\Services\SystemSettingService;
use App\Admin\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        if (! $this->settings->adminIpAllowed($request->ip())) {
            $this->auditLogs->permission('admin.login.blocked', [
                'entity_type'  => 'admin_session',
                'entity_label' => (string) $request->input('email'),
                'metadata'     => ['reason' => 'ip_not_allowed'],
            ], null, $request);

            return $this->error(__('auth.unauthorized'), 403);
        }

        $rateLimitKey = $this->loginRateLimitKey($request);
        $maxAttempts  = $this->settings->integer('security.login_rate_limit_attempts', 5);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $this->auditLogs->permission('admin.login.rate_limited', [
                'entity_type'  => 'admin_session',
                'entity_label' => (string) $request->input('email'),
                'metadata'     => ['max_attempts' => $maxAttempts],
            ], null, $request);

            return $this->error('Too many login attempts. Please try again later.', 429);
        }

        $user = $this->attemptLogin($request);

        if (! $user) {
            RateLimiter::hit($rateLimitKey, $this->settings->integer('security.login_rate_limit_window_minutes', 1) * 60);
            $this->auditLogs->auth('admin.login.failed', [
                'severity'     => 'warning',
                'entity_type'  => 'admin_session',
                'entity_label' => (string) $request->input('email'),
                'metadata'     => ['reason' => 'invalid_credentials'],
            ], null, $request);

            return $this->error(__('auth.invalid'), 401);
        }

        if ($user->user_type !== UserType::ADMIN || ! $user->is_active) {
            $this->auditLogs->permission('admin.login.blocked', [
                'entity_type'  => 'admin_session',
                'entity_id'    => $user->id,
                'entity_label' => $user->email,
                'metadata'     => ['reason' => 'inactive_or_non_admin'],
            ], $user, $request);

            return $this->error(__('auth.unauthorized'), 403);
        }

        RateLimiter::clear($rateLimitKey);
        $this->auditLogs->auth('admin.login', [
            'entity_type'  => 'admin_session',
            'entity_id'    => $user->id,
            'entity_label' => $user->email,
        ], $user, $request);

        return $this->loginResponse($user);
    }

    public function me(Request $request): JsonResponse
    {
        if (! $this->settings->adminIpAllowed($request->ip())) {
            $this->auditLogs->permission('admin.logout.blocked', [
                'entity_type' => 'admin_session',
                'metadata'    => ['reason' => 'ip_not_allowed'],
            ], Auth::user(), $request);

            return $this->error(__('auth.unauthorized'), 403);
        }

        if (Auth::user()?->user_type !== UserType::ADMIN || ! Auth::user()?->is_active) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        return $this->success(new UserResource(Auth::user()), __('auth.retrieved'));
    }

    public function logout(Request $request): JsonResponse
    {
        if (! $this->settings->adminIpAllowed($request->ip())) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        if (Auth::user()?->user_type !== UserType::ADMIN || ! Auth::user()?->is_active) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        $user = Auth::user();

        if ($user) {
            $this->auditLogs->auth('admin.logout', [
                'entity_type'  => 'admin_session',
                'entity_id'    => $user->getAuthIdentifier(),
                'entity_label' => data_get($user, 'email'),
            ], $user, $request);
        }

        $user?->tokens()->delete();

        return $this->success(null, __('auth.logged_out'));
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

    private function loginResponse(User $user): JsonResponse
    {
        $expiresAt = now()->addMinutes($this->settings->integer('security.session_lifetime_minutes', 120));
        $token     = $user->createToken('admin_token', ['*'], $expiresAt)->plainTextToken;

        return $this->success([
            'user'        => new UserResource($user),
            'admin_token' => $token,
        ], __('auth.logged_in'));
    }

    private function loginRateLimitKey(LoginRequest $request): string
    {
        return Str::lower((string) $request->input('email')).'|admin|'.$request->ip();
    }
}
