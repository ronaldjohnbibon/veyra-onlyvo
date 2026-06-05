<?php

namespace App\Admin\Dashboard\Http\Controllers;

use App\Admin\Dashboard\Services\AdminDashboardService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $service,
    ) {}

    public function index(): JsonResponse
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);

        return $this->success($this->service->dashboard(), 'Admin dashboard retrieved.');
    }
}
