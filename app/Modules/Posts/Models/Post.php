<?php

namespace App\Modules\Posts\Models;

use App\Modules\Templates\Models\Template;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'template_id',
        'title',
        'slug',
        'content',
        'featured_image',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('content', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status));
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
