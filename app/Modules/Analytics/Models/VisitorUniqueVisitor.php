<?php

namespace App\Modules\Analytics\Models;

use App\Modules\Templates\Models\Template;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorUniqueVisitor extends Model
{
    use HasUuids;

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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
