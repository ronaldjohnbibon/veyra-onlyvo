<?php

namespace App\Admin\AuditLogs\Services;

use App\Admin\AuditLogs\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, ?Authenticatable $actor = null, ?Request $request = null): ?AuditLog
    {
        if (! $this->tableExists()) {
            return null;
        }

        return AuditLog::query()->create([
            'actor_type'     => $data['actor_type'] ?? ($actor ? 'admin' : 'system'),
            'actor_id'       => (string) ($data['actor_id'] ?? $actor?->getAuthIdentifier() ?? '') ?: null,
            'actor_name'     => $data['actor_name']  ?? data_get($actor, 'name'),
            'actor_email'    => $data['actor_email'] ?? data_get($actor, 'email'),
            'ip_address'     => $data['ip_address']  ?? $request?->ip(),
            'entity_type'    => $data['entity_type'],
            'entity_id'      => isset($data['entity_id']) ? (string) $data['entity_id'] : null,
            'entity_label'   => $data['entity_label'] ?? null,
            'action'         => $data['action'],
            'previous_value' => $this->normalizeValue($data['previous_value'] ?? null),
            'new_value'      => $this->normalizeValue($data['new_value'] ?? null),
            'metadata'       => $this->normalizeValue($data['metadata'] ?? null),
            'occurred_at'    => $data['occurred_at'] ?? now(),
        ]);
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
            'entity_type'    => $entityType ?? $this->entityType($model),
            'entity_id'      => $model->getKey(),
            'entity_label'   => $entityLabel ?? $this->entityLabel($model),
            'action'         => $action,
            'previous_value' => $previousValue,
            'new_value'      => $newValue,
            'metadata'       => $metadata,
        ], $actor, $request);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<AuditLog>
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $sorts = [
            'occurred_at' => 'occurred_at',
            'actor'       => 'actor_name',
            'entity'      => 'entity_label',
            'action'      => 'action',
            'ip_address'  => 'ip_address',
        ];

        $sort      = $sorts[$filters['sort'] ?? 'occurred_at'] ?? 'occurred_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return AuditLog::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $this->applySearch($query, $search))
            ->when($filters['actor'] ?? null, function (Builder $query, string $actor): void {
                $query->where(function (Builder $query) use ($actor): void {
                    $query->where('actor_name', 'like', '%'.$actor.'%')
                        ->orWhere('actor_email', 'like', '%'.$actor.'%');
                });
            })
            ->when($filters['entity_type'] ?? null, fn (Builder $query, string $type) => $query->where('entity_type', $type))
            ->when($filters['entity_id'] ?? null, fn (Builder $query, string $id) => $query->where('entity_id', $id))
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['ip_address'] ?? null, fn (Builder $query, string $ip) => $query->where('ip_address', 'like', '%'.$ip.'%'))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->where('occurred_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->where('occurred_at', '<=', $date.' 23:59:59'))
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
        $filters['pageSize'] = 1000;

        return $this->search($filters)
            ->getCollection()
            ->map(fn (AuditLog $log): array => [
                'occurred_at'    => $log->occurred_at?->toISOString(),
                'actor'          => trim(($log->actor_name ?? '').' <'.($log->actor_email ?? '').'>'),
                'ip_address'     => $log->ip_address,
                'entity_type'    => $log->entity_type,
                'entity_id'      => $log->entity_id,
                'entity_label'   => $log->entity_label,
                'action'         => $log->action,
                'previous_value' => json_encode($log->previous_value),
                'new_value'      => json_encode($log->new_value),
            ])
            ->all();
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
                ->orWhere('ip_address', 'like', '%'.$search.'%');
        });
    }

    private function entityType(Model $model): string
    {
        return str($model::class)->afterLast('\\')->snake()->toString();
    }

    private function entityLabel(Model $model): ?string
    {
        foreach (['name', 'title', 'key', 'email'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if ($value) {
                return (string) $value;
            }
        }

        return null;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return $value->attributesToArray();
        }

        return $value;
    }
}
