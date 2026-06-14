<?php

namespace App\Tenant\TrackingLogs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\TrackingLogs\Http\Requests\TrackingLogIndexRequest;
use App\Tenant\TrackingLogs\Services\TrackingLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Sprout\Contracts\Tenant as CurrentTenant;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrackingLogController extends Controller
{
    public function __construct(
        private readonly TrackingLogService $service,
        private readonly SystemSettingService $settings,
    ) {}

    public function index(TrackingLogIndexRequest $request, CurrentTenant $tenant): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_tracking_logs')) {
            return $this->error('Tracking logs are disabled.', 403);
        }

        return $this->success($this->service->index($this->tenantId($tenant), $request->validated()), 'Tracking logs retrieved.');
    }

    public function export(TrackingLogIndexRequest $request, CurrentTenant $tenant): StreamedResponse
    {
        if (! $this->settings->featureEnabled('enable_tracking_logs')) {
            abort(403, 'Tracking logs are disabled.');
        }

        $rows = $this->service->exportRows($this->tenantId($tenant), $request->validated());

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if (! $handle) {
                return;
            }

            fputcsv($handle, [
                'Time',
                'Activity',
                'Event Type',
                'Website',
                'Page',
                'Visitor',
                'IP',
                'Device',
                'Browser',
                'Referrer',
                'UTM Source',
                'UTM Medium',
                'UTM Campaign',
                'Conversion Status',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['created_at'],
                    $row['activity_title'],
                    $row['event_type_label'],
                    $row['website_template'],
                    $row['landing_page_url'],
                    $row['visitor_label'],
                    $row['ip_address_label'],
                    $row['device_type'],
                    $row['browser'],
                    $row['referrer_url'],
                    $row['utm_source'],
                    $row['utm_medium'],
                    $row['utm_campaign'],
                    $row['conversion_status'],
                ]);
            }

            fclose($handle);
        }, 'tracking-logs.csv', ['Content-Type' => 'text/csv']);
    }

    private function tenantId(CurrentTenant $tenant): string
    {
        $tenantId     = (string) $tenant->getTenantKey();
        $userTenantId = Auth::user()?->tenant_id;

        abort_unless($userTenantId && hash_equals($tenantId, (string) $userTenantId), 403);

        return $tenantId;
    }
}
