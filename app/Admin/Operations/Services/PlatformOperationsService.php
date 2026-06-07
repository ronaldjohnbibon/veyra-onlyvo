<?php

namespace App\Admin\Operations\Services;

use App\Admin\SystemSettings\Services\SystemSettingService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class PlatformOperationsService
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $sections = [
            'queue'     => $this->queueStatus(),
            'mail'      => $this->mailStatus(),
            'storage'   => $this->storageStatus(),
            'analytics' => $this->analyticsStatus(),
            'schedule'  => $this->scheduledTasksStatus(),
            'errors'    => $this->errorSummary(),
        ];

        return [
            'status'                 => $this->overallStatus($sections),
            'summary'                => $this->summary($sections),
            'sections'               => $sections,
            'failed_jobs'            => $this->failedJobs(),
            'recent_platform_events' => $this->recentPlatformEvents(),
            'generated_at'           => now()->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function queueStatus(): array
    {
        $connection = (string) config('queue.default');
        $jobsTable  = (string) config('queue.connections.database.table', 'jobs');
        $pending    = $this->hasTable($jobsTable) ? (int) DB::table($jobsTable)->count() : 0;
        $oldest     = $this->hasTable($jobsTable)
            ? DB::table($jobsTable)->orderBy('created_at')->first(['queue', 'attempts', 'created_at'])
            : null;
        $failed = $this->failedJobCount();
        $status = $failed > 0 ? 'critical' : ($pending > 25 ? 'warning' : 'healthy');

        return [
            'key'         => 'queue',
            'label'       => 'Queue status',
            'status'      => $status,
            'value'       => "{$pending} pending",
            'description' => $failed > 0
                ? "{$failed} failed job(s) need review."
                : ($pending > 0 ? 'Jobs are waiting for workers.' : 'No queued jobs are waiting.'),
            'metrics' => [
                'connection'      => $connection,
                'pending_jobs'    => $pending,
                'failed_jobs'     => $failed,
                'oldest_queue'    => $oldest?->queue,
                'oldest_attempts' => $oldest?->attempts,
                'oldest_created'  => $this->timestamp($oldest?->created_at),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mailStatus(): array
    {
        $driver   = $this->settings->string('email.mail_driver', (string) config('mail.default'));
        $sender   = $this->settings->string('email.sender_email', (string) config('mail.from.address'));
        $host     = $this->settings->string('email.smtp_host', (string) config('mail.mailers.smtp.host'));
        $ready    = $driver !== '' && $sender !== '' && ($driver !== 'smtp' || $host !== '');
        $status   = $ready ? (in_array($driver, ['log', 'array'], true) ? 'warning' : 'healthy') : 'critical';
        $lastTest = $this->latestHistory('email.test_delivery', 'tested');

        return [
            'key'         => 'mail',
            'label'       => 'Mail delivery status',
            'status'      => $status,
            'value'       => $driver !== '' ? strtoupper($driver) : 'Not configured',
            'description' => $ready
                ? ($status === 'warning' ? 'Mail is configured for local/non-delivery mode.' : 'Mail settings are configured for delivery.')
                : 'Mail driver, sender, or SMTP host needs configuration.',
            'metrics' => [
                'driver'         => $driver,
                'sender_email'   => $sender,
                'smtp_host'      => $driver === 'smtp' ? $host : null,
                'last_tested_at' => $this->timestamp($lastTest?->changed_at),
                'last_tested_by' => $lastTest?->changed_by_email ?: $lastTest?->changed_by_name,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storageStatus(): array
    {
        $publicBytes = $this->directorySize(storage_path('app/public'));
        $logBytes    = $this->directorySize(storage_path('logs'));
        $uploadLimit = $this->settings->integer('storage.maximum_upload_size', 4096);
        $allowed     = $this->settings->string('storage.allowed_file_types', 'jpg,jpeg,png,webp,gif');
        $status      = $publicBytes > 1024 * 1024 * 1024 || $logBytes > 512 * 1024 * 1024 ? 'warning' : 'healthy';

        return [
            'key'         => 'storage',
            'label'       => 'Storage usage',
            'status'      => $status,
            'value'       => $this->formatBytes($publicBytes + $logBytes),
            'description' => "Public uploads use {$this->formatBytes($publicBytes)}. Logs use {$this->formatBytes($logBytes)}.",
            'metrics'     => [
                'public_uploads_bytes' => $publicBytes,
                'public_uploads'       => $this->formatBytes($publicBytes),
                'logs_bytes'           => $logBytes,
                'logs'                 => $this->formatBytes($logBytes),
                'upload_limit_kb'      => $uploadLimit,
                'upload_limit'         => $this->formatBytes($uploadLimit * 1024),
                'allowed_file_types'   => $allowed,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function analyticsStatus(): array
    {
        $moduleEnabled  = $this->settings->featureEnabled('enable_analytics_module');
        $visitorEnabled = $this->settings->boolean('analytics.enable_visitor_tracking', true);
        $ctaEnabled     = $this->settings->boolean('analytics.enable_cta_tracking', true);
        $visits         = $this->recentCount('visitor_visits', 24);
        $ctaEvents      = $this->recentCount('cta_events', 24);
        $enabled        = $moduleEnabled && ($visitorEnabled || $ctaEnabled);
        $recent         = $visits + $ctaEvents;

        return [
            'key'         => 'analytics',
            'label'       => 'Analytics capture status',
            'status'      => $enabled ? ($recent > 0 ? 'healthy' : 'warning') : 'critical',
            'value'       => $enabled ? "{$recent} last 24h" : 'Disabled',
            'description' => $enabled
                ? ($recent > 0 ? 'Analytics events are being captured.' : 'Capture is enabled, but no recent events were recorded.')
                : 'Analytics module or capture settings are disabled.',
            'metrics' => [
                'module_enabled'  => $moduleEnabled,
                'visitor_enabled' => $visitorEnabled,
                'cta_enabled'     => $ctaEnabled,
                'visits_24h'      => $visits,
                'cta_events_24h'  => $ctaEvents,
                'visits_7d'       => $this->recentCount('visitor_visits', 24 * 7),
                'cta_events_7d'   => $this->recentCount('cta_events', 24 * 7),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduledTasksStatus(): array
    {
        $events = collect(app(Schedule::class)->events())
            ->map(fn (object $event): array => [
                'command'     => method_exists($event, 'getSummaryForDisplay') ? $event->getSummaryForDisplay() : (string) ($event->command ?? 'Scheduled task'),
                'expression'  => (string) ($event->expression ?? '* * * * *'),
                'description' => (string) ($event->description ?? ''),
            ])
            ->values()
            ->all();
        $status = count($events) > 0 ? 'healthy' : 'warning';

        return [
            'key'         => 'schedule',
            'label'       => 'Scheduled tasks status',
            'status'      => $status,
            'value'       => count($events).' configured',
            'description' => count($events) > 0
                ? 'Scheduled commands are registered with Laravel.'
                : 'No scheduled commands are registered.',
            'tasks' => $events,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function errorSummary(): array
    {
        $logPath = storage_path('logs/laravel.log');
        $errors  = $this->logErrors($logPath);
        $recent  = collect($errors)->where('is_recent', true)->count();

        return [
            'key'         => 'errors',
            'label'       => 'Error summaries',
            'status'      => $recent > 0 ? 'critical' : (count($errors) > 0 ? 'warning' : 'healthy'),
            'value'       => "{$recent} recent",
            'description' => $recent > 0
                ? 'Recent application errors were found in the Laravel log.'
                : (count($errors) > 0 ? 'Older errors exist in the Laravel log.' : 'No recent error log entries found.'),
            'items' => array_slice($errors, 0, 8),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function failedJobs(): array
    {
        if (! $this->hasTable('failed_jobs')) {
            return [];
        }

        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(8)
            ->get(['id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at'])
            ->map(function (object $job): array {
                $payload = json_decode((string) $job->payload, true);

                return [
                    'id'         => (string) ($job->uuid ?: $job->id),
                    'connection' => (string) $job->connection,
                    'queue'      => (string) $job->queue,
                    'name'       => (string) data_get($payload, 'displayName', data_get($payload, 'job', 'Queued job')),
                    'exception'  => Str::limit(strtok((string) $job->exception, "\n") ?: 'Failed job', 180),
                    'failed_at'  => $this->timestamp($job->failed_at),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentPlatformEvents(): array
    {
        $events = collect();

        if ($this->hasTable('system_setting_histories')) {
            DB::table('system_setting_histories')
                ->orderByDesc('changed_at')
                ->limit(6)
                ->get(['setting_key', 'action', 'changed_by_email', 'changed_at'])
                ->each(fn (object $row) => $events->push([
                    'type'        => 'settings',
                    'label'       => 'System setting '.$row->action,
                    'description' => (string) $row->setting_key,
                    'actor'       => $row->changed_by_email,
                    'occurred_at' => $this->timestamp($row->changed_at),
                ]));
        }

        if ($this->hasTable('design_request_events')) {
            DB::table('design_request_events')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(['event_type', 'message', 'actor_name', 'created_at'])
                ->each(fn (object $row) => $events->push([
                    'type'        => 'design_request',
                    'label'       => Str::headline((string) $row->event_type),
                    'description' => (string) $row->message,
                    'actor'       => $row->actor_name,
                    'occurred_at' => $this->timestamp($row->created_at),
                ]));
        }

        if ($this->hasTable('failed_jobs')) {
            DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(4)
                ->get(['connection', 'queue', 'failed_at'])
                ->each(fn (object $row) => $events->push([
                    'type'        => 'failed_job',
                    'label'       => 'Failed queued job',
                    'description' => "{$row->connection}:{$row->queue}",
                    'actor'       => null,
                    'occurred_at' => $this->timestamp($row->failed_at),
                ]));
        }

        return $events
            ->sortByDesc('occurred_at')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $sections
     */
    private function overallStatus(array $sections): string
    {
        $statuses = collect($sections)->pluck('status');

        if ($statuses->contains('critical')) {
            return 'critical';
        }

        return $statuses->contains('warning') ? 'warning' : 'healthy';
    }

    /**
     * @param  array<string, mixed>  $sections
     */
    private function summary(array $sections): string
    {
        $critical = collect($sections)->where('status', 'critical')->count();
        $warning  = collect($sections)->where('status', 'warning')->count();

        if ($critical > 0) {
            return "{$critical} critical platform issue(s) need attention.";
        }

        if ($warning > 0) {
            return "{$warning} platform check(s) need review.";
        }

        return 'Platform systems are operating normally.';
    }

    private function failedJobCount(): int
    {
        return $this->hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;
    }

    private function recentCount(string $table, int $hours): int
    {
        if (! $this->hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)
            ->where('created_at', '>=', Carbon::now()->subHours($hours))
            ->count();
    }

    private function latestHistory(string $key, string $action): ?object
    {
        if (! $this->hasTable('system_setting_histories')) {
            return null;
        }

        return DB::table('system_setting_histories')
            ->where('setting_key', $key)
            ->where('action', $action)
            ->orderByDesc('changed_at')
            ->first(['changed_by_email', 'changed_by_name', 'changed_at']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function logErrors(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $since = Carbon::now()->subHours(24);

        return collect($this->tailLines($path, 5000))
            ->filter(fn (string $line): bool => Str::contains($line, ['.ERROR:', '.CRITICAL:', '.ALERT:', '.EMERGENCY:']))
            ->take(20)
            ->map(function (string $line) use ($since): array {
                preg_match('/^\[(?<date>[^\]]+)\]\s+(?<env>[^\s]+)\.(?<level>[A-Z]+):\s+(?<message>.*)$/', $line, $matches);
                $date = $matches['date'] ?? null;
                $time = $date ? $this->parseTimestamp($date) : null;

                return [
                    'level'       => $matches['level'] ?? 'ERROR',
                    'message'     => Str::limit($matches['message'] ?? $line, 220),
                    'occurred_at' => $time?->toISOString(),
                    'is_recent'   => $time ? $time->greaterThanOrEqualTo($since) : false,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function tailLines(string $path, int $limit): array
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        try {
            $position  = filesize($path) ?: 0;
            $chunkSize = 8192;
            $maxBytes  = 2 * 1024 * 1024;
            $bytesRead = 0;
            $buffer    = '';

            while ($position > 0 && substr_count($buffer, "\n") <= $limit && $bytesRead < $maxBytes) {
                $readSize = min($chunkSize, $position, $maxBytes - $bytesRead);
                $position -= $readSize;
                $bytesRead += $readSize;

                fseek($handle, $position);
                $buffer = fread($handle, $readSize).$buffer;
            }

            $lines = preg_split('/\r\n|\r|\n/', trim($buffer)) ?: [];

            return collect($lines)
                ->filter(fn (string $line): bool => trim($line) !== '')
                ->take(-$limit)
                ->reverse()
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        } finally {
            fclose($handle);
        }
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function timestamp(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::createFromTimestamp((int) $value)->toISOString();
            }

            return Carbon::parse((string) $value)->toISOString();
        } catch (Throwable) {
            return null;
        }
    }

    private function parseTimestamp(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function directorySize(string $path): int
    {
        if (! is_dir($path)) {
            return 0;
        }

        $size     = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / 1024 / 1024 / 1024, 1).' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
