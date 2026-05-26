<?php

namespace App\Modules\Templates\Models;

use App\Modules\Posts\Models\Post;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sprout\Attributes\TenantRelation;
use Sprout\Database\Eloquent\Concerns\BelongsToTenant;

class Template extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

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
            'is_default'   => 'boolean',
        ];
    }

    public function scopeFilter($query, array $filters)
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

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->latest('published_at')->latest();
    }

    public function websiteType(): BelongsTo
    {
        return $this->belongsTo(WebsiteType::class);
    }

    #[TenantRelation]
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
