<?php

namespace App\Shared\SystemSettings\Http\Middleware;

use App\Shared\SystemSettings\Services\SystemSettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotInMaintenance
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $adminBypassed = $request->is('api/admin*') && $this->settings->adminBypassesMaintenance();

        if (
            ! $adminBypassed
            && $this->settings->maintenanceActive()
            && $this->settings->maintenanceAffectsPath($request->path())
        ) {
            return response()->json([
                'status'  => false,
                'message' => $this->settings->string('maintenance.maintenance_message', 'The platform is temporarily unavailable for maintenance.'),
                'errors'  => [],
            ], 503);
        }

        return $next($request);
    }
}
