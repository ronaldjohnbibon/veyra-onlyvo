<?php

namespace App\Tenant\AuditLogs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\AuditLogs\Http\Requests\AuditLogIndexRequest;
use App\Tenant\AuditLogs\Http\Resources\AuditLogResource;
use App\Tenant\AuditLogs\Models\AuditLog;
use App\Tenant\AuditLogs\Services\TenantLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantAuditLogController extends Controller
{
    public function __construct(
        private readonly TenantLogService $service,
    ) {}

    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        $logs = $this->service->search($this->tenantId(), $request->validated());

        return $this->success(AuditLogResource::collection($logs), 'Audit logs retrieved.');
    }

    public function show(string $log): JsonResponse
    {
        $record = AuditLog::withoutTenantRestrictions(fn () => AuditLog::query()
            ->where('tenant_id', $this->tenantId())
            ->whereKey($log)
            ->first());

        if (! $record) {
            return $this->error('Audit log not found.', 404);
        }

        return $this->success(new AuditLogResource($record), 'Audit log retrieved.');
    }

    public function export(AuditLogIndexRequest $request): StreamedResponse
    {
        $rows = $this->service->export($this->tenantId(), $request->validated());

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if (! $handle) {
                return;
            }

            fputcsv($handle, ['Time', 'Category', 'Severity', 'Action', 'Actor', 'Entity Type', 'Entity ID', 'Entity Label', 'IP', 'User Agent', 'Summary']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['occurred_at'],
                    $row['category'],
                    $row['severity'],
                    $row['action'],
                    $row['actor'],
                    $row['entity_type'],
                    $row['entity_id'],
                    $row['entity_label'],
                    $row['ip_address'],
                    $row['user_agent'],
                    $row['summary'],
                ]);
            }

            fclose($handle);
        }, 'tenant-audit-logs.csv', ['Content-Type' => 'text/csv']);
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return $tenantId;
    }
}
