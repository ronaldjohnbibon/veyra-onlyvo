<?php

namespace App\Tenant\SystemSettings\Models;

use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sprout\Attributes\TenantRelation;
use Sprout\Database\Eloquent\Concerns\BelongsToTenant;

class TenantSystemSettingHistory extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'setting_key',
        'action',
        'previous_value',
        'new_value',
        'changed_by_user_id',
        'changed_by_name',
        'changed_by_email',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_value' => 'json',
            'new_value'      => 'json',
            'changed_at'     => 'datetime',
        ];
    }

    #[TenantRelation]
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
