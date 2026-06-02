<?php

namespace App\Admin\Auth\Http\Controllers;

use App\Admin\Auth\Http\Requests\LoginRequest;
use App\Admin\Auth\Http\Resources\UserResource;
use App\Admin\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->attemptLogin($request);

        if (! $user) {
            return $this->error(__('auth.invalid'), 401);
        }

        if ($user->user_type !== UserType::ADMIN) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        return $this->loginResponse($user);
    }

    public function me(): JsonResponse
    {
        if (Auth::user()?->user_type !== UserType::ADMIN) {
            return $this->error(__('auth.unauthorized'), 403);
        }

        return $this->success(new UserResource(Auth::user()), __('auth.retrieved'));
    }

    public function logout(): JsonResponse
    {
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
        $token = $user->createToken('admin_token')->plainTextToken;

        return $this->success([
            'user'        => new UserResource($user),
            'admin_token' => $token,
        ], __('auth.logged_in'));
    }
}
