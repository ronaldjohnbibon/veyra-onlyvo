<?php

namespace App\Tenant\Templates\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateCtaSubmission extends Model
{
    use HasFactory, HasUuids;

    public const STATUSES = [
        'new',
        'contacted',
        'closed',
        'spam',
        'archived',
    ];

    public const TENANT_EDITABLE_STATUSES = [
        'new',
        'contacted',
        'archived',
    ];

    protected $fillable = [
        'template_id',
        'cta_type',
        'payload',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
