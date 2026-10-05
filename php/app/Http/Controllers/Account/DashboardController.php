<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CheckIn;
use App\Models\Intake;
use App\Models\Measurement;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plans\Schedule;
use App\Site\Site;
use App\Site\Texts;
use App\Support\Intake as IntakeRules;
use App\Support\Weeks;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Mijn omgeving: dashboard, wekelijkse check-in en online coaching aanvragen. */
class DashboardController extends Controller
{
    public const STATUS = [
        'geen' => ['label' => 'Nog niet gestart', 'tone' => 'bg-surface text-ink'],
        'aangevraagd' => ['label' => 'Aanvraag ontvangen', 'tone' => 'bg-accent-tint text-accent'],
        'actief' => ['label' => 'Actief', 'tone' => 'bg-ink text-white'],
        'gepauzeerd' => ['label' => 'Gepauzeerd', 'tone' => 'bg-surface text-ink'],
        'gestopt' => ['label' => 'Gestopt', 'tone' => 'bg-surface text-muted'],
    ];

    public function show(Request $request): View
    {
        $user = $request->user();
        $now = CarbonImmutable::now('UTC');
        // Ingeplande schema's waarvan de startdag is aangebroken, worden nu zichtbaar.
        Schedule::activateDuePlans();

        $checkIns = CheckIn::query()->where('user_id', $user->id)->orderByDesc('week')->limit(52)->get();
        $intakeRow = Intake::query()->find($user->id);
        [$intake] = $intakeRow ? IntakeRules::validate($intakeRow->data ?? []) : [null];
        $week = Weeks::isoWeekKey($now->setTimezone('Europe/Amsterdam'));
        $onlinePlans = Texts::onlinePlans();
        $vriendenactie = Texts::get('algemeen')['vriendenactie'];

        return view('account.dashboard', [
            'user' => $user,
            'now' => $now,
            'welkom' => $request->query('welkom'),
            'intakeParam' => $request->query('intake'),
            'checkIns' => $checkIns,
            'friends' => User::query()->where('referred_by_id', $user->id)->orderByDesc('created_at')->get(['first_name', 'coaching_status', 'referral_reward_at']),
            'intake' => $intake,
            'plans' => Plan::query()->where('user_id', $user->id)->where('status', '!=', 'vervangen')->orderByDesc('created_at')->orderByDesc('id')
                ->get(['id', 'type', 'status', 'published_at', 'starts_on']),
            'upcoming' => Appointment::query()->where('user_id', $user->id)->where('status', 'gepland')->where('ends_at', '>', $now)->orderBy('starts_at')->get(),
            'measurements' => Measurement::query()->where('user_id', $user->id)->orderBy('measured_at')->get(),
            'week' => $week,
            'checkedInThisWeek' => $checkIns->contains('week', $week),
            'streak' => Weeks::checkInStreak($checkIns->pluck('week')->all(), $now->setTimezone('Europe/Amsterdam')),
            'onlinePlans' => $onlinePlans,
            'plan' => collect($onlinePlans)->firstWhere('id', $user->plan),
            'goal' => Site::goalLabel($user->goal),
            'vriendenactie' => $vriendenactie,
            'referralUrl' => self::origin($request).'/r/'.$user->referral_code,
            'status' => self::STATUS[$user->coaching_status] ?? self::STATUS['geen'],
        ]);
    }

    /** Adres van de site voor de uitnodigingslink: APP_URL, of anders het adres waarop de site nu draait. */
    private static function origin(Request $request): string
    {
        return config('app.url') && ! str_contains((string) config('app.url'), 'localhost') ? Site::url() : $request->getSchemeAndHttpHost();
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $user = $request->user();
        $values = $request->only('energy', 'sleep', 'nutrition', 'workouts', 'weight', 'note');
        $errors = [];
        $data = [];
        foreach (['energy' => 'energie', 'sleep' => 'slaap', 'nutrition' => 'voeding'] as $field => $label) {
            $v = trim((string) ($values[$field] ?? ''));
            if (! preg_match('/^\d+$/', $v) || (int) $v < 1 || (int) $v > 5) {
                $errors[$field] = "Geef een score voor {$label}";
            } else {
                $data[$field] = (int) $v;
            }
        }
        $workouts = trim((string) ($values['workouts'] ?? ''));
        $workouts = $workouts === '' ? '0' : $workouts;
        if (! preg_match('/^\d+$/', $workouts) || (int) $workouts > 21) {
            $errors['workouts'] = 'Aantal trainingen klopt niet';
        } else {
            $data['workouts'] = (int) $workouts;
        }
        $weight = trim((string) ($values['weight'] ?? ''));
        if ($weight === '') {
            $data['weight'] = null;
        } else {
            $kg = str_replace(',', '.', $weight);
            if (! is_numeric($kg) || (float) $kg <= 25 || (float) $kg >= 350) {
                $errors['weight'] = 'Vul een geldig gewicht in (kg)';
            } else {
                $data['weight'] = (float) $kg;
            }
        }
        $note = trim((string) ($values['note'] ?? ''));
        if (mb_strlen($note) > 1000) {
            $errors['note'] = 'Je bericht mag maximaal 1000 tekens hebben';
        }
        if ($errors) {
            return back()->withErrors($errors, 'checkin')->withInput();
        }

        $now = CarbonImmutable::now('UTC');
        $week = Weeks::isoWeekKey($now->setTimezone('Europe/Amsterdam'));
        $inserted = CheckIn::query()->insertOrIgnore([...$data, 'user_id' => $user->id, 'week' => $week, 'note' => $note !== '' ? $note : null, 'created_at' => $now]);
        if ($inserted === 0) {
            return back()->with('checkin_error', 'Je hebt deze week al ingecheckt. Tot volgende week!');
        }
        $streak = Weeks::checkInStreak(CheckIn::query()->where('user_id', $user->id)->pluck('week')->all(), $now->setTimezone('Europe/Amsterdam'));

        return back()->with('checkin_success', $streak > 1 ? "Check-in opgeslagen. {$streak} weken op rij, goed bezig!" : 'Check-in opgeslagen. Steyn kijkt ernaar.');
    }

    public function requestCoaching(Request $request): RedirectResponse
    {
        $user = $request->user();
        $plan = collect(Texts::onlinePlans())->firstWhere('id', (string) $request->input('plan', ''));
        if (! $plan) {
            return back()->with('coaching_error', 'Kies een pakket om te starten.');
        }
        if ($user->coaching_status === 'actief') {
            return back()->with('coaching_error', 'Je online coaching is al actief.');
        }
        $user->forceFill(['plan' => $plan['id'], 'coaching_status' => 'aangevraagd'])->save();

        return back()->with('coaching_success', "Je aanvraag voor Online coaching {$plan['name']} is ontvangen. Steyn neemt binnen 24 uur contact met je op.");
    }
}
