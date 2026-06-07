<?php

namespace App\Admin\Operations\Http\Controllers;

use App\Admin\Operations\Services\PlatformOperationsService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminOperationsController extends Controller
{
    public function __construct(
        private readonly PlatformOperationsService $operations,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorizeAdmin();

        return $this->success($this->operations->overview(), 'Platform operations retrieved.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
