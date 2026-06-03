<?php

namespace App\Admin\Auth\Http\Controllers;

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
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        if (! $this->settings->adminIpAllowed($request->ip())) {
            return $this->error(__('auth.unauthorized'), 403);
        }

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

        if ($user->user_type !== UserType::ADMIN) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        RateLimiter::clear($rateLimitKey);

        return $this->loginResponse($user);
    }

    public function me(Request $request): JsonResponse
    {
        if (! $this->settings->adminIpAllowed($request->ip())) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        if (Auth::user()?->user_type !== UserType::ADMIN) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        return $this->success(new UserResource(Auth::user()), __('auth.retrieved'));
    }

    public function logout(Request $request): JsonResponse
    {
        if (! $this->settings->adminIpAllowed($request->ip())) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        if (Auth::user()?->user_type !== UserType::ADMIN) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        Auth::user()?->tokens()->delete();

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
