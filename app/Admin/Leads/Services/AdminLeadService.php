<?php

namespace App\Admin\Leads\Services;

use App\Admin\Leads\Models\TemplateCtaSubmission;
use App\Admin\Templates\Models\Template;
use App\Admin\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminLeadService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters): Builder
    {
        $sortMap = [
            'created_at' => 'template_cta_submissions.created_at',
            'status'     => 'template_cta_submissions.status',
            'cta_type'   => 'template_cta_submissions.cta_type',
            'template'   => 'templates.business_name',
            'tenant'     => 'tenants.name',
        ];
        $sort      = $sortMap[(string) ($filters['sort'] ?? 'created_at')] ?? 'template_cta_submissions.created_at';
        $direction = (string) ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return TemplateCtaSubmission::query()
            ->select('template_cta_submissions.*')
            ->join('templates', 'templates.id', '=', 'template_cta_submissions.template_id')
            ->leftJoin('tenants', 'tenants.id', '=', 'templates.tenant_id')
            ->with(['template.tenant'])
            ->when($filters['tenant_id'] ?? null, fn (Builder $query, string $tenantId) => $query->where('templates.tenant_id', $tenantId))
            ->when($filters['template_id'] ?? null, fn (Builder $query, string $templateId) => $query->where('template_cta_submissions.template_id', $templateId))
            ->when($filters['cta_type'] ?? null, fn (Builder $query, string $ctaType) => $query->where('template_cta_submissions.cta_type', $ctaType))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('template_cta_submissions.status', $status))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('template_cta_submissions.created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('template_cta_submissions.created_at', '<=', $to))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.Str::lower($search).'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->whereRaw('lower(template_cta_submissions.cta_type) like ?', [$like])
                        ->orWhereRaw('lower(template_cta_submissions.status) like ?', [$like])
                        ->orWhereRaw('lower(coalesce(templates.business_name, templates.name, \'\')) like ?', [$like])
                        ->orWhereRaw('lower(coalesce(tenants.name, \'\')) like ?', [$like])
                        ->orWhereRaw('lower(cast(template_cta_submissions.payload as char)) like ?', [$like]);
                });
            })
            ->orderBy($sort, $direction)
            ->orderByDesc('template_cta_submissions.created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function meta(array $filters): array
    {
        return [
            'filters'     => $this->filterOptions(),
            'summary'     => $this->summary($filters),
            'trends'      => $this->trends($filters),
            'conversions' => $this->conversions($filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
            'tenants' => Tenant::query()
                ->orderBy('name')
                ->get(['id', 'name', 'subdomain'])
                ->map(fn (Tenant $tenant): array => [
                    'id'        => (string) $tenant->id,
                    'name'      => $tenant->name,
                    'subdomain' => $tenant->subdomain,
                ])
                ->values(),
            'templates' => Template::query()
                ->with('tenant')
                ->orderBy('business_name')
                ->orderBy('name')
                ->get(['id', 'tenant_id', 'name', 'business_name', 'slug'])
                ->map(fn (Template $template): array => [
                    'id'          => (string) $template->id,
                    'name'        => (string) ($template->business_name ?: $template->name),
                    'tenant_id'   => (string) $template->tenant_id,
                    'tenant_name' => $template->tenant?->name,
                    'slug'        => $template->slug,
                ])
                ->values(),
            'cta_types' => TemplateCtaSubmission::query()
                ->distinct()
                ->orderBy('cta_type')
                ->pluck('cta_type')
                ->map(fn (string $type): array => [
                    'value' => $type,
                    'label' => $this->label($type),
                ])
                ->values(),
            'statuses' => collect(TemplateCtaSubmission::STATUSES)
                ->map(fn (string $status): array => [
                    'value' => $status,
                    'label' => $this->label($status),
                ])
                ->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters): array
    {
        $query  = $this->query($filters);
        $counts = (clone $query)
            ->reorder()
            ->select('template_cta_submissions.status')
            ->selectRaw('count(*) as total')
            ->groupBy('template_cta_submissions.status')
            ->pluck('total', 'status');

        return [
            'total'     => (clone $query)->count(),
            'new'       => (int) ($counts['new'] ?? 0),
            'contacted' => (int) ($counts['contacted'] ?? 0),
            'closed'    => (int) ($counts['closed'] ?? 0),
            'spam'      => (int) ($counts['spam'] ?? 0),
            'archived'  => (int) ($counts['archived'] ?? 0),
            'tenants'   => (clone $query)->distinct('templates.tenant_id')->count('templates.tenant_id'),
            'templates' => (clone $query)->distinct('template_cta_submissions.template_id')->count('template_cta_submissions.template_id'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function trends(array $filters): array
    {
        return $this->query($filters)
            ->reorder()
            ->select(DB::raw('date(template_cta_submissions.created_at) as date'))
            ->selectRaw('count(*) as total')
            ->whereDate('template_cta_submissions.created_at', '>=', $filters['from'] ?? now()->subDays(13)->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn (object $row): array => [
                'date'  => (string) $row->date,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function conversions(array $filters): array
    {
        $query     = $this->query($filters);
        $total     = max(1, (clone $query)->count());
        $closed    = (clone $query)->where('template_cta_submissions.status', 'closed')->count();
        $contacted = (clone $query)->whereIn('template_cta_submissions.status', ['contacted', 'closed'])->count();

        return [
            'contact_rate' => round(($contacted / $total) * 100, 1),
            'close_rate'   => round(($closed / $total) * 100, 1),
            'by_cta_type'  => (clone $query)
                ->reorder()
                ->select('template_cta_submissions.cta_type')
                ->selectRaw('count(*) as total')
                ->groupBy('template_cta_submissions.cta_type')
                ->orderByDesc('total')
                ->limit(8)
                ->get()
                ->map(fn (object $row): array => [
                    'cta_type' => (string) $row->cta_type,
                    'label'    => $this->label((string) $row->cta_type),
                    'total'    => (int) $row->total,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<int, string>>
     */
    public function exportRows(array $filters): array
    {
        /** @var EloquentCollection<int, TemplateCtaSubmission> $submissions */
        $submissions = $this->query($filters)->limit(5000)->get();

        return $submissions
            ->map(fn (TemplateCtaSubmission $submission): array => [
                (string) $submission->created_at,
                $this->label((string) $submission->status),
                (string) $submission->template?->tenant?->name,
                (string) ($submission->template?->business_name ?: $submission->template?->name),
                $this->label($submission->cta_type),
                $this->payloadSummary($submission->payload),
                json_encode($submission->payload ?? [], JSON_UNESCAPED_SLASHES) ?: '{}',
            ])
            ->values()
            ->all();
    }

    public function setStatus(TemplateCtaSubmission $submission, string $status): TemplateCtaSubmission
    {
        $submission->update(['status' => $status]);

        return $submission->fresh('template.tenant');
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
        return Str::of($value)->replace(['_', '-'], ' ')->title()->toString();
    }
}
