<?php

namespace App\Modules\Analytics\Models;

use App\Modules\Templates\Models\Template;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CtaEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'template_id',
        'cta_identifier',
        'cta_label',
        'cta_type',
        'event_type',
        'url',
        'ip_address',
        'user_agent',
        'referrer',
        'visitor_hash',
        'event_date',
        'triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date'   => 'date',
            'triggered_at' => 'datetime',
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
