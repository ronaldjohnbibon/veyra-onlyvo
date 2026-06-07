<?php

namespace App\Tenant\Templates\Services;

use App\Tenant\Templates\Models\TemplateCtaSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TemplateCtaSubmissionService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(string $tenantId, array $filters): Builder
    {
        $sortMap = [
            'created_at' => 'template_cta_submissions.created_at',
            'status'     => 'template_cta_submissions.status',
            'cta_type'   => 'template_cta_submissions.cta_type',
            'template'   => 'templates.business_name',
        ];
        $sort      = $sortMap[(string) ($filters['sort'] ?? 'created_at')] ?? 'template_cta_submissions.created_at';
        $direction = (string) ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return TemplateCtaSubmission::query()
            ->select('template_cta_submissions.*')
            ->join('templates', 'templates.id', '=', 'template_cta_submissions.template_id')
            ->where('templates.tenant_id', $tenantId)
            ->with('template')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('template_cta_submissions.status', $status))
            ->when($filters['template_id'] ?? null, fn (Builder $query, string $templateId) => $query->where('template_cta_submissions.template_id', $templateId))
            ->when($filters['cta_type'] ?? null, fn (Builder $query, string $ctaType) => $query->where('template_cta_submissions.cta_type', $ctaType))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('template_cta_submissions.created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('template_cta_submissions.created_at', '<=', $to))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.Str::lower($search).'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->whereRaw('lower(template_cta_submissions.cta_type) like ?', [$like])
                        ->orWhereRaw('lower(template_cta_submissions.status) like ?', [$like])
                        ->orWhereRaw('lower(coalesce(templates.business_name, templates.name, \'\')) like ?', [$like])
                        ->orWhereRaw('lower(cast(template_cta_submissions.payload as char)) like ?', [$like]);
                });
            })
            ->orderBy($sort, $direction)
            ->orderByDesc('template_cta_submissions.created_at');
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(string $tenantId): array
    {
        $counts = TemplateCtaSubmission::query()
            ->join('templates', 'templates.id', '=', 'template_cta_submissions.template_id')
            ->where('templates.tenant_id', $tenantId)
            ->selectRaw('template_cta_submissions.status, count(*) as total')
            ->groupBy('template_cta_submissions.status')
            ->pluck('total', 'status');

        return [
            'new'       => (int) ($counts['new'] ?? 0),
            'contacted' => (int) ($counts['contacted'] ?? 0),
            'archived'  => (int) ($counts['archived'] ?? 0),
        ];
    }

    public function findForTenant(string $tenantId, string $id): ?TemplateCtaSubmission
    {
        return TemplateCtaSubmission::query()
            ->with('template')
            ->whereKey($id)
            ->whereHas('template', fn ($query) => $query->where('tenant_id', $tenantId))
            ->first();
    }

    public function updateStatus(TemplateCtaSubmission $submission, string $status): TemplateCtaSubmission
    {
        $submission->update(['status' => $status]);

        return $submission->fresh('template');
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(string $tenantId): array
    {
        $templates = TemplateCtaSubmission::query()
            ->join('templates', 'templates.id', '=', 'template_cta_submissions.template_id')
            ->where('templates.tenant_id', $tenantId)
            ->select(['templates.id', 'templates.name', 'templates.business_name'])
            ->distinct()
            ->orderBy('templates.business_name')
            ->orderBy('templates.name')
            ->get()
            ->map(fn (object $template): array => [
                'id'   => (string) $template->id,
                'name' => (string) ($template->business_name ?: $template->name),
            ])
            ->values();

        $ctaTypes = TemplateCtaSubmission::query()
            ->join('templates', 'templates.id', '=', 'template_cta_submissions.template_id')
            ->where('templates.tenant_id', $tenantId)
            ->distinct()
            ->orderBy('template_cta_submissions.cta_type')
            ->pluck('template_cta_submissions.cta_type')
            ->map(fn (string $type): array => [
                'value' => $type,
                'label' => $this->label($type),
            ])
            ->values();

        return [
            'templates' => $templates,
            'cta_types' => $ctaTypes,
            'statuses'  => [
                ['value' => 'new', 'label' => 'New'],
                ['value' => 'contacted', 'label' => 'Contacted'],
                ['value' => 'archived', 'label' => 'Archived'],
            ],
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function exportRows(string $tenantId, array $filters): array
    {
        /** @var EloquentCollection<int, TemplateCtaSubmission> $submissions */
        $submissions = $this->query($tenantId, $filters)
            ->limit(1000)
            ->get();

        return $submissions
            ->map(fn (TemplateCtaSubmission $submission): array => [
                (string) $submission->created_at,
                $this->label((string) $submission->status),
                (string) ($submission->template?->business_name ?: $submission->template?->name),
                $this->label($submission->cta_type),
                $this->payloadSummary($submission->payload),
                json_encode($submission->payload ?? [], JSON_UNESCAPED_SLASHES) ?: '{}',
            ])
            ->values()
            ->all();
    }

    private function payloadSummary(mixed $payload): string
    {
        if (! is_array($payload)) {
            return '';
        }

        return Collection::make($payload)
            ->filter(fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '')
            ->take(3)
            ->map(fn (mixed $value, string|int $key): string => $this->label((string) $key).': '.Str::limit((string) $value, 80))
            ->join(' | ');
    }

    private function label(string $value): string
    {
        return Str::of($value)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
