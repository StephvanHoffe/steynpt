<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trainings- of voedingsschema.
 * genereren -> concept (of fout) -> gepland -> gepubliceerd; oude versies worden "vervangen".
 */
class Plan extends Model
{
    public const TYPES = ['training', 'voeding'];

    public const STATUSES = ['genereren', 'fout', 'concept', 'gepland', 'gepubliceerd', 'vervangen'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['content' => 'array', 'ai_draft' => 'array', 'published_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
