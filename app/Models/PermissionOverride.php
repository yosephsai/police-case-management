<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionOverride extends Model
{
    protected $fillable = [
        'role',
        'permission',
        'allowed',
    ];

    protected function casts(): array
    {
        return [
            'allowed' => 'boolean',
        ];
    }
}
