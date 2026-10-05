<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryCode extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }
}
