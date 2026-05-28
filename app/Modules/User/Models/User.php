<?php

namespace App\Modules\User\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Auth\Enums\UserType;
use App\Modules\DesignRequests\Models\DesignRequest;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Sprout\Attributes\TenantRelation;
use Sprout\Database\Eloquent\Concerns\BelongsToTenant;

class User extends Authenticatable
{
    use BelongsToTenant, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'is_active',
        'user_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'user_type'         => UserType::class,
        ];
    }

    #[TenantRelation]
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function designRequests(): HasMany
    {
        return $this->hasMany(DesignRequest::class);
    }
}
