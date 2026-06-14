<?php

namespace App\Tenant\AuditLogs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\AuditLogs\Http\Requests\AuditLogIndexRequest;
use App\Tenant\AuditLogs\Http\Resources\AuditLogResource;
use App\Tenant\AuditLogs\Services\TenantLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Sprout\Contracts\Tenant as CurrentTenant;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantAuditLogController extends Controller
{
    public function __construct(
        private readonly TenantLogService $service,
    ) {}

    public function index(AuditLogIndexRequest $request, CurrentTenant $tenant): JsonResponse
    {
        $logs = $this->service->search($this->tenantId($tenant), $request->validated());

        return $this->success(AuditLogResource::collection($logs), 'Audit logs retrieved.');
    }

    public function show(string $log, CurrentTenant $tenant): JsonResponse
    {
        $record = $this->service->find($this->tenantId($tenant), $log);

        if (! $record) {
            return $this->error('Audit log not found.', 404);
        }

        return $this->success(new AuditLogResource($record), 'Audit log retrieved.');
    }

    public function export(AuditLogIndexRequest $request, CurrentTenant $tenant): StreamedResponse
    {
        $rows = $this->service->export($this->tenantId($tenant), $request->validated());

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

    private function tenantId(CurrentTenant $tenant): string
    {
        $tenantId     = (string) $tenant->getTenantKey();
        $userTenantId = Auth::user()?->tenant_id;

        abort_unless($userTenantId && hash_equals($tenantId, (string) $userTenantId), 403);

        return $tenantId;
    }
}
