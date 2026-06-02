<?php

namespace App\Shared\SystemSettings\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'group',
        'key',
        'label',
        'type',
        'value',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'value'     => 'json',
            'is_public' => 'boolean',
        ];
    }
}
