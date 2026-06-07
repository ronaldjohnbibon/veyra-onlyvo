<?php

namespace App\Admin\AuditLogs\Http\Controllers;

use App\Admin\AuditLogs\Http\Requests\AuditLogIndexRequest;
use App\Admin\AuditLogs\Http\Resources\AuditLogResource;
use App\Admin\AuditLogs\Models\AuditLog;
use App\Admin\AuditLogs\Services\AuditLogService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AdminAuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogService $service,
    ) {}

    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $logs = $this->service->search($request->validated());

        return $this->success(AuditLogResource::collection($logs), 'Audit logs retrieved.');
    }

    public function show(string $auditLog): JsonResponse
    {
        $this->authorizeAdmin();

        $log = AuditLog::query()->find($auditLog);

        if (! $log) {
            return $this->error('Audit log not found.', 404);
        }

        return $this->success(new AuditLogResource($log), 'Audit log retrieved.');
    }

    public function export(AuditLogIndexRequest $request): Response
    {
        $this->authorizeAdmin();

        $rows = $this->service->export($request->validated());
        $csv  = fopen('php://temp', 'r+');

        fputcsv($csv, ['Timestamp', 'Actor', 'IP Address', 'Entity Type', 'Entity ID', 'Entity Label', 'Action', 'Previous Value', 'New Value']);

        foreach ($rows as $row) {
            fputcsv($csv, [
                $row['occurred_at'],
                $row['actor'],
                $row['ip_address'],
                $row['entity_type'],
                $row['entity_id'],
                $row['entity_label'],
                $row['action'],
                $row['previous_value'],
                $row['new_value'],
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv) ?: '';
        fclose($csv);

        return response($content, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-logs-'.now()->format('Y-m-d-His').'.csv"',
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
