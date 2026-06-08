<?php

namespace App\Tenant\AuditLogs\Models;

use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sprout\Attributes\TenantRelation;
use Sprout\Database\Eloquent\Concerns\BelongsToTenant;

class AuditLog extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $table = 'tenant_audit_logs';

    protected $fillable = [
        'scope',
        'tenant_id',
        'category',
        'severity',
        'actor_type',
        'actor_id',
        'actor_name',
        'actor_email',
        'ip_address',
        'user_agent',
        'entity_type',
        'entity_id',
        'entity_label',
        'action',
        'previous_value',
        'new_value',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'previous_value' => 'array',
        'new_value'      => 'array',
        'metadata'       => 'array',
        'occurred_at'    => 'datetime',
    ];

    #[TenantRelation]
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
