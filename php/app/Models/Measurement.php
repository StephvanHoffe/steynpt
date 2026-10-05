<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    public const UPDATED_AT = null;

    public const FIELDS = ['weight', 'body_fat', 'muscle_mass', 'waist', 'hip', 'chest', 'arm', 'thigh'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_fill_keys(self::FIELDS, 'float') + ['measured_at' => 'datetime'];
    }
}
