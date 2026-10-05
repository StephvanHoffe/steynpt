<?php

namespace App\Services\Plans;

use App\Models\Plan;
use App\Support\Agenda;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Ingeplande schema's waarvan de startdatum is aangebroken worden gepubliceerd en vervangen het vorige schema.
 * Gebeurt zodra de klant of Steyn een pagina met schema's opent (eenmaal per verzoek) en elke minuut via de cronjob.
 */
final class Schedule
{
    public static function activateDuePlans(): int
    {
        return once(function () {
            $today = Agenda::zonedParts(CarbonImmutable::now('UTC'))['day'];
            $due = Plan::query()->where('status', 'gepland')->where('starts_on', '<=', $today)->orderBy('starts_on')
                ->get(['id', 'user_id', 'type', 'starts_on']);

            foreach ($due as $p) {
                $now = CarbonImmutable::now('UTC');
                DB::transaction(function () use ($p, $today, $now) {
                    $activated = Plan::query()->whereKey($p->id)->where('status', 'gepland')->update([
                        'status' => 'gepubliceerd',
                        'published_at' => Agenda::zonedTimeToUtc($p->starts_on ?? $today, '00:00'),
                        'updated_at' => $now,
                    ]);
                    if (! $activated) {
                        return;
                    }
                    Plan::query()->where('user_id', $p->user_id)->where('type', $p->type)->where('status', 'gepubliceerd')
                        ->whereKeyNot($p->id)->update(['status' => 'vervangen', 'updated_at' => $now]);
                });
            }

            return $due->count();
        });
    }
}
