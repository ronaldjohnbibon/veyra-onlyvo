<?php

namespace App\Admin\Templates\Models;

use App\Admin\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'website_type_id',
        'name',
        'slug',
        'template_key',
        'business_name',
        'logo',
        'contact_info',
        'social_links',
        'content',
        'font_family',
        'primary_color',
        'secondary_color',
        'background_color',
        'text_color',
        'status',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'contact_info' => 'array',
            'social_links' => 'array',
            'content'      => 'array',
            'is_default'   => 'boolean',
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('business_name', 'like', '%'.$search.'%');
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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
