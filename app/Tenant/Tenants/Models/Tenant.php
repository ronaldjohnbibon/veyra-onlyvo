<?php

namespace App\Tenant\Tenants\Models;

use App\Tenant\Dashboard\Models\VisitorUniqueVisitor;
use App\Tenant\Dashboard\Models\VisitorVisit;
use App\Shared\Enums\UserType;
use App\Tenant\DesignRequests\Models\DesignRequest;
use App\Tenant\Sidebar\Models\Sidebar;
use App\Tenant\Templates\Models\Template;
use App\Tenant\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sprout\Contracts\Tenant as SproutTenant;
use Sprout\Database\Eloquent\Concerns\IsTenant;

class Tenant extends Model implements SproutTenant
{
    use HasFactory, HasUuids, IsTenant, SoftDeletes;

    protected $fillable = [
        'name',
        'subdomain',
        'settings',
        'timezone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function getTenantIdentifierName(): string
    {
        return 'subdomain';
    }

    public function sidebars(): HasMany
    {
        return $this->hasMany(Sidebar::class);
    }

    public function designRequests(): HasMany
    {
        return $this->hasMany(DesignRequest::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function visitorVisits(): HasMany
    {
        return $this->hasMany(VisitorVisit::class);
    }

    public function visitorUniqueVisitors(): HasMany
    {
        return $this->hasMany(VisitorUniqueVisitor::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)
            ->where('user_type', UserType::TENANT)
            ->oldestOfMany();
    }
}
