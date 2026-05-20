<?php

namespace App\Modules\Tenant\Models;

use App\Modules\Sidebar\Models\Sidebar;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
