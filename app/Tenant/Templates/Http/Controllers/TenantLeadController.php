<?php

namespace App\Tenant\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\AuditLogs\Services\TenantLogService;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\Templates\Http\Requests\TemplateCtaSubmissionIndexRequest;
use App\Tenant\Templates\Http\Requests\TemplateCtaSubmissionStatusRequest;
use App\Tenant\Templates\Http\Resources\TemplateCtaSubmissionResource;
use App\Tenant\Templates\Services\TemplateCtaSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantLeadController extends Controller
{
    public function __construct(
        private readonly TemplateCtaSubmissionService $service,
        private readonly SystemSettingService $settings,
        private readonly TenantLogService $logs,
    ) {}

    public function index(TemplateCtaSubmissionIndexRequest $request): JsonResponse
    {
        if ($response = $this->ctaFormsDisabled()) {
            return $response;
        }

        $tenantId = $this->tenantId();
        $filters  = $request->validated();
        $leads    = $this->service->query($tenantId, $filters)
            ->paginate(
                (int) ($filters['pageSize'] ?? 15),
                ['*'],
                'page',
                (int) ($filters['page'] ?? 1),
            );

        $response = $this->success(TemplateCtaSubmissionResource::collection($leads), 'Leads retrieved.');
        $payload  = $response->getData(true);

        $payload['meta'] = [
            'status_counts' => $this->service->statusCounts($tenantId),
            'filters'       => $this->service->filterOptions($tenantId),
        ];

        return response()->json($payload, $response->getStatusCode());
    }

    public function show(string $lead): JsonResponse
    {
        if ($response = $this->ctaFormsDisabled()) {
            return $response;
        }

        $record = $this->service->findForTenant($this->tenantId(), $lead);

        if (! $record) {
            return $this->error('Lead not found.', 404);
        }

        return $this->success(new TemplateCtaSubmissionResource($record), 'Lead retrieved.');
    }

    public function updateStatus(TemplateCtaSubmissionStatusRequest $request, string $lead): JsonResponse
    {
        if ($response = $this->ctaFormsDisabled()) {
            return $response;
        }

        $record = $this->service->findForTenant($this->tenantId(), $lead);

        if (! $record) {
            return $this->error('Lead not found.', 404);
        }

        $previous = $record->attributesToArray();
        $updated  = $this->service->updateStatus($record, $request->validated('status'));
        $this->logs->recordModel('lead.status_updated', $updated, Auth::user(), $request, $previous, $updated->attributesToArray(), 'lead');

        return $this->success(new TemplateCtaSubmissionResource($updated), 'Lead updated.');
    }

    public function export(TemplateCtaSubmissionIndexRequest $request): StreamedResponse|JsonResponse
    {
        if ($response = $this->ctaFormsDisabled()) {
            return $response;
        }

        $rows = $this->service->exportRows($this->tenantId(), $request->validated());

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if (! $handle) {
                return;
            }

            fputcsv($handle, ['Submitted At', 'Status', 'Template', 'CTA Type', 'Summary', 'Payload']);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'tenant-leads.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function tenantId(): string
    {
        $tenantId = Auth::user()?->tenant_id;

        abort_unless($tenantId, 403);

        return (string) $tenantId;
    }

    private function ctaFormsDisabled(): ?JsonResponse
    {
        return $this->settings->featureEnabled('enable_cta_forms')
            ? null
            : $this->error('CTA forms are disabled.', Response::HTTP_FORBIDDEN);
    }
}
