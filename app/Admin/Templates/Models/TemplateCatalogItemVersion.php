<?php

namespace App\Admin\Templates\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateCatalogItemVersion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'template_catalog_item_id',
        'version',
        'action',
        'changelog',
        'snapshot',
        'created_by_user_id',
        'created_by_name',
        'created_by_email',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(TemplateCatalogItem::class, 'template_catalog_item_id');
    }
}
