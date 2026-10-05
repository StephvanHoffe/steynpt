<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\BlockedPeriod;
use App\Models\User;
use App\Services\AdminCalendar;
use App\Services\AdminFormat;
use App\Services\AdminLabels;
use App\Services\AgendaServer;
use App\Support\Agenda;
use App\Support\AgendaCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Agenda van Steyn: dag, week, maand en lijst, met details per afspraak en zelf inplannen, verplaatsen of annuleren. */
class AgendaController extends Controller
{
    private const MELDING = ['gepland' => 'Afspraak ingepland.', 'verplaatst' => 'Afspraak verplaatst. De oude afspraak is geannuleerd.'];

    public function index(Request $request): View
    {
        $now = CarbonImmutable::now('UTC');
        $today = Agenda::zonedParts($now)['day'];
        $view = AgendaCalendar::parseView($request->query('weergave'));
        $datum = $request->query('datum');
        $day = is_string($datum) && Agenda::isValidDay($datum) ? $datum : $today;
        $cancelled = $request->query('geannuleerd') === '1';
        $params = ['view' => $view, 'day' => $day, 'cancelled' => $cancelled];
        $selectedId = filter_var($request->query('afspraak'), FILTER_VALIDATE_INT);

        $days = AgendaCalendar::viewDays($view, $day);
        $from = Agenda::zonedTimeToUtc($days[0], '00:00');
        $to = Agenda::zonedTimeToUtc(Agenda::addDays($days[count($days) - 1], 1), '00:00');

        $rows = Appointment::query()
            ->join('users', 'users.id', '=', 'appointments.user_id')
            ->select('appointments.*', 'users.first_name', 'users.last_name')
            ->where('appointments.starts_at', '<', $to)->where('appointments.ends_at', '>', $from)
            ->when(! $cancelled, fn ($q) => $q->where('appointments.status', 'gepland'))
            ->orderBy('appointments.starts_at')->orderBy('appointments.id')
            ->get();
        $windows = Availability::query()->orderBy('weekday')->orderBy('start_time')->get();
        $blocks = BlockedPeriod::query()->where('starts_at', '<', $to)->where('ends_at', '>', $from)->get();
        $selected = $selectedId !== false && $selectedId > 0 ? Appointment::query()->with('user')->find($selectedId) : null;
        if ($selected && ! $selected->user) {
            $selected = null;
        }

        $events = $rows->map(function (Appointment $a) use ($params) {
            $startDay = Agenda::zonedParts($a->starts_at)['day'];

            return [
                'id' => $a->id,
                'day' => $startDay,
                'start' => AgendaCalendar::minutesOfDay($a->starts_at),
                'end' => Agenda::zonedParts($a->ends_at)['day'] === $startDay ? AgendaCalendar::minutesOfDay($a->ends_at) : 24 * 60,
                'startsAt' => $a->starts_at,
                'endsAt' => $a->ends_at,
                'type' => $a->type,
                'typeLabel' => Agenda::getAppointmentType($a->type)['label'] ?? $a->type,
                'location' => $a->location,
                'client' => AdminCalendar::shortName($a->first_name, $a->last_name),
                'cancelled' => $a->status === 'geannuleerd',
                'href' => AdminCalendar::href($params, ['afspraak' => $a->id]),
            ];
        })->all();

        // Vrije periodes per dag (in minuten), voor rooster, maand en lijst.
        $dayBlocks = [];
        foreach ($days as $d) {
            $start = Agenda::zonedTimeToUtc($d, '00:00');
            $end = Agenda::zonedTimeToUtc(Agenda::addDays($d, 1), '00:00');
            foreach ($blocks as $b) {
                if ($b->starts_at->lt($end) && $b->ends_at->gt($start)) {
                    $dayBlocks[] = [
                        'day' => $d,
                        'start' => $b->starts_at->lte($start) ? 0 : AgendaCalendar::minutesOfDay($b->starts_at),
                        'end' => $b->ends_at->gte($end) ? 24 * 60 : AgendaCalendar::minutesOfDay($b->ends_at),
                        'reason' => $b->reason,
                    ];
                }
            }
        }
        $blockedDays = [];
        foreach ($dayBlocks as $b) {
            $blockedDays[$b['day']] = $b['reason'];
        }

        $windowRows = $windows->map(fn (Availability $w) => [
            'weekday' => (int) $w->weekday, 'startTime' => $w->start_time, 'endTime' => $w->end_time, 'location' => $w->location,
        ])->all();
        $weekdays = array_unique(array_map(Agenda::weekdayOf(...), $days));
        $hours = AgendaCalendar::gridHours([
            ...array_map(
                fn (array $w) => ['start' => AgendaCalendar::timeToMinutes($w['startTime']), 'end' => AgendaCalendar::timeToMinutes($w['endTime'])],
                array_values(array_filter($windowRows, fn (array $w) => in_array($w['weekday'], $weekdays, true))),
            ),
            ...$events,
        ]);

        // Aandachtspunten bij de geselecteerde afspraak.
        $warnings = [];
        if ($selected && $selected->status === 'gepland') {
            $a = $selected;
            $start = AgendaCalendar::minutesOfDay($a->starts_at);
            $end = AgendaCalendar::minutesOfDay($a->ends_at);
            $weekday = Agenda::weekdayOf(Agenda::zonedParts($a->starts_at)['day']);
            $fits = collect($windowRows)->contains(fn (array $w) => $w['weekday'] === $weekday && $w['location'] === $a->location
                && AgendaCalendar::timeToMinutes($w['startTime']) <= $start && AgendaCalendar::timeToMinutes($w['endTime']) >= $end);
            if (! $fits) {
                $warnings[] = 'Valt buiten je beschikbaarheid voor '.(Agenda::getAgendaLocation($a->location)['label'] ?? $a->location).'.';
            }
            $block = BlockedPeriod::query()->where('starts_at', '<', $a->ends_at)->where('ends_at', '>', $a->starts_at)->first();
            if ($block) {
                $warnings[] = 'Valt in een vrije periode'.($block->reason ? " ({$block->reason})" : '').'.';
            }
            $overlap = Appointment::query()->whereKeyNot($a->id)->where('status', 'gepland')
                ->where('starts_at', '<', $a->ends_at)->where('ends_at', '>', $a->starts_at)->exists();
            if ($overlap) {
                $warnings[] = 'Overlapt met een andere afspraak.';
            }
        }

        $planned = count(array_filter($events, fn (array $e) => ! $e['cancelled']));
        $melding = $request->query('melding');

        return view('admin.agenda.index', [
            'now' => $now,
            'today' => $today,
            'params' => $params,
            'view' => $view,
            'day' => $day,
            'cancelled' => $cancelled,
            'days' => $days,
            'events' => $events,
            'windows' => $windowRows,
            'dayBlocks' => $dayBlocks,
            'blockedDays' => $blockedDays,
            'hours' => $hours,
            'selected' => $selected,
            'warnings' => $warnings,
            'planned' => $planned,
            'period' => self::periodTitle($view, $days, $day),
            'newHref' => '/admin/agenda/nieuw?datum='.($day < $today ? $today : $day),
            'melding' => is_string($melding) ? (self::MELDING[$melding] ?? null) : null,
        ]);
    }

    /** @return array{title: string, eyebrow: ?string} */
    private static function periodTitle(string $view, array $days, string $day): array
    {
        $first = Agenda::dayToDate($days[0]);
        $last = Agenda::dayToDate($days[count($days) - 1]);
        if ($view === 'dag') {
            return ['title' => AdminFormat::longDayYear(Agenda::dayToDate($day)), 'eyebrow' => 'Week '.AgendaCalendar::isoWeek($day)];
        }
        if ($view === 'maand') {
            return ['title' => AdminFormat::monthYear(Agenda::dayToDate($day)), 'eyebrow' => null];
        }

        return [
            'title' => AdminFormat::dayMonthShort($first).' – '.AdminFormat::dayMonthShortYear($last),
            'eyebrow' => $view === 'week' ? 'Week '.AgendaCalendar::isoWeek($days[0]) : count($days).' dagen',
        ];
    }

    /** Nieuwe afspraak, of een bestaande verplaatsen (?verplaats=id). */
    public function create(Request $request): View
    {
        $today = Agenda::zonedParts(CarbonImmutable::now('UTC'))['day'];
        $replacesId = filter_var($request->query('verplaats'), FILTER_VALIDATE_INT);

        $members = User::query()->where('role', 'member')->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'coaching_status']);
        $options = $members->map(fn (User $m) => [
            'id' => $m->id,
            'name' => "{$m->first_name} {$m->last_name}",
            'note' => $m->coaching_status === 'geen' ? '' : 'coaching '.mb_strtolower(AdminLabels::coaching($m->coaching_status)),
        ])->all();
        $old = $replacesId !== false && $replacesId > 0 ? Appointment::query()->find($replacesId) : null;
        $moving = $old && $old->status === 'gepland' ? $old : null;

        $datum = $request->query('datum');
        $tijd = $request->query('tijd');
        $day = is_string($datum) && Agenda::isValidDay($datum) && $datum >= $today ? $datum
            : ($moving ? Agenda::zonedParts($moving->starts_at)['day'] : $today);
        $time = is_string($tijd) && preg_match('/^\d{2}:\d{2}$/D', $tijd) ? $tijd
            : ($moving ? Agenda::zonedParts($moving->starts_at)['time'] : '09:00');
        $lid = is_string($request->query('lid')) ? $request->query('lid') : null;
        $locked = $moving ? collect($options)->firstWhere('id', $moving->user_id) : null;

        return view('admin.agenda.new', [
            'today' => $today,
            'moving' => $moving,
            'members' => $options,
            'locked' => $locked,
            'defaults' => [
                'userId' => $moving?->user_id ?? (collect($options)->contains('id', $lid) ? $lid : null),
                'type' => $moving?->type,
                'location' => $moving?->location,
                'day' => $day,
                'time' => $time,
                'note' => $moving?->note,
                'replaces' => $moving?->id,
            ],
        ]);
    }

    /**
     * Steyn plant zelf een afspraak in, of verplaatst er een (replaces): dan wordt de nieuwe afspraak gemaakt en de
     * oude in dezelfde transactie geannuleerd. Steyn mag buiten de beschikbaarheid plannen; alleen dubbel boeken
     * wordt tegengehouden.
     */
    public function store(Request $request): RedirectResponse
    {
        // Laravel maakt van lege velden null; het formulier stuurt altijd tekst.
        $str = fn (string $key) => is_string($request->input($key)) ? $request->input($key) : '';
        $input = ['userId' => $str('userId'), 'type' => $str('type'), 'location' => $str('location'), 'day' => $str('day'), 'time' => $str('time')];
        $note = trim($str('note'));
        $replacesRaw = $request->input('replaces');

        $errors = [];
        if ($input['userId'] === '') {
            $errors['userId'] = 'Kies een klant';
        }
        if ($input['type'] === '') {
            $errors['type'] = 'Kies een soort afspraak';
        }
        if ($input['location'] === '') {
            $errors['location'] = 'Kies een locatie';
        }
        if (! Agenda::isValidDay($input['day'])) {
            $errors['day'] = 'Kies een datum';
        }
        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', $input['time'])) {
            $errors['time'] = 'Vul een tijd in';
        }
        if (mb_strlen($note) > 500) {
            $errors['note'] = 'Maximaal 500 tekens';
        }
        $replaces = null;
        if ($replacesRaw !== null && $replacesRaw !== '') {
            $replaces = filter_var($replacesRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($replaces === false) {
                $errors['replaces'] = 'Ongeldige afspraak';
            }
        }

        $type = Agenda::getAppointmentType($input['type']);
        if (! $errors && ! $type) {
            $errors['type'] = 'Kies een soort afspraak';
        }
        if (! $errors && ! in_array($input['location'], $type['locations'], true)) {
            $errors['location'] = "{$type['label']} kan niet op deze locatie";
        }
        if (! $errors) {
            $start = Agenda::zonedTimeToUtc($input['day'], $input['time']);
            $end = $start->addMinutes($type['minutes']);
            if ($start->lt(CarbonImmutable::now('UTC'))) {
                $errors['time'] = 'Kies een tijdstip in de toekomst';
            }
        }
        if ($errors) {
            return back()->withErrors($errors, 'appointment')->withInput();
        }

        $result = AgendaServer::withBookingLock(function () use ($input, $note, $replaces, $type, $start, $end) {
            if (! User::query()->whereKey($input['userId'])->exists()) {
                return ['error' => 'Deze klant bestaat niet meer.'];
            }
            $old = $replaces ? Appointment::query()->whereKey($replaces)->where('status', 'gepland')->first() : null;
            if ($replaces && ! $old) {
                return ['error' => 'De afspraak die je wilt verplaatsen is al geannuleerd.'];
            }
            // Opnieuw controleren binnen de lock: zo kan een tijdstip nooit dubbel geboekt worden.
            $clash = Appointment::query()
                ->join('users', 'users.id', '=', 'appointments.user_id')
                ->select('appointments.starts_at', 'appointments.ends_at', 'users.first_name', 'users.last_name')
                ->where('appointments.status', 'gepland')
                ->where('appointments.starts_at', '<', $end)->where('appointments.ends_at', '>', $start)
                ->when($replaces, fn ($q) => $q->where('appointments.id', '!=', $replaces))
                ->first();
            if ($clash) {
                return ['error' => "Op dit tijdstip staat al een afspraak met {$clash->first_name} {$clash->last_name} ("
                    .Agenda::zonedParts($clash->starts_at)['time'].'–'.Agenda::zonedParts($clash->ends_at)['time'].').'];
            }

            $now = CarbonImmutable::now('UTC');
            $row = Appointment::query()->create([
                'user_id' => $input['userId'],
                'type' => $type['id'],
                'location' => $input['location'],
                'starts_at' => $start,
                'ends_at' => $end,
                'note' => $note !== '' ? $note : ($old?->note ?: null),
            ]);
            $old?->update(['status' => 'geannuleerd', 'cancelled_by' => 'steyn', 'cancelled_at' => $now]);

            return ['id' => $row->id];
        });

        if (isset($result['error'])) {
            return back()->with('appointment_error', $result['error'])->withInput();
        }

        return redirect('/admin/agenda?'.http_build_query([
            'weergave' => 'dag', 'datum' => $input['day'], 'afspraak' => $result['id'], 'melding' => $replaces ? 'verplaatst' : 'gepland',
        ]));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $id = filter_var($request->input('id'), FILTER_VALIDATE_INT);
        if ($id !== false) {
            Appointment::query()->whereKey($id)->where('status', 'gepland')
                ->update(['status' => 'geannuleerd', 'cancelled_by' => 'steyn', 'cancelled_at' => CarbonImmutable::now('UTC')]);
        }

        return back();
    }
}
