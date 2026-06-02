<?php

namespace App\Admin\Sidebar\Models;

use App\Admin\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sidebar extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'is_admin',
        'tenant_id',
        'name',
        'data',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'data'     => 'array',
            'is_admin' => 'boolean',
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query->when($filters['search'] ?? null, function ($query, string $search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
