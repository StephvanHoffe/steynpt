@php
    use App\Site\Locale;
    use App\Support\Agenda;
    $next = $upcoming->first();
    $c = $user->coaching_status;
@endphp
<x-layouts.account>
  <section class="hero-soft">
    <div class="container-site py-10 lg:py-12">
      @if ($welkom || $intakeParam)
        <div role="status" class="mb-8 flex items-start gap-3 rounded-lg border border-accent/30 bg-accent-tint p-4 sm:items-center">
          <x-icon name="PartyPopper" class="size-5 shrink-0 text-accent" />
          <p class="text-sm font-medium">{{ $welkom ? __('Welkom bij SteynPT, :name!', ['name' => $user->first_name]) : '' }}{{ $welkom && $c === 'aangevraagd' ? ' '.__('Steyn neemt binnen 24 uur contact met je op voor je intake.') : '' }}{{ $intakeParam ? ($intakeParam === 'gestart' ? __('Je intake is opgeslagen. We maken nu een eerste opzet van je schema; Steyn controleert het en laat het je weten zodra het klaarstaat.') : __('Je intake is opgeslagen. Steyn gebruikt je gegevens voor je schema.')) : '' }}</p>
        </div>
      @endif
      <div class="grid gap-8 lg:grid-cols-[1.3fr_1fr] lg:items-end">
        <div>
          <p class="eyebrow text-accent">{{ __('Mijn omgeving') }}</p>
          <h1 class="display display-lg mt-3">{{ __('Hoi :name', ['name' => $user->first_name]) }}</h1>
          <p class="mt-3 text-muted">{{ $goal ? __('Doel: :goal', ['goal' => __($goal)]) : __('Stel je doel in via je intake') }}{{ $plan ? ' · '.__('online coaching :plan', ['plan' => $plan['name']]) : '' }}</p>
        </div>
        <div class="card p-5">
          <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-muted"><x-icon name="CalendarDays" class="size-4" /> {{ __('Volgende afspraak') }}</p>
          @if ($next)
            <p class="mt-2 text-lg font-semibold first-letter:uppercase">{{ Agenda::formatDayLong($next->starts_at, Locale::current()) }}, {{ Agenda::formatTime($next->starts_at) }}</p>
            <p class="text-sm text-muted">{{ __(Agenda::getAppointmentType($next->type)['label'] ?? $next->type) }} · {{ __(Agenda::getAgendaLocation($next->location)['label'] ?? $next->location) }}</p>
            <a href="/account/agenda" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold underline decoration-accent underline-offset-4">{{ __('Alle afspraken') }}</a>
          @else
            <p class="mt-2 text-sm text-muted">{{ __('Je hebt nog geen afspraak gepland.') }}</p>
            <a href="/account/agenda" class="btn btn-primary btn-sm mt-3">{{ __('Afspraak maken') }}</a>
          @endif
        </div>
      </div>
    </div>
  </section>

  <div class="container-site grid gap-6 py-10 lg:grid-cols-[1.6fr_1fr] lg:py-12">
    <div class="grid min-w-0 content-start gap-6">
      <x-account.my-plans-card :intake="$intake" :plans="$plans" :coaching-status="$c" />

      {{-- Voortgang --}}
      <section class="card p-6 sm:p-8" aria-labelledby="progress-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 id="progress-title" class="display flex items-center gap-2 text-2xl"><x-icon name="LineChart" class="size-6" /> {{ __('Mijn voortgang') }}</h2>
          @if ($measurements->isNotEmpty())
            <a href="/account/voortgang" class="text-sm font-semibold underline decoration-accent underline-offset-4">{{ __('Alle metingen') }}</a>
          @endif
        </div>
        @if ($measurements->isEmpty())
          <div class="mt-4 rounded-lg bg-surface p-5 text-sm">
            <p>{{ __('Steyn houdt hier je metingen bij, zoals gewicht, vetpercentage en omvang. Na je eerste meting zie je hier je voortgang.') }}</p>
            <a href="/account/agenda?type=meting" class="mt-3 inline-flex items-center gap-1 font-semibold underline decoration-accent underline-offset-4">{{ __('Plan een meting') }} <x-icon name="ArrowRight" class="size-4" /></a>
          </div>
        @else
          <div class="mt-5">
            <x-progress.overview :rows="$measurements" charts="main" />
          </div>
        @endif
      </section>

      {{-- Coaching --}}
      <section class="card p-6 sm:p-8" aria-labelledby="coaching-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 id="coaching-title" class="display text-2xl">{{ __('Online coaching') }}</h2>
          <span class="rounded px-2.5 py-1 text-xs font-semibold uppercase tracking-wider {{ $status['tone'] }}">{{ __($status['label']) }}</span>
        </div>

        @if ($c === 'geen' || $c === 'gestopt')
          <div class="mt-5">
            <p class="text-muted">{{ $c === 'geen'
                ? __('Klaar voor de volgende stap? Kies je pakket en Steyn neemt binnen 24 uur contact met je op voor een intake. Je betaalt pas als je na de intake start.')
                : __('Zin om weer te beginnen? Kies je pakket, dan plannen we een nieuwe intake.') }}</p>
            <div class="mt-5">
              <x-account.request-coaching-form :current-plan="$user->plan" :plans="$onlinePlans" />
            </div>
            <a href="{{ Locale::path('/online-coaching#pakketten') }}" class="mt-3 inline-block text-sm font-semibold underline decoration-accent underline-offset-4">{{ __('Vergelijk de pakketten') }}</a>
          </div>
        @endif

        @if ($c === 'aangevraagd')
          <div class="mt-5">
            <p class="text-muted">{!! __('Je aanvraag voor :plan is binnen. Dit zijn de volgende stappen:', ['plan' => '<strong class="text-ink">'.e(isset($plan['name']) ? __('online coaching :plan', ['plan' => $plan['name']]) : __('online coaching')).'</strong>']) !!}</p>
            <ol class="mt-5 space-y-3">
              @foreach ([
                  ['done' => true, 'text' => __('Account aangemaakt')],
                  ['done' => true, 'text' => __('Pakket gekozen')],
                  ['done' => (bool) $intake, 'text' => __('Intake ingevuld')],
                  ['done' => false, 'text' => __('Intakegesprek met Steyn (je hoort binnen 24 uur van ons)')],
                  ['done' => $plans->contains('status', 'gepubliceerd'), 'text' => __('Je persoonlijke schema staat klaar')],
              ] as $s)
                <li class="flex items-center gap-3">
                  @if ($s['done'])<x-icon name="CircleCheck" class="size-5 text-success" />@else<x-icon name="Circle" class="size-5 text-line" />@endif
                  <span class="{{ $s['done'] ? '' : 'text-muted' }}">{{ $s['text'] }}</span>
                </li>
              @endforeach
            </ol>
          </div>
        @endif

        @if ($c === 'actief')
          <p class="mt-5 text-muted">{!! __('Je volgt :plan. Check elke week in, zodat Steyn je plan kan bijsturen.', ['plan' => '<strong class="text-ink">'.e(__('online coaching :plan', ['plan' => $plan['name'] ?? ''])).'</strong>']) !!}</p>
        @endif

        @if ($c === 'gepauzeerd')
          <p class="mt-5 text-muted">{{ __('Je coaching staat tijdelijk op pauze. Klaar om verder te gaan?') }} <a href="{{ Locale::path('/contact?onderwerp=online-coaching') }}" class="font-semibold text-ink underline">{{ __('Laat het Steyn weten') }}</a>.</p>
        @endif

        @if ($user->coach_note)
          <div class="mt-6 flex gap-3 rounded-lg border border-accent/30 bg-accent-tint p-4">
            <x-icon name="MessageSquareQuote" class="size-5 shrink-0 text-accent" />
            <div>
              <p class="text-sm font-semibold">{{ __('Bericht van Steyn') }}</p>
              <p class="mt-1 whitespace-pre-line text-sm leading-relaxed">{{ $user->coach_note }}</p>
            </div>
          </div>
        @endif
      </section>

      {{-- Check-in --}}
      <section class="card p-6 sm:p-8" aria-labelledby="checkin-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 id="checkin-title" class="display flex items-center gap-2 text-2xl"><x-icon name="CalendarCheck" class="size-6" /> {{ __('Wekelijkse check-in') }}</h2>
          @if ($streak > 0)
            <span class="inline-flex items-center gap-1.5 rounded bg-surface px-2.5 py-1 text-sm font-medium"><x-icon name="Flame" class="size-4 text-accent" /> {{ $streak === 1 ? __(':count week op rij', ['count' => $streak]) : __(':count weken op rij', ['count' => $streak]) }}</span>
          @endif
        </div>
        <p class="mt-2 text-sm text-muted">{{ __('Week') }} {{ explode('-W', $week)[1] }} · {{ $checkedInThisWeek ? __('Je hebt deze week al ingecheckt. Top!') : __('Laat Steyn weten hoe je week ging, dan kan hij je plan bijsturen.') }}</p>
        <div class="mt-6 empty:hidden">@if (session('checkin_success') || ! $checkedInThisWeek || session('checkin_error'))<x-account.check-in-form :done="$checkedInThisWeek" />@endif</div>

        @if ($checkIns->isNotEmpty())
          <div class="mt-8 relative overflow-x-auto">
            <table class="w-full min-w-[480px] text-left text-sm">
              <caption class="sr-only">{{ __('Je laatste check-ins') }}</caption>
              <thead class="text-xs uppercase tracking-wider text-muted">
                <tr class="border-b border-line">
                  <th class="py-2 pr-3 font-semibold">{{ __('Week') }}</th>
                  <th class="py-2 pr-3 font-semibold">{{ __('Energie') }}</th>
                  <th class="py-2 pr-3 font-semibold">{{ __('Slaap') }}</th>
                  <th class="py-2 pr-3 font-semibold">{{ __('Voeding') }}</th>
                  <th class="py-2 pr-3 font-semibold">{{ __('Trainingen') }}</th>
                  <th class="py-2 font-semibold">{{ __('Gewicht') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($checkIns->take(8) as $ci)
                  <tr class="border-b border-line/70 last:border-0">
                    <td class="py-2.5 pr-3 font-semibold">{{ explode('-W', $ci->week)[1] }}</td>
                    <td class="py-2.5 pr-3">{{ $ci->energy }}/5</td>
                    <td class="py-2.5 pr-3">{{ $ci->sleep }}/5</td>
                    <td class="py-2.5 pr-3">{{ $ci->nutrition }}/5</td>
                    <td class="py-2.5 pr-3">{{ $ci->workouts }}×</td>
                    <td class="py-2.5">{{ $ci->weight ? \App\Support\Progress::formatNumber($ci->weight, 3, Locale::current()).' kg' : '–' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </section>
    </div>

    <div class="grid min-w-0 content-start gap-6">
      {{-- Agenda --}}
      <section class="card p-6" aria-labelledby="agenda-title">
        <div class="flex items-center justify-between gap-3">
          <h2 id="agenda-title" class="display flex items-center gap-2 text-2xl"><x-icon name="CalendarDays" class="size-6" /> {{ __('Agenda') }}</h2>
          <a href="/account/agenda" class="btn btn-primary btn-sm">{{ __('Afspraak maken') }}</a>
        </div>
        <div class="mt-4">
          <x-agenda.appointment-list :items="$upcoming->take(3)" :now="$now" :compact="true" />
        </div>
      </section>

      {{-- Vrienden --}}
      <section class="card p-6" aria-labelledby="friends-title">
        <h2 id="friends-title" class="display flex items-center gap-2 text-2xl"><x-icon name="Users" class="size-6" /> {{ __('Vriend uitnodigen') }}</h2>
        <p class="mt-2 text-sm text-muted">{{ __(':headline: je vriend krijgt :friend, jij :referrer zodra je vriend start.', ['headline' => $vriendenactie['headline'], 'friend' => $vriendenactie['friendReward'], 'referrer' => $vriendenactie['referrerReward']]) }}</p>
        <div class="mt-5">
          <x-account.referral-share :url="$referralUrl" :code="$user->referral_code" :first-name="$user->first_name" :friend-reward="$vriendenactie['friendReward']" />
        </div>
        @if ($friends->isNotEmpty())
          <ul class="mt-5 divide-y divide-line border-t border-line text-sm">
            @foreach ($friends as $f)
              <li class="flex justify-between gap-3 py-2.5">
                <span>{{ $f->first_name }}</span>
                <span class="text-muted">{{ $f->referral_reward_at ? __('Korting verrekend') : ($f->coaching_status === 'actief' ? __('Gestart · korting volgt') : __('Aangemeld')) }}</span>
              </li>
            @endforeach
          </ul>
        @endif
        <a href="{{ Locale::path('/vriend-uitnodigen#voorwaarden') }}" class="mt-4 inline-block text-xs text-muted underline">{{ __('Voorwaarden') }}</a>
      </section>
    </div>
  </div>
</x-layouts.account>
