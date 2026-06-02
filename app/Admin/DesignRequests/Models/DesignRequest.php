<?php

namespace App\Admin\DesignRequests\Models;

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
        'rejected',
        'completed',
    ];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'description',
        'notes',
        'reference_links',
        'mockup_concept',
        'status',
        'admin_remarks',
        'reviewed_by',
        'reviewed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'reference_links' => 'array',
            'reviewed_at'     => 'datetime',
            'completed_at'    => 'datetime',
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
            ->when($filters['tenant_id'] ?? null, fn ($query, string $tenantId) => $query->where('tenant_id', $tenantId));
    }

    public function files(): HasMany
    {
        return $this->hasMany(DesignRequestFile::class)->oldest();
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
}
