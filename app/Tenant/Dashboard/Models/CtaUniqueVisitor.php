<?php

namespace App\Tenant\Dashboard\Models;

use App\Tenant\Templates\Models\Template;
use App\Tenant\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CtaUniqueVisitor extends Model
{
    use HasUuids;

    protected $table = 'cta_unique_visitors';

    protected $fillable = [
        'tenant_id',
        'template_id',
        'cta_identifier',
        'cta_label',
        'cta_type',
        'ip_address',
        'user_agent',
        'visitor_hash',
        'event_date',
        'first_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date'    => 'date',
            'first_seen_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
