<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class ContactRequest extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    /** Zoals in de privacyverklaring: contactaanvragen zonder vervolg verwijderen we uiterlijk na 12 maanden. */
    public const KEEP_MONTHS = 12;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['handled' => 'boolean'];
    }

    /** Wordt een vervolg, dan staan de gegevens in het account; de aanvraag zelf is dan niet meer nodig. */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subMonths(self::KEEP_MONTHS));
    }
}
