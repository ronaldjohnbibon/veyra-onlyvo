<?php

namespace App\Admin\Leads\Http\Controllers;

use App\Admin\AuditLogs\Services\AuditLogService;
use App\Admin\Leads\Http\Requests\AdminLeadIndexRequest;
use App\Admin\Leads\Http\Requests\AdminLeadStatusRequest;
use App\Admin\Leads\Http\Resources\AdminLeadResource;
use App\Admin\Leads\Models\TemplateCtaSubmission;
use App\Admin\Leads\Services\AdminLeadService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLeadController extends Controller
{
    public function __construct(
        private readonly AdminLeadService $service,
        private readonly AuditLogService $auditLogs,
    ) {}

    public function index(AdminLeadIndexRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $filters = $request->validated();
        $leads   = $this->service->query($filters)
            ->paginate(
                (int) $request->input('pageSize', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1),
            );

        $response        = $this->success(AdminLeadResource::collection($leads), 'Leads retrieved.');
        $payload         = $response->getData(true);
        $payload['meta'] = $this->service->meta($filters);

        return response()->json($payload, $response->getStatusCode());
    }

    public function updateStatus(AdminLeadStatusRequest $request, string $lead): JsonResponse
    {
        $this->authorizeAdmin();

        $record = TemplateCtaSubmission::query()->with('template.tenant')->find($lead);

        if (! $record) {
            return $this->error('Lead not found.', 404);
        }

        $previous = $record->attributesToArray();
        $updated  = $this->service->setStatus($record, $request->validated('status'));
        $this->auditLogs->recordModel('lead.status_updated', $updated, Auth::user(), $request, $previous, $updated->attributesToArray(), 'lead', null, [
            'tenant_id' => data_get($updated, 'template.tenant_id'),
        ]);

        return $this->success(new AdminLeadResource($updated), 'Lead updated.');
    }

    public function export(AdminLeadIndexRequest $request): StreamedResponse
    {
        $this->authorizeAdmin();

        $rows = $this->service->exportRows($request->validated());

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Created At', 'Status', 'Tenant', 'Template', 'CTA Type', 'Summary', 'Payload']);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'admin-leads.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
