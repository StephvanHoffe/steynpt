<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CheckIn;
use App\Models\User;
use App\Services\AdminCounts;
use App\Services\Plans\PipelineServer;
use App\Site\Texts;
use App\Support\Agenda;
use App\Support\AgendaCalendar;
use App\Support\Weeks;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Overzicht van het beheer: vandaag, wat er te doen is en de stand van de schema's. */
class OverviewController extends Controller
{
    public function index(Request $request): View
    {
        $now = CarbonImmutable::now('UTC');
        $today = Agenda::zonedParts($now)['day'];
        $dayStart = Agenda::zonedTimeToUtc($today, '00:00');
        $dayEnd = Agenda::zonedTimeToUtc(Agenda::addDays($today, 1), '00:00');
        $weekStart = Agenda::zonedTimeToUtc(AgendaCalendar::startOfWeek($today), '00:00');
        $weekEnd = Agenda::zonedTimeToUtc(Agenda::addDays(AgendaCalendar::startOfWeek($today), 7), '00:00');

        $withClient = fn () => Appointment::query()
            ->join('users', 'users.id', '=', 'appointments.user_id')
            ->select('appointments.*', 'users.first_name', 'users.last_name', 'users.phone')
            ->where('appointments.status', 'gepland')
            ->orderBy('appointments.starts_at');

        $hour = (int) substr(Agenda::zonedParts($now)['time'], 0, 2);

        return view('admin.overview', [
            'admin' => $request->user(),
            'now' => $now,
            'today' => $today,
            'greeting' => $hour < 12 ? 'Goedemorgen' : ($hour < 18 ? 'Goedemiddag' : 'Goedenavond'),
            'vriendenactie' => Texts::get('algemeen')['vriendenactie'],
            'counts' => AdminCounts::get(),
            'pipeline' => PipelineServer::load(),
            'todays' => $withClient()->where('appointments.starts_at', '>=', $dayStart)->where('appointments.starts_at', '<', $dayEnd)->get(),
            'upcoming' => $withClient()->where('appointments.starts_at', '>=', $dayEnd)->limit(5)->get(),
            'rewardsDue' => User::query()
                ->join('users as referrer', 'referrer.id', '=', 'users.referred_by_id')
                ->select('users.id', 'users.first_name', 'users.last_name', 'referrer.first_name as referrer_first', 'referrer.last_name as referrer_last')
                ->where('users.coaching_status', 'actief')
                ->whereNotNull('users.referred_by_id')
                ->whereNull('users.referral_reward_at')
                ->get(),
            'week' => Appointment::query()->where('status', 'gepland')->where('starts_at', '>=', $weekStart)->where('starts_at', '<', $weekEnd)->count(),
            'active' => User::query()->where('coaching_status', 'actief')->count(),
            'newMembers' => User::query()->where('role', 'member')->where('created_at', '>', $now->subDays(30))->count(),
            'checks' => CheckIn::query()->where('week', Weeks::isoWeekKey($now->setTimezone(Agenda::TIME_ZONE)))->count(),
        ]);
    }

    /** Vriendenactie: de korting voor de uitnodiger is verrekend. */
    public function markReferralReward(Request $request): RedirectResponse
    {
        $friendId = (string) $request->input('friendId', '');
        if ($friendId !== '') {
            User::query()->whereKey($friendId)->whereNull('referral_reward_at')->update(['referral_reward_at' => CarbonImmutable::now('UTC')]);
        }

        return back();
    }
}
