<?php

namespace App\Modules\Templates\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateDesign extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'website_type_id',
        'key',
        'name',
        'description',
        'preview_image',
        'sections',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sections'   => 'array',
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function websiteType(): BelongsTo
    {
        return $this->belongsTo(WebsiteType::class);
    }
}
