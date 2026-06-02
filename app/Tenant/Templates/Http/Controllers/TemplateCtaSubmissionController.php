<?php

namespace App\Tenant\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Tenant\Templates\Http\Requests\TemplateCtaSubmissionRequest;
use App\Tenant\Templates\Models\TemplateCtaSubmission;
use Illuminate\Http\JsonResponse;

class TemplateCtaSubmissionController extends Controller
{
    public function store(TemplateCtaSubmissionRequest $request): JsonResponse
    {
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
