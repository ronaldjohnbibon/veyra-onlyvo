<?php

namespace App\Admin\Users\Http\Controllers;

use App\Admin\Users\Http\Requests\AdminUserRequest;
use App\Admin\Users\Http\Resources\AdminUserResource;
use App\Admin\Users\Models\User;
use App\Admin\Users\Services\AdminUserService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminUserService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $sort      = in_array($request->input('sort'), ['name', 'email', 'is_active', 'created_at', 'updated_at'], true) ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $users = User::query()
            ->where('user_type', UserType::ADMIN)
            ->with(['tokens' => fn ($query) => $query->latest()])
            ->withCount('tokens')
            ->when($request->input('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%');
                });
            })
            ->when($request->input('status'), fn ($query, string $status) => $query->where('is_active', $status === 'active'))
            ->orderBy($sort, $direction)
            ->paginate(
                (int) $request->input('pageSize', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1),
            );

        return $this->success(AdminUserResource::collection($users), 'Admin users retrieved.');
    }

    public function store(AdminUserRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $user = $this->service->create($request->validated())->load('tokens')->loadCount('tokens');

        return $this->success(new AdminUserResource($user), 'Admin user created.', 201);
    }

    public function show(string $adminUser): JsonResponse
    {
        $this->authorizeAdmin();

        $user = $this->adminUser($adminUser);

        if (! $user) {
            return $this->error('Admin user not found.', 404);
        }

        return $this->success(new AdminUserResource($user), 'Admin user retrieved.');
    }

    public function update(AdminUserRequest $request, string $adminUser): JsonResponse
    {
        $this->authorizeAdmin();

        $user = $this->adminUser($adminUser);

        if (! $user) {
            return $this->error('Admin user not found.', 404);
        }

        $updated = $this->service->update($user, $request->validated())->load('tokens')->loadCount('tokens');

        return $this->success(new AdminUserResource($updated), 'Admin user updated.');
    }

    public function deactivate(string $adminUser): JsonResponse
    {
        $this->authorizeAdmin();

        $user = $this->adminUser($adminUser);

        if (! $user) {
            return $this->error('Admin user not found.', 404);
        }

        if ((int) Auth::id() === (int) $user->id) {
            return $this->error('You cannot deactivate your own admin user.', 422);
        }

        $updated = $this->service->deactivate($user)->load('tokens')->loadCount('tokens');

        return $this->success(new AdminUserResource($updated), 'Admin user deactivated.');
    }

    public function reactivate(string $adminUser): JsonResponse
    {
        $this->authorizeAdmin();

        $user = $this->adminUser($adminUser);

        if (! $user) {
            return $this->error('Admin user not found.', 404);
        }

        $updated = $this->service->reactivate($user)->load('tokens')->loadCount('tokens');

        return $this->success(new AdminUserResource($updated), 'Admin user reactivated.');
    }

    public function passwordReset(string $adminUser): JsonResponse
    {
        $this->authorizeAdmin();

        $user = $this->adminUser($adminUser);

        if (! $user) {
            return $this->error('Admin user not found.', 404);
        }

        return $this->success($this->service->createPasswordReset($user), 'Password reset token generated.');
    }

    private function adminUser(string $id): ?User
    {
        return User::query()
            ->whereKey($id)
            ->where('user_type', UserType::ADMIN)
            ->with(['tokens' => fn ($query) => $query->latest()])
            ->withCount('tokens')
            ->first();
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN && Auth::user()?->is_active, 403);
    }
}
