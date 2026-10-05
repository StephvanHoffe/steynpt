<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    protected $table = 'availability';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['weekday' => 'integer'];
    }
}
