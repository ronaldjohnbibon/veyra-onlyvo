<?php

use App\Tenant\Dashboard\Services\AnalyticsService;
use App\Tenant\SystemSettings\Services\TenantNotificationService;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tenants:send-weekly-analytics-summaries', function () {
    $analytics     = app(AnalyticsService::class);
    $notifications = app(TenantNotificationService::class);
    $settings      = app(TenantSystemSettingService::class);
    $sent          = 0;

    Tenant::query()
        ->where('status', 'active')
        ->each(function (Tenant $tenant) use ($analytics, $notifications, $settings, &$sent): void {
            if (! $settings->boolean($tenant, 'notifications.weekly_analytics_summary')) {
                return;
            }

            $analytics->pruneExpired(
                (string) $tenant->id,
                $settings->integer($tenant, 'analytics.retention_days', 365),
            );

            $dashboard = $analytics->dashboard((string) $tenant->id, [
                'period'   => 'last_7_days',
                'pageSize' => 1,
            ]);

            $notifications->sendWeeklyAnalyticsSummary($tenant, $dashboard['summary'] ?? []);
            $sent++;
        });

    $this->info("Weekly analytics summaries processed for {$sent} tenant(s).");
})->purpose('Send weekly analytics summary emails for tenants that enabled them');

Schedule::command('tenants:send-weekly-analytics-summaries')->weekly();
