<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\BlockedPeriod;
use App\Services\AgendaServer;
use App\Site\Site;
use App\Support\Agenda;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Instellingen van de agenda: beschikbaarheid per week, vrije dagen en de koppeling met de eigen agenda (iCal). */
class AgendaSettingsController extends Controller
{
    private const TIME = '/^([01]\d|2[0-3]):[0-5]\d$/D';

    public function show(Request $request): View
    {
        $now = CarbonImmutable::now('UTC');
        $feedUrl = self::origin($request).'/ical/'.AgendaServer::icalToken().'.ics';

        return view('admin.agenda.settings', [
            'today' => Agenda::zonedParts($now)['day'],
            'windows' => Availability::query()->orderBy('weekday')->orderBy('start_time')->get(),
            'blocks' => BlockedPeriod::query()->where('ends_at', '>', $now)->orderBy('starts_at')->get(),
            'feedUrl' => $feedUrl,
            'webcalUrl' => preg_replace('/^https?:/', 'webcal:', $feedUrl),
        ]);
    }

    /** Adres van de site: APP_URL, of anders het domein van het verzoek (zoals siteOrigin() in de Next.js-versie). */
    private static function origin(Request $request): string
    {
        $configured = Site::url();
        if ($configured !== '' && ! str_contains($configured, 'localhost')) {
            return $configured;
        }

        return $request->getSchemeAndHttpHost();
    }

    public function addAvailability(Request $request): RedirectResponse
    {
        $weekday = filter_var($request->input('weekday'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 7]]);
        $start = (string) $request->input('startTime', '');
        $end = (string) $request->input('endTime', '');
        $location = (string) $request->input('location', '');
        $error = match (true) {
            $weekday === false, $location === '' => 'Controleer de invoer.',
            ! preg_match(self::TIME, $start), ! preg_match(self::TIME, $end) => 'Vul een tijd in',
            $start >= $end => 'De eindtijd moet na de begintijd liggen',
            default => null,
        };
        if ($error) {
            return back()->with('availability_error', $error)->withInput();
        }
        Availability::query()->create(['weekday' => $weekday, 'start_time' => $start, 'end_time' => $end, 'location' => $location]);

        return back()->with('availability_success', 'Beschikbaarheid toegevoegd.');
    }

    public function deleteAvailability(Request $request): RedirectResponse
    {
        $id = filter_var($request->input('id'), FILTER_VALIDATE_INT);
        if ($id !== false) {
            Availability::query()->whereKey($id)->delete();
        }

        return back();
    }

    public function addBlock(Request $request): RedirectResponse
    {
        $from = (string) $request->input('fromDay', '');
        $to = (string) $request->input('toDay', '');
        $reason = trim((string) $request->input('reason', ''));
        $error = match (true) {
            ! Agenda::isValidDay($from) => 'Kies een begindatum',
            ! Agenda::isValidDay($to) => 'Kies een einddatum',
            mb_strlen($reason) > 200 => 'De reden mag maximaal 200 tekens hebben',
            $from > $to => 'De einddatum moet na de begindatum liggen',
            default => null,
        };
        if ($error) {
            return back()->with('block_error', $error)->withInput();
        }

        // Hele dagen blokkeren, van 00:00 op de eerste dag tot 00:00 op de dag na de laatste dag.
        $startsAt = Agenda::zonedTimeToUtc($from, '00:00');
        $endsAt = Agenda::zonedTimeToUtc(Agenda::addDays($to, 1), '00:00');
        BlockedPeriod::query()->create(['starts_at' => $startsAt, 'ends_at' => $endsAt, 'reason' => $reason !== '' ? $reason : null]);

        $n = Appointment::query()->where('status', 'gepland')->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)->count();

        return back()->with('block_success', $n ? "Periode geblokkeerd. Let op: er staan nog {$n} afspraken in deze periode." : 'Periode geblokkeerd.');
    }

    public function deleteBlock(Request $request): RedirectResponse
    {
        $id = filter_var($request->input('id'), FILTER_VALIDATE_INT);
        if ($id !== false) {
            BlockedPeriod::query()->whereKey($id)->delete();
        }

        return back();
    }

    /** Nieuwe geheime link: de oude werkt direct niet meer. */
    public function rotateIcalToken(): RedirectResponse
    {
        AgendaServer::rotateIcalToken();

        return redirect('/admin/agenda/instellingen#ical');
    }
}
