<?php

namespace App\Tenant\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use App\Tenant\Auth\Http\Requests\ForgotPasswordRequest;
use App\Tenant\Auth\Http\Requests\LoginRequest;
use App\Tenant\Auth\Http\Requests\RegisterRequest;
use App\Tenant\Auth\Http\Requests\ResetPasswordRequest;
use App\Tenant\Auth\Http\Resources\TenantResource;
use App\Tenant\Auth\Http\Resources\UserResource;
use App\Tenant\Auth\Services\AuthService;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\Users\Models\User;
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
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        if (! $this->settings->boolean('authentication.allow_tenant_registration', true)) {
            return $this->error('Tenant registration is currently disabled.', 403);
        }

        $tenant = $this->service->register($request->validated());

        return $this->success(new TenantResource($tenant), __('auth.registered'), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $rateLimitKey = $this->loginRateLimitKey($request);
        $maxAttempts  = $this->settings->integer('security.login_rate_limit_attempts', 5);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            return $this->error('Too many login attempts. Please try again later.', 429);
        }

        $user = $this->attemptLogin($request);

        if (! $user) {
            RateLimiter::hit($rateLimitKey, $this->settings->integer('security.login_rate_limit_window_minutes', 1) * 60);

            return $this->error(__('auth.invalid'), 401);
        }

        if ($user->user_type === UserType::ADMIN) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        if ($user->tenant?->status !== 'active') {
            return $this->error(__('auth.unauthorized'), 403);
        }

        if ($this->settings->boolean('authentication.require_email_verification') && ! $user->email_verified_at) {
            return $this->error('Please verify your email address before signing in.', 403);
        }

        RateLimiter::clear($rateLimitKey);

        return $this->loginResponse($user, 'tenant_token');
    }

    public function logout(): JsonResponse
    {
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
            return $this->error(__($status), 422, [
                'email' => [__($status)],
            ]);
        }

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
            return $this->error(__($status), 422, [
                'email' => [__($status)],
            ]);
        }

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
}
