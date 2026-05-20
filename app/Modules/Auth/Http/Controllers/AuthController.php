<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Enums\UserType;
use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use App\Modules\Auth\Http\Resources\TenantResource;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthService;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $service,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $tenant = $this->service->register($request->validated());

        return $this->success(new TenantResource($tenant), __('auth.registered'), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->attemptLogin($request);

        if (! $user) {
            return $this->error(__('auth.invalid'), 401);
        }

        if ($user->user_type === UserType::ADMIN) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        return $this->loginResponse($user);
    }

    public function adminLogin(LoginRequest $request): JsonResponse
    {
        $user = $this->attemptLogin($request);

        if (! $user) {
            return $this->error(__('auth.invalid'), 401);
        }

        if ($user->user_type !== UserType::ADMIN) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        return $this->loginResponse($user, 'admin-token');
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

    public function adminMe(): JsonResponse
    {
        if (Auth::user()?->user_type !== UserType::ADMIN) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        return $this->success(new UserResource(Auth::user()), __('auth.retrieved'));
    }

    public function adminLogout(): JsonResponse
    {
        if (Auth::user()?->user_type !== UserType::ADMIN) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        Auth::user()?->tokens()->delete();

        return $this->success(null, __('auth.logged_out'));
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

    private function loginResponse(User $user, string $tokenName = 'token'): JsonResponse
    {
        $token = $user->createToken($tokenName)->plainTextToken;

        return $this->success([
            'user'  => new UserResource($user),
            'token' => $token,
        ], __('auth.logged_in'));
    }
}
