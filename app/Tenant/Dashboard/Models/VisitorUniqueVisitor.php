<?php

namespace App\Tenant\Dashboard\Models;

use App\Tenant\Templates\Models\Template;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sprout\Attributes\TenantRelation;
use Sprout\Database\Eloquent\Concerns\BelongsToTenant;

class VisitorUniqueVisitor extends Model
{
    use BelongsToTenant, HasUuids;

    protected $table = 'visitor_unique_visitors';

    protected $fillable = [
        'tenant_id',
        'template_id',
        'ip_address',
        'user_agent',
        'visitor_hash',
        'visit_date',
        'first_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'visit_date'    => 'date',
            'first_seen_at' => 'datetime',
        ];
    }

    #[TenantRelation]
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
