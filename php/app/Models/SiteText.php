<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteText extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public const CREATED_AT = null;

    protected $guarded = [];
}
