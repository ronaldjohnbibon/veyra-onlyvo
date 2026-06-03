<?php

namespace App\Tenant\SystemSettings\Services;

use App\Tenant\DesignRequests\Models\DesignRequest;
use App\Tenant\Templates\Models\TemplateCtaSubmission;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TenantNotificationService
{
    public function __construct(
        private readonly TenantSystemSettingService $settings,
    ) {}

    public function sendCtaSubmission(TemplateCtaSubmission $submission, Tenant $tenant): void
    {
        $recipient = $submission->template?->content['cta_recipient_email'] ?? null;
        $recipient = is_string($recipient) && $recipient !== ''
            ? $recipient
            : $this->settings->string($tenant, 'notifications.cta_submission_email');

        if ($recipient === '') {
            return;
        }

        $subject = 'New CTA submission for '.$this->settings->string($tenant, 'profile.business_name', $tenant->name);
        $body    = $this->messageBody([
            'Tenant'   => $tenant->name,
            'Template' => $submission->template?->name,
            'CTA Type' => $submission->cta_type,
            'Status'   => $submission->status,
            'Payload'  => $submission->payload,
        ]);

        $this->sendRaw($tenant, $recipient, $subject, $body);
    }

    public function sendDesignRequest(DesignRequest $request, Tenant $tenant): void
    {
        $recipient = $this->settings->string($tenant, 'notifications.design_request_email');

        if ($recipient === '') {
            return;
        }

        $subject = 'New design request for '.$this->settings->string($tenant, 'profile.business_name', $tenant->name);
        $body    = $this->messageBody([
            'Tenant'      => $tenant->name,
            'Title'       => $request->title,
            'Status'      => $request->status,
            'Description' => $request->description,
            'Requester'   => $request->requester?->email,
        ]);

        $this->sendRaw($tenant, $recipient, $subject, $body);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function sendWeeklyAnalyticsSummary(Tenant $tenant, array $summary): void
    {
        $recipient = $this->settings->string($tenant, 'notifications.cta_submission_email')
            ?: $this->settings->string($tenant, 'notifications.design_request_email');

        if ($recipient === '') {
            return;
        }

        $subject = 'Weekly analytics summary for '.$this->settings->string($tenant, 'profile.business_name', $tenant->name);
        $body    = $this->messageBody([
            'Tenant'                 => $tenant->name,
            'Total Visits'           => $summary['total_visits']           ?? 0,
            'Unique Visitors'        => $summary['unique_visitors']        ?? 0,
            'Last 7 Days Visits'     => $summary['last_7_days_visits']     ?? 0,
            'Total CTA Events'       => $summary['total_cta_events']       ?? 0,
            'Last 7 Days CTA Events' => $summary['last_7_days_cta_events'] ?? 0,
            'Unique CTA Visitors'    => $summary['unique_cta_visitors']    ?? 0,
            'Today Visits'           => $summary['today_visits']           ?? 0,
            'Today CTA Events'       => $summary['today_cta_events']       ?? 0,
        ]);

        $this->sendRaw($tenant, $recipient, $subject, $body);
    }

    /**
     * @param  array<string, mixed>  $lines
     */
    private function messageBody(array $lines): string
    {
        return collect($lines)
            ->map(function (mixed $value, string $label): string {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                }

                return $label.': '.($value ?: '-');
            })
            ->implode("\n");
    }

    private function sendRaw(Tenant $tenant, string $recipient, string $subject, string $body): void
    {
        try {
            Mail::raw($body, function ($message) use ($tenant, $recipient, $subject): void {
                $message->to($recipient)->subject($subject);

                $replyTo = $this->settings->string($tenant, 'notifications.reply_to_email');

                if ($replyTo !== '') {
                    $message->replyTo($replyTo);
                }
            });
        } catch (Throwable $exception) {
            Log::warning('Tenant notification email failed.', [
                'tenant_id' => $tenant->id,
                'recipient' => $recipient,
                'subject'   => $subject,
                'error'     => $exception->getMessage(),
            ]);
        }
    }
}
