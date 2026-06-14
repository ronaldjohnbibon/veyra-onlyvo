<?php

namespace App\Tenant\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\Dashboard\Http\Requests\AnalyticsDashboardRequest;
use App\Tenant\Dashboard\Services\AnalyticsService;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $service,
        private readonly SystemSettingService $settings,
        private readonly TenantSystemSettingService $tenantSettings,
    ) {}

    public function index(AnalyticsDashboardRequest $request): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_analytics_module')) {
            return $this->error('Analytics module is disabled.', 403);
        }

        $tenant = $this->tenant();
        $this->service->pruneExpired(
            (string) $tenant->id,
            $this->tenantSettings->integer($tenant, 'analytics.retention_days', 365),
        );

        return $this->success($this->service->dashboard((string) $tenant->id, $request->validated()), 'Analytics retrieved.');
    }

    public function export(AnalyticsDashboardRequest $request): StreamedResponse
    {
        if (! $this->settings->featureEnabled('enable_analytics_module')) {
            abort(403, 'Analytics module is disabled.');
        }

        $tenant = $this->tenant();
        $this->service->pruneExpired(
            (string) $tenant->id,
            $this->tenantSettings->integer($tenant, 'analytics.retention_days', 365),
        );

        $dashboard = $this->service->dashboard((string) $tenant->id, $request->validated());
        $filename  = 'tenant-analytics-'.$dashboard['range']['from'].'-to-'.$dashboard['range']['to'].'.csv';

        return response()->streamDownload(function () use ($dashboard): void {
            $handle = fopen('php://output', 'w');

            if (! $handle) {
                return;
            }

            fputcsv($handle, ['Section', 'Name', 'Value', 'Extra']);
            foreach ($dashboard['range_summary'] as $key => $value) {
                fputcsv($handle, ['Summary', $key, $value, '']);
            }
            foreach ($dashboard['insights'] as $insight) {
                fputcsv($handle, ['Insight', $insight['title'], $insight['body'], $insight['tone']]);
            }
            foreach ($dashboard['campaigns'] as $campaign) {
                fputcsv($handle, ['Campaign', $campaign['campaign'], $campaign['visits'], $campaign['source'].' / '.$campaign['medium'].' / CTA events '.$campaign['cta_events']]);
            }
            foreach ($dashboard['cta_drilldowns'] as $cta) {
                fputcsv($handle, ['CTA', $cta['cta_label'], $cta['total_events'], 'views '.$cta['views'].' / clicks '.$cta['clicks'].' / submissions '.$cta['submissions']]);
            }
            foreach ($dashboard['top_pages']['data'] as $page) {
                fputcsv($handle, ['Top Page', $page['value'], $page['total'], 'visits']);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function tenant(): Tenant
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        $tenant = Tenant::query()->find($tenantId);

        abort_unless($tenant, 404);

        return $tenant;
    }
}
