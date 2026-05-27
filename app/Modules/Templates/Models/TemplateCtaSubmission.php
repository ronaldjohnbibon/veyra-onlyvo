<?php

namespace App\Modules\Templates\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateCtaSubmission extends Model
{
    use HasFactory, HasUuids;

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
