<?php

namespace App\Tenant\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\Account\Http\Requests\AccountPasswordRequest;
use App\Tenant\Account\Http\Requests\AccountProfileRequest;
use App\Tenant\Account\Services\AccountService;
use App\Tenant\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $service,
    ) {}

    public function show(): JsonResponse
    {
        $user = $this->currentUser();

        return $this->success($this->service->accountPayload($user), 'Account workspace retrieved.');
    }

    public function updateProfile(AccountProfileRequest $request): JsonResponse
    {
        $user = $this->currentUser();
        $user = $this->service->updateProfile($user, $request->validated());

        return $this->success($this->service->accountPayload($user), 'Profile updated.');
    }

    public function updatePassword(AccountPasswordRequest $request): JsonResponse
    {
        $user = $this->currentUser();
        $user = $this->service->updatePassword(
            $user,
            (string) $request->input('current_password'),
            (string) $request->input('password'),
        );

        return $this->success($this->service->accountPayload($user), 'Password updated.');
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
