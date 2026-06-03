<?php

namespace App\Admin\Auth\Http\Middleware;

use App\Shared\SystemSettings\Services\SystemSettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminIpAllowed
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->adminIpAllowed($request->ip())) {
            return response()->json([
                'status'  => false,
                'message' => __('auth.unauthorized'),
                'errors'  => [],
            ], 403);
        }

        return $next($request);
    }
}
