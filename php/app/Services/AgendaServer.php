<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Availability;
use App\Models\BlockedPeriod;
use App\Models\Setting;
use App\Models\User;
use App\Site\Texts;
use App\Support\Agenda;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Agenda met de database: vrije tijdsloten, boekbare dagen en de iCal-feed voor Steyn. */
final class AgendaServer
{
    private const ICAL_KEY = 'ical_token';

    /**
     * Alles wat een tijdslot bezet maakt: geplande afspraken en geblokkeerde periodes.
     *
     * @return list<array{startsAt: CarbonImmutable, endsAt: CarbonImmutable}>
     */
    public static function busy(CarbonInterface $from, CarbonInterface $to): array
    {
        $booked = Appointment::query()->where('status', 'gepland')->where('starts_at', '<', $to)->where('ends_at', '>', $from)->get(['starts_at', 'ends_at']);
        $blocked = BlockedPeriod::query()->where('starts_at', '<', $to)->where('ends_at', '>', $from)->get(['starts_at', 'ends_at']);

        return $booked->concat($blocked)->map(fn ($row) => ['startsAt' => $row->starts_at, 'endsAt' => $row->ends_at])->values()->all();
    }

    /** @return list<array{weekday: int, startTime: string, endTime: string, location: string}> */
    public static function windows(): array
    {
        return Availability::query()->get()->map(fn (Availability $w) => [
            'weekday' => (int) $w->weekday,
            'startTime' => $w->start_time,
            'endTime' => $w->end_time,
            'location' => $w->location,
        ])->all();
    }

    /** @return list<CarbonImmutable> */
    public static function slotsForDay(array $type, string $location, string $day, ?CarbonInterface $now = null): array
    {
        $busy = self::busy(Agenda::zonedTimeToUtc($day, '00:00'), Agenda::zonedTimeToUtc(Agenda::addDays($day, 1), '00:00'));

        return Agenda::computeSlots($type, $location, $day, self::windows(), $busy, $now ?? CarbonImmutable::now('UTC'));
    }

    /**
     * Dagen binnen de boekingshorizon met minstens één vrij tijdslot.
     *
     * @return list<array{day: string, slots: int}>
     */
    public static function availableDays(array $type, string $location, ?CarbonInterface $now = null): array
    {
        $now ??= CarbonImmutable::now('UTC');
        $windows = self::windows();
        $today = Agenda::zonedParts($now)['day'];
        $end = Agenda::addDays($today, Agenda::BOOKING_RULES['horizonDays'] + 1);
        $busy = self::busy($now, Agenda::zonedTimeToUtc($end, '00:00'));
        $days = [];
        for ($i = 0; $i <= Agenda::BOOKING_RULES['horizonDays']; $i++) {
            $day = Agenda::addDays($today, $i);
            $slots = count(Agenda::computeSlots($type, $location, $day, $windows, $busy, $now));
            if ($slots) {
                $days[] = ['day' => $day, 'slots' => $slots];
            }
        }

        return $days;
    }

    /**
     * Locaties waar dit type de komende weken überhaupt boekbaar is.
     *
     * @return list<string>
     */
    public static function locationsWithAvailability(array $type): array
    {
        $locations = Availability::query()->distinct()->pluck('location')->all();

        return array_values(array_filter($type['locations'], fn (string $l) => in_array($l, $locations, true)));
    }

    /**
     * Boeken en verplaatsen één voor één: binnen dit slot (en een databasetransactie) wordt opnieuw gecontroleerd of het
     * tijdstip vrij is, zodat een tijdslot nooit dubbel geboekt wordt (ook niet op MySQL, waar een transactie alleen
     * dat niet garandeert).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withBookingLock(callable $callback): mixed
    {
        return Cache::lock('agenda-boeken', 30)->block(10, fn () => DB::transaction($callback));
    }

    // ---------------------------------------------------------------------------
    // iCal-feed voor Steyn

    public static function icalToken(): string
    {
        $token = Setting::get(self::ICAL_KEY);
        if ($token !== null) {
            return $token;
        }
        DB::table('settings')->insertOrIgnore(['key' => self::ICAL_KEY, 'value' => self::newToken()]);

        return Setting::get(self::ICAL_KEY);
    }

    public static function rotateIcalToken(): string
    {
        $token = self::newToken();
        Setting::put(self::ICAL_KEY, $token);

        return $token;
    }

    public static function isValidIcalToken(string $candidate): bool
    {
        $token = Setting::get(self::ICAL_KEY);

        return $token !== null && hash_equals($token, $candidate);
    }

    private static function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }

    /** Afspraak als agenda-item; voor Steyn met klantgegevens, voor de klant zonder. */
    public static function toCalendarEvent(Appointment $appointment, User $client, string $view): array
    {
        $type = Agenda::getAppointmentType($appointment->type);
        $location = Agenda::getAgendaLocation($appointment->location);
        $typeLabel = $type['label'] ?? $appointment->type;
        $name = "{$client->first_name} {$client->last_name}";

        return [
            'uid' => "appointment-{$appointment->id}@steynpt.nl",
            'start' => $appointment->starts_at,
            'end' => $appointment->ends_at,
            'summary' => $view === 'steyn' ? "{$typeLabel} – {$name}" : "{$typeLabel} met Steyn (SteynPT)",
            'location' => ! $location ? $appointment->location
                : (in_array($location['id'], ['online', 'op-locatie'], true) ? $location['label'] : "{$location['label']}, ".Texts::agendaAddress($location)),
            'description' => $view === 'steyn'
                ? implode("\n", array_filter([$name, $client->phone ? "Tel: {$client->phone}" : null, "E-mail: {$client->email}", $appointment->note ? "Notitie: {$appointment->note}" : null]))
                : 'Afzeggen kan tot 24 uur van tevoren via Mijn omgeving op steynpt.nl.',
            'cancelled' => $appointment->status === 'geannuleerd',
            'updatedAt' => $appointment->cancelled_at ?? $appointment->created_at,
        ];
    }
}
