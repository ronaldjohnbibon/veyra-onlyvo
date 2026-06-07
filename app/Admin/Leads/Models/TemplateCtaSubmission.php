<?php

namespace App\Admin\Leads\Models;

use App\Admin\Templates\Models\Template;
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

    protected $table = 'template_cta_submissions';

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
