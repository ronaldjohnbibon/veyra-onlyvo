<?php

namespace App\Modules\Templates\Models;

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
        'name',
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
    ];

    protected function casts(): array
    {
        return [
            'contact_info' => 'array',
            'social_links' => 'array',
        ];
    }

    public function scopeFilter($query, array $filters)
    {
        return $query->when($filters['search'] ?? null, function ($query, string $search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('business_name', 'like', '%'.$search.'%');
            });
        });
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TemplateSection::class)->orderBy('sort_order');
    }

    #[TenantRelation]
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
