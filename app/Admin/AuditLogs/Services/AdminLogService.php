<?php

namespace App\Admin\AuditLogs\Services;

use App\Admin\AuditLogs\Models\AuditLog;
use App\Shared\Logging\Concerns\NormalizesLogData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminLogService
{
    use NormalizesLogData;

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        if (! $this->tableExists()) {
            return null;
        }

        $payload          = $this->normalizedPayload($data, $actor, $request);
        $payload['scope'] = 'admin';

        return AuditLog::query()->create($payload);
    }

    public function recordModel(
        string $action,
        Model $model,
        ?Authenticatable $actor = null,
        ?Request $request = null,
        mixed $previousValue = null,
        mixed $newValue = null,
        ?string $entityType = null,
        ?string $entityLabel = null,
        array $metadata = [],
    ): ?AuditLog {
        return $this->record([
            'category'       => $this->categoryForAction($action),
            'entity_type'    => $entityType ?? $this->entityType($model),
            'entity_id'      => $model->getKey(),
            'entity_label'   => $entityLabel           ?? $this->entityLabel($model),
            'tenant_id'      => $metadata['tenant_id'] ?? data_get($model, 'tenant_id') ?? ($entityType === 'tenant' ? $model->getKey() : null),
            'action'         => $action,
            'previous_value' => $previousValue,
            'new_value'      => $newValue,
            'metadata'       => $metadata,
        ], $actor, $request);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function activity(string $action, array $data = [], ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        return $this->record(array_merge($data, ['category' => 'activity', 'action' => $action]), $actor, $request);
    }

    public function auth(string $action, array $data = [], ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        return $this->record(array_merge($data, ['category' => 'auth', 'action' => $action]), $actor, $request);
    }

    public function audit(string $action, array $data = [], ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        return $this->record(array_merge($data, ['category' => 'audit', 'action' => $action]), $actor, $request);
    }

    public function error(string $action, array $data = [], ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        return $this->record(array_merge(['severity' => 'error'], $data, ['category' => 'error', 'action' => $action]), $actor, $request);
    }

    public function notification(string $action, array $data = [], ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        return $this->record(array_merge($data, ['category' => 'notification', 'action' => $action]), $actor, $request);
    }

    public function system(string $action, array $data = [], ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        return $this->record(array_merge($data, ['category' => 'system', 'action' => $action]), $actor, $request);
    }

    public function fileUpload(string $action, array $data = [], ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        return $this->record(array_merge($data, ['category' => 'file_upload', 'action' => $action]), $actor, $request);
    }

    public function permission(string $action, array $data = [], ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        return $this->record(array_merge(['severity' => 'warning'], $data, ['category' => 'permission', 'action' => $action]), $actor, $request);
    }

    public function transaction(string $action, array $data = [], ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        return $this->record(array_merge($data, ['category' => 'transaction', 'action' => $action]), $actor, $request);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<AuditLog>
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $sorts = [
            'occurred_at' => 'occurred_at',
            'category'    => 'category',
            'severity'    => 'severity',
            'actor'       => 'actor_name',
            'entity'      => 'entity_label',
            'action'      => 'action',
            'ip_address'  => 'ip_address',
        ];

        $sort      = $sorts[$filters['sort'] ?? 'occurred_at'] ?? 'occurred_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return AuditLog::query()
            ->where('scope', 'admin')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['severity'] ?? null, fn (Builder $query, string $severity) => $query->where('severity', $severity))
            ->when($filters['tenant_id'] ?? null, fn (Builder $query, string $tenantId) => $query->where('tenant_id', $tenantId))
            ->when($filters['actor'] ?? null, function (Builder $query, string $actor): void {
                $query->where(function (Builder $query) use ($actor): void {
                    $query->where('actor_name', 'like', '%'.$actor.'%')
                        ->orWhere('actor_email', 'like', '%'.$actor.'%')
                        ->orWhere('actor_id', $actor);
                });
            })
            ->when($filters['entity_type'] ?? null, fn (Builder $query, string $type) => $query->where('entity_type', $type))
            ->when($filters['entity_id'] ?? null, fn (Builder $query, string $id) => $query->where('entity_id', $id))
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['ip_address'] ?? null, fn (Builder $query, string $ip) => $query->where('ip_address', 'like', '%'.$ip.'%'))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('occurred_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('occurred_at', '<=', $date))
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(
                (int) ($filters['pageSize'] ?? 15),
                ['*'],
                'page',
                (int) ($filters['page'] ?? 1),
            );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function export(array $filters): array
    {
        $filters['pageSize'] = 5000;

        return $this->search($filters)
            ->getCollection()
            ->map(fn (AuditLog $log): array => [
                'occurred_at'  => $log->occurred_at?->toISOString(),
                'scope'        => $log->scope,
                'tenant_id'    => $log->tenant_id,
                'category'     => $log->category,
                'severity'     => $log->severity,
                'action'       => $log->action,
                'actor'        => trim(($log->actor_name ?? '').' <'.($log->actor_email ?? '').'>'),
                'entity_type'  => $log->entity_type,
                'entity_id'    => $log->entity_id,
                'entity_label' => $log->entity_label,
                'ip_address'   => $log->ip_address,
                'user_agent'   => $log->user_agent,
                'summary'      => $this->summary($log),
            ])
            ->all();
    }

    protected function defaultLogScope(): string
    {
        return 'admin';
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('audit_logs');
        } catch (\Throwable) {
            return false;
        }
    }

    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search): void {
            $query->where('actor_name', 'like', '%'.$search.'%')
                ->orWhere('actor_email', 'like', '%'.$search.'%')
                ->orWhere('entity_type', 'like', '%'.$search.'%')
                ->orWhere('entity_label', 'like', '%'.$search.'%')
                ->orWhere('action', 'like', '%'.$search.'%')
                ->orWhere('category', 'like', '%'.$search.'%')
                ->orWhere('severity', 'like', '%'.$search.'%')
                ->orWhere('ip_address', 'like', '%'.$search.'%')
                ->orWhere('tenant_id', $search);
        });
    }

    private function summary(AuditLog $log): string
    {
        $entity = $log->entity_label ?: $log->entity_id ?: $log->entity_type ?: 'system';

        return str($log->action)->headline()->toString().' on '.$entity;
    }
}
