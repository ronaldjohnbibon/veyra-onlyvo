<?php

namespace App\Admin\AuditLogs\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'actor_name',
        'actor_email',
        'ip_address',
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
}
