<?php

namespace App\Modules\Templates\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateSection extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'template_id',
        'section_type',
        'design_key',
        'sort_order',
        'is_enabled',
        'content_json',
    ];

    protected function casts(): array
    {
        return [
            'sort_order'   => 'integer',
            'is_enabled'   => 'boolean',
            'content_json' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
