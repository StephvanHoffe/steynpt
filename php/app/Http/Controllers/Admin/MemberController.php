<?php

namespace App\Http\Controllers\Admin;

use App\Auth\Accounts;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CheckIn;
use App\Models\Intake as IntakeRow;
use App\Models\Measurement;
use App\Models\User;
use App\Services\Plans\PipelineServer;
use App\Site\Texts;
use App\Support\Agenda;
use App\Support\Intake;
use App\Support\Progress;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Leden: lijst met zoeken en filters, en per lid coaching, schema's, metingen, afspraken en beveiliging. */
class MemberController extends Controller
{
    /** Filters boven de ledenlijst, in deze volgorde. */
    public const FILTERS = ['alle', 'aangevraagd', 'actief', 'gepauzeerd', 'gestopt', 'geen'];

    public function index(Request $request): View
    {
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $status = in_array($request->query('status'), User::COACHING_STATUSES, true) ? $request->query('status') : 'alle';
        $now = CarbonImmutable::now('UTC');

        $all = User::query()->where('role', 'member')->orderBy('first_name')->orderBy('last_name')->get();
        $nextBy = [];
        foreach (Appointment::query()->where('status', 'gepland')->where('starts_at', '>', $now)->orderBy('starts_at')->get(['user_id', 'starts_at']) as $a) {
            $nextBy[$a->user_id] ??= $a->starts_at;
        }
        $checkBy = CheckIn::query()->selectRaw('user_id, max(week) as week')->groupBy('user_id')->pluck('week', 'user_id')->all();

        $needle = mb_strtolower($q);
        $searched = $all->filter(fn (User $u) => $needle === ''
            || str_contains(mb_strtolower("{$u->first_name} {$u->last_name} {$u->email} ".($u->phone ?? '')), $needle))->values();
        $members = $status === 'alle' ? $searched : $searched->where('coaching_status', $status)->values();
        $countFor = fn (string $f) => $f === 'alle' ? $searched->count() : $searched->where('coaching_status', $f)->count();
        $href = function (string $f) use ($q) {
            $p = [];
            if ($q !== '') {
                $p['q'] = $q;
            }
            if ($f !== 'alle') {
                $p['status'] = $f;
            }

            return '/admin/leden'.($p ? '?'.http_build_query($p) : '');
        };

        return view('admin.members.index', [
            'q' => $q,
            'status' => $status,
            'total' => $all->count(),
            'members' => $members,
            'filters' => array_map(fn (string $f) => ['id' => $f, 'count' => $countFor($f), 'href' => $href($f)], self::FILTERS),
            'nextBy' => $nextBy,
            'checkBy' => $checkBy,
        ]);
    }

    public function show(string $id): View
    {
        $member = User::query()->find($id) ?? abort(404);
        $now = CarbonImmutable::now('UTC');
        $intakeRow = IntakeRow::query()->find($id);
        [$intake] = $intakeRow && is_array($intakeRow->data) ? Intake::validate($intakeRow->data) : [null];

        return view('admin.members.show', [
            'member' => $member,
            'now' => $now,
            'today' => Agenda::zonedParts($now)['day'],
            'planName' => Texts::onlinePlanName($member->plan),
            'vriendenactie' => Texts::get('algemeen')['vriendenactie'],
            'intake' => $intake,
            'intakeUpdatedAt' => $intakeRow?->updated_at,
            'pipeline' => PipelineServer::load($member->id),
            'measurements' => Measurement::query()->where('user_id', $id)->orderBy('measured_at')->orderBy('id')->get(),
            'upcoming' => Appointment::query()->where('user_id', $id)->where('status', 'gepland')->where('ends_at', '>', $now)->orderBy('starts_at')->get(),
            'inviter' => $member->referred_by_id ? User::query()->find($member->referred_by_id, ['first_name', 'last_name']) : null,
            'codesLeft' => $member->totp_enabled_at ? Accounts::remainingRecoveryCodes($member) : 0,
            'passwordChanged' => Accounts::passwordChangedAt($member),
        ]);
    }

    /** Coachingstatus en het bericht in het dashboard van de klant. */
    public function updateCoaching(Request $request, string $id): RedirectResponse
    {
        $member = User::query()->find($id) ?? abort(404);
        $status = $request->input('coachingStatus');
        $note = $request->input('coachNote');
        $note = is_string($note) ? trim($note) : '';
        if (! in_array($status, User::COACHING_STATUSES, true) || mb_strlen($note) > 2000) {
            return back()->with('coaching_error', 'Controleer de invoer.')->withInput($request->only('coachingStatus', 'coachNote'));
        }
        $member->forceFill(['coaching_status' => $status, 'coach_note' => $note !== '' ? $note : null])->save();

        return back()->with('coaching_success', 'Opgeslagen. De klant ziet het bericht direct in Mijn omgeving.');
    }

    public function addMeasurement(Request $request, string $id): RedirectResponse
    {
        $member = User::query()->find($id);
        $to = "/admin/leden/{$id}#metingen";
        if (! $member) {
            return redirect('/admin/leden')->with('measurement_error', 'Lid niet gevonden.');
        }
        [$data, $errors] = Progress::validateMeasurement(Progress::measurementFromFormData($request->except('_token')));
        if ($data === null) {
            return redirect($to)->withErrors($errors, 'measurement')->withInput($request->except('_token'));
        }

        $row = ['user_id' => $member->id, 'note' => $data['note']];
        foreach (Progress::MEASUREMENT_FIELDS as $field) {
            $row[Str::snake($field['key'])] = $data[$field['key']];
        }
        // Datum als 12:00 Nederlandse tijd opslaan (zoals de Next.js-versie: +01:00), zodat hij in elke tijdzone op dezelfde dag valt.
        $row['measured_at'] = CarbonImmutable::parse($data['measuredAt'].'T12:00:00+01:00')->utc();
        Measurement::query()->create($row);

        return redirect($to)->with('measurement_success', 'Meting opgeslagen. De klant ziet hem direct in Mijn omgeving.');
    }

    public function deleteMeasurement(Request $request, string $id): RedirectResponse
    {
        $measurementId = filter_var($request->input('id'), FILTER_VALIDATE_INT);
        if ($measurementId !== false) {
            Measurement::query()->whereKey($measurementId)->where('user_id', $id)->delete();
        }

        return redirect("/admin/leden/{$id}#metingen");
    }

    /** Alleen als het lid zijn telefoon én herstelcodes kwijt is: bij de volgende keer inloggen koppelt het lid opnieuw. */
    public function resetTwoFactor(string $id): RedirectResponse
    {
        $member = User::query()->find($id);
        if ($member && $member->role === 'member') {
            Accounts::clearTwoFactor($member);
        }

        return redirect("/admin/leden/{$id}#beveiliging");
    }
}
