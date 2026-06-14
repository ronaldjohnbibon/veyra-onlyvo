<?php

namespace App\Tenant\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\Account\Http\Requests\AccountPasswordRequest;
use App\Tenant\Account\Http\Requests\AccountProfileRequest;
use App\Tenant\Account\Services\AccountService;
use App\Tenant\AuditLogs\Services\TenantLogService;
use App\Tenant\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Sprout\Contracts\Tenant as CurrentTenant;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $service,
        private readonly TenantLogService $logs,
    ) {}

    public function show(CurrentTenant $tenant): JsonResponse
    {
        $user = $this->currentUser($tenant);

        return $this->success($this->service->accountPayload($user), 'Account workspace retrieved.');
    }

    public function updateProfile(AccountProfileRequest $request, CurrentTenant $tenant): JsonResponse
    {
        $user     = $this->currentUser($tenant);
        $previous = $user->attributesToArray();
        $user     = $this->service->updateProfile($user, $request->validated());
        $this->logs->recordModel('account.profile_updated', $user, $user, $request, $previous, $user->attributesToArray(), 'tenant_user');

        return $this->success($this->service->accountPayload($user), 'Profile updated.');
    }

    public function updatePassword(AccountPasswordRequest $request, CurrentTenant $tenant): JsonResponse
    {
        $user = $this->currentUser($tenant);
        $data = $request->validated();

        $user = $this->service->updatePassword(
            $user,
            (string) $data['current_password'],
            (string) $data['password'],
        );
        $this->logs->auth('account.password_updated', [
            'tenant_id'    => $user->tenant_id,
            'entity_type'  => 'tenant_user',
            'entity_id'    => $user->id,
            'entity_label' => $user->email,
        ], $user, $request);

        return $this->success($this->service->accountPayload($user), 'Password updated.');
    }

    private function currentUser(CurrentTenant $tenant): User
    {
        $user     = Auth::user();
        $tenantId = (string) $tenant->getTenantKey();

        abort_unless($user instanceof User, 403);
        abort_unless($user->tenant_id && hash_equals($tenantId, (string) $user->tenant_id), 403);

        return $user;
    }
}
