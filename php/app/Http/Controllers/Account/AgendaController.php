<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AgendaServer;
use App\Support\Agenda;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Throwable;

/** Agenda van de klant: komende afspraken, een afspraak boeken (stap voor stap) en afzeggen. */
class AgendaController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $now = CarbonImmutable::now('UTC');
        $upcoming = Appointment::query()->where('user_id', $user->id)->where('status', 'gepland')->where('ends_at', '>', $now)->orderBy('starts_at')->get();
        $booked = is_string($request->query('geboekt')) ? $upcoming->firstWhere('id', (int) $request->query('geboekt')) : null;

        $type = Agenda::getAppointmentType(is_string($request->query('type')) ? $request->query('type') : null);
        $locations = $type ? AgendaServer::locationsWithAvailability($type) : [];
        $requested = is_string($request->query('locatie')) ? $request->query('locatie') : null;
        $location = $type && $requested && in_array($requested, $locations, true) ? $requested : (count($locations) === 1 ? $locations[0] : null);
        $days = $type && $location ? AgendaServer::availableDays($type, $location, $now) : [];
        $datum = $request->query('datum');
        $day = Agenda::isValidDay($datum) && in_array($datum, array_column($days, 'day'), true) ? $datum : null;
        $slots = $type && $location && $day ? AgendaServer::slotsForDay($type, $location, $day, $now) : [];

        return view('account.agenda', compact('user', 'now', 'upcoming', 'booked', 'type', 'locations', 'location', 'days', 'day', 'slots'));
    }

    public function book(Request $request): RedirectResponse
    {
        $user = $request->user();
        $start = self::parseIso($request->input('start'));
        $note = trim((string) $request->input('note', ''));
        if (! $start || mb_strlen($note) > 500 || ! is_string($request->input('type')) || ! is_string($request->input('location'))) {
            return back()->with('booking_error', 'Kies een tijd om te boeken.');
        }
        $type = Agenda::getAppointmentType($request->input('type'));
        $location = $request->input('location');
        if (! $type || ! in_array($location, $type['locations'], true)) {
            return back()->with('booking_error', 'Dit afspraaktype of deze locatie bestaat niet.');
        }
        if (($type['requiresCoaching'] ?? false) && $user->coaching_status !== 'actief') {
            return back()->with('booking_error', 'Deze afspraak is alleen voor klanten met actieve online coaching.');
        }

        $now = CarbonImmutable::now('UTC');
        $result = AgendaServer::withBookingLock(function () use ($user, $type, $location, $start, $now, $note) {
            $upcoming = Appointment::query()->where('user_id', $user->id)->where('status', 'gepland')->where('starts_at', '>', $now)->pluck('type');
            if ($upcoming->count() >= Agenda::BOOKING_RULES['maxUpcomingPerClient']) {
                return 'max';
            }
            if (isset($type['maxUpcoming']) && $upcoming->filter(fn ($t) => $t === $type['id'])->count() >= $type['maxUpcoming']) {
                return 'type-max';
            }
            // Opnieuw berekenen binnen het slot: zo kan een tijdslot nooit dubbel geboekt worden.
            $slots = AgendaServer::slotsForDay($type, $location, Agenda::zonedParts($start)['day'], $now);
            if (! collect($slots)->contains(fn ($s) => $s->getTimestamp() === $start->getTimestamp())) {
                return 'taken';
            }

            return Appointment::query()->create([
                'user_id' => $user->id,
                'type' => $type['id'],
                'location' => $location,
                'starts_at' => $start,
                'ends_at' => $start->addMinutes($type['minutes']),
                'note' => $note !== '' ? $note : null,
            ])->id;
        });

        return match ($result) {
            'max' => back()->with('booking_error', 'Je hebt al '.Agenda::BOOKING_RULES['maxUpcomingPerClient'].' afspraken staan. Neem contact op als je meer wilt plannen.'),
            'type-max' => back()->with('booking_error', 'Je hebt al een '.mb_strtolower($type['label']).' gepland.'),
            'taken' => back()->with('booking_error', 'Dit tijdstip is net niet meer beschikbaar. Kies een ander tijdstip.'),
            default => redirect("/account/agenda?geboekt={$result}"),
        };
    }

    public function cancel(Request $request): RedirectResponse
    {
        $id = filter_var($request->input('id'), FILTER_VALIDATE_INT);
        $appointment = $id ? Appointment::query()->where('id', $id)->where('user_id', $request->user()->id)->first() : null;
        if ($appointment && $appointment->status === 'gepland'
            && $appointment->starts_at->getTimestamp() - time() >= Agenda::BOOKING_RULES['cancelUntilHours'] * 3600) {
            $appointment->forceFill(['status' => 'geannuleerd', 'cancelled_by' => 'klant', 'cancelled_at' => CarbonImmutable::now('UTC')])->save();
        }

        return back();
    }

    /** Losse afspraak als .ics, zodat de klant hem in de eigen agenda kan zetten. */
    public function ics(Request $request, string $id): Response
    {
        $user = $request->user();
        if (! $user) {
            return response('Log eerst in', 401);
        }
        $appointment = ctype_digit($id) ? Appointment::query()->where('id', (int) $id)->where('user_id', $user->id)->first() : null;
        if (! $appointment) {
            return response('Niet gevonden', 404);
        }

        return response(Agenda::buildIcs([AgendaServer::toCalendarEvent($appointment, $user, 'klant')], 'SteynPT'), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"steynpt-afspraak-{$appointment->id}.ics\"",
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Tijdstip zoals z.iso.datetime({ offset: true }): 2026-10-07T07:00:00.000Z of met +02:00. */
    private static function parseIso(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:?\d{2})$/', $value)) {
            return null;
        }
        try {
            return CarbonImmutable::parse($value)->setTimezone('UTC');
        } catch (Throwable) {
            return null;
        }
    }
}
