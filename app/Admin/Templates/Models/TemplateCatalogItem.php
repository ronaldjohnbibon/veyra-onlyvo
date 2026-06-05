<?php

namespace App\Admin\Templates\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateCatalogItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'website_type_id',
        'key',
        'name',
        'description',
        'preview_image',
        'field_schema',
        'default_content',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'field_schema'    => 'array',
            'default_content' => 'array',
            'is_active'       => 'boolean',
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('key', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['website_type_id'] ?? null, function ($query, string $websiteTypeId): void {
                $query->where('website_type_id', $websiteTypeId);
            });
    }

    public function websiteType(): BelongsTo
    {
        return $this->belongsTo(WebsiteType::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TemplateCatalogItemVersion::class);
    }
}
