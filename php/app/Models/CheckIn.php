<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['weight' => 'float', 'energy' => 'integer', 'sleep' => 'integer', 'nutrition' => 'integer', 'workouts' => 'integer'];
    }
}
