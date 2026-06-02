<?php

namespace App\Tenant\Templates\Models;

use App\Tenant\Dashboard\Models\CtaEvent;
use App\Tenant\Dashboard\Models\CtaUniqueVisitor;
use App\Tenant\Dashboard\Models\VisitorUniqueVisitor;
use App\Tenant\Dashboard\Models\VisitorVisit;
use App\Tenant\Templates\Posts\Models\Post;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
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

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->latest('published_at')->latest();
    }

    public function ctaSubmissions(): HasMany
    {
        return $this->hasMany(TemplateCtaSubmission::class)->latest();
    }

    public function visitorVisits(): HasMany
    {
        return $this->hasMany(VisitorVisit::class);
    }

    public function visitorUniqueVisitors(): HasMany
    {
        return $this->hasMany(VisitorUniqueVisitor::class);
    }

    public function ctaEvents(): HasMany
    {
        return $this->hasMany(CtaEvent::class);
    }

    public function ctaUniqueVisitors(): HasMany
    {
        return $this->hasMany(CtaUniqueVisitor::class);
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
