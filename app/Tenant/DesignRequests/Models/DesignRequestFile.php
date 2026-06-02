<?php

namespace App\Tenant\DesignRequests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignRequestFile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'design_request_id',
        'name',
        'path',
        'mime_type',
        'size',
    ];

    public function designRequest(): BelongsTo
    {
        return $this->belongsTo(DesignRequest::class);
    }
}
