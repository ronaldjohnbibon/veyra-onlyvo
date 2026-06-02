<?php

namespace App\Shared\SystemSettings\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSettingHistory extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'setting_key',
        'previous_value',
        'new_value',
        'changed_by_user_id',
        'changed_by_name',
        'changed_by_email',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_value' => 'json',
            'new_value'      => 'json',
            'changed_at'     => 'datetime',
        ];
    }
}
