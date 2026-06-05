<?php

namespace App\Admin\DesignRequests\Models;

use App\Admin\Templates\Models\Template;
use App\Admin\Tenants\Models\Tenant;
use App\Admin\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignRequest extends Model
{
    use HasFactory, HasUuids;

    public const STATUSES = [
        'pending',
        'under_review',
        'approved',
        'changes_requested',
        'rejected',
        'completed',
    ];

    public const PRIORITIES = [
        'low',
        'normal',
        'high',
        'urgent',
    ];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'assigned_to',
        'title',
        'description',
        'notes',
        'reference_links',
        'mockup_concept',
        'status',
        'priority',
        'due_at',
        'sla_due_at',
        'admin_remarks',
        'internal_notes',
        'notification_requested',
        'notification_sent_at',
        'conversion_type',
        'conversion_payload',
        'converted_at',
        'converted_by',
        'linked_template_id',
        'linked_site_url',
        'reviewed_by',
        'reviewed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'reference_links'        => 'array',
            'due_at'                 => 'datetime',
            'sla_due_at'             => 'datetime',
            'notification_requested' => 'boolean',
            'notification_sent_at'   => 'datetime',
            'conversion_payload'     => 'array',
            'converted_at'           => 'datetime',
            'reviewed_at'            => 'datetime',
            'completed_at'           => 'datetime',
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['tenant_id'] ?? null, fn ($query, string $tenantId) => $query->where('tenant_id', $tenantId))
            ->when($filters['assigned_to'] ?? null, fn ($query, string $assigneeId) => $query->where('assigned_to', $assigneeId))
            ->when($filters['priority'] ?? null, fn ($query, string $priority) => $query->where('priority', $priority));
    }

    public function files(): HasMany
    {
        return $this->hasMany(DesignRequestFile::class)->oldest();
    }

    public function events(): HasMany
    {
        return $this->hasMany(DesignRequestEvent::class)->oldest();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function linkedTemplate(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'linked_template_id');
    }
}
