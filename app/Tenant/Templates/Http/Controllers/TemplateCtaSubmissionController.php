<?php

namespace App\Tenant\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\SystemSettings\Services\TenantNotificationService;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use App\Tenant\Templates\Http\Requests\TemplateCtaSubmissionRequest;
use App\Tenant\Templates\Models\TemplateCtaSubmission;
use Illuminate\Http\JsonResponse;
use Sprout\Contracts\Tenant as CurrentTenant;

class TemplateCtaSubmissionController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly TenantSystemSettingService $tenantSettings,
        private readonly TenantNotificationService $notifications,
    ) {}

    public function store(TemplateCtaSubmissionRequest $request, CurrentTenant $tenant): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_cta_forms')) {
            return $this->error('CTA forms are disabled.', 403);
        }

        if (! $this->tenantSettings->boolean($tenant, 'website.contact_form_enabled', true)) {
            return $this->error('Contact forms are disabled.', 403);
        }

        $submission = TemplateCtaSubmission::create([
            'template_id' => $request->validated('template_id'),
            'cta_type'    => $request->validated('cta_type'),
            'payload'     => $request->validated('payload'),
            'status'      => 'new',
        ]);

        $this->notifications->sendCtaSubmission($submission->load('template'), $tenant);

        return $this->success([
            'id' => $submission->id,
        ], 'Submission received.', 201);
    }
}
