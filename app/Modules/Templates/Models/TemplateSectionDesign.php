<?php

namespace App\Modules\Templates\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateSectionDesign extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'section_type',
        'section_label',
        'name',
        'design_key',
        'preview_image',
        'fields_json',
        'default_content_json',
        'default_enabled',
        'default_sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fields_json'          => 'array',
            'default_content_json' => 'array',
            'default_enabled'      => 'boolean',
            'default_sort_order'   => 'integer',
            'is_active'            => 'boolean',
        ];
    }
}
