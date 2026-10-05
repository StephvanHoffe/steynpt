<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedPeriod extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }
}
