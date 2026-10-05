<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AgendaServer;
use App\Support\Agenda;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;

/** iCal-abonnement voor Steyn: /ical/<token>.ics (Google Agenda, Apple Agenda, Outlook). Zonder inloggen; het token is geheim. */
class IcalController extends Controller
{
    public function feed(string $file): Response
    {
        $token = (string) preg_replace('/\.ics$/', '', $file);
        if (! AgendaServer::isValidIcalToken($token)) {
            return response('Niet gevonden', 404);
        }

        $since = CarbonImmutable::now('UTC')->subDays(60);
        $rows = Appointment::query()->with('user:id,first_name,last_name,email,phone')->where('ends_at', '>', $since)->get();
        $events = $rows->filter(fn (Appointment $a) => $a->user !== null)
            ->map(fn (Appointment $a) => AgendaServer::toCalendarEvent($a, $a->user, 'steyn'))->values()->all();

        return response(Agenda::buildIcs($events, 'SteynPT afspraken'), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="steynpt.ics"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
