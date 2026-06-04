<?php

namespace App\Tenant\DesignRequests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignRequestEvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'design_request_id',
        'actor_type',
        'actor_name',
        'actor_id',
        'event_type',
        'from_status',
        'to_status',
        'message',
    ];

    public function designRequest(): BelongsTo
    {
        return $this->belongsTo(DesignRequest::class);
    }
}
