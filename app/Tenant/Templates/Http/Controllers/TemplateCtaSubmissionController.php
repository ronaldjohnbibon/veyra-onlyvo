<?php

namespace App\Tenant\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\SystemSettings\Services\SystemSettingService;
use App\Tenant\Templates\Http\Requests\TemplateCtaSubmissionRequest;
use App\Tenant\Templates\Models\TemplateCtaSubmission;
use Illuminate\Http\JsonResponse;

class TemplateCtaSubmissionController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function store(TemplateCtaSubmissionRequest $request): JsonResponse
    {
        if (! $this->settings->featureEnabled('enable_cta_forms')) {
            return $this->error('CTA forms are disabled.', 403);
        }

        $submission = TemplateCtaSubmission::create([
            'template_id' => $request->validated('template_id'),
            'cta_type'    => $request->validated('cta_type'),
            'payload'     => $request->validated('payload'),
            'status'      => 'new',
        ]);

        return $this->success([
            'id' => $submission->id,
        ], 'Submission received.', 201);
    }
}
