<?php

namespace App\Admin\Tenants\Models;

use App\Admin\DesignRequests\Models\DesignRequest;
use App\Admin\Sidebar\Models\Sidebar;
use App\Admin\Templates\Models\Template;
use App\Admin\Users\Models\User;
use App\Shared\Enums\UserType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
