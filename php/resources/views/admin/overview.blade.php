@php
    use App\Services\AdminCalendar;
    use App\Services\AdminLabels;
    use App\Support\Agenda;
    use App\View\PlanLabels;

    $plural = AdminLabels::plural(...);
    $todo = [];
    foreach (['training', 'voeding'] as $type) {
        $section = AdminLabels::PLAN_SECTION[$type];
        $todo[] = [
            'n' => $pipeline['counts'][$type]['controleren'],
            'href' => $section['href'].'?fase=controleren',
            'icon' => 'ClipboardCheck',
            'one' => $section['one'].' te controleren',
            'many' => mb_strtolower($section['title']).' te controleren',
        ];
    }
    $todo[] = ['n' => $counts['requests'], 'href' => '/admin/aanvragen', 'icon' => 'Inbox', 'one' => 'nieuwe contactaanvraag', 'many' => 'nieuwe contactaanvragen'];
    $todo[] = ['n' => $counts['applied'], 'href' => '/admin/leden?status=aangevraagd', 'icon' => 'UserPlus', 'one' => 'lid heeft coaching aangevraagd', 'many' => 'leden hebben coaching aangevraagd'];
    $todo = array_values(array_filter($todo, fn ($t) => $t['n'] > 0));

    $dayView = ['view' => 'dag', 'day' => $today, 'cancelled' => false];
    $stats = [
        ['label' => 'Afspraken deze week', 'value' => $week, 'href' => AdminCalendar::href(['view' => 'week', 'day' => $today, 'cancelled' => false])],
        ['label' => 'Actieve coachingklanten', 'value' => $active, 'href' => '/admin/leden?status=actief'],
        ['label' => 'Check-ins deze week', 'value' => $checks, 'href' => '/admin/leden?status=actief'],
        ['label' => 'Nieuwe leden (30 dagen)', 'value' => $newMembers, 'href' => '/admin/leden'],
    ];
    $typeLabel = fn ($type) => Agenda::getAppointmentType($type)['label'] ?? $type;
    $locationLabel = fn ($location) => Agenda::getAgendaLocation($location)['label'] ?? $location;
@endphp
{{-- Zoals in het origineel: de overzichtspagina valt niet onder het titelsjabloon van het beheer. --}}
<x-layouts.admin title="Overzicht" title-template="%s · SteynPT">
  <x-admin.page>
    <x-admin.page-header :title="$greeting.', '.$admin->first_name">
      <x-slot:description><span class="first-letter:uppercase">{{ Agenda::formatDayLong($now) }}</span></x-slot:description>
      <x-slot:actions>
        <a href="/admin/agenda/nieuw?datum={{ $today }}" class="btn btn-sm btn-primary">
          <x-icon name="CalendarPlus" class="size-4" /> Nieuwe afspraak
        </a>
      </x-slot:actions>
    </x-admin.page-header>

    {{-- Schema's: wie wacht, wie is binnenkort aan de beurt en wie heeft een actief schema --}}
    <div class="mb-6 grid gap-4 lg:grid-cols-2">
      @foreach (['training', 'voeding'] as $type)
        @php
            $section = AdminLabels::PLAN_SECTION[$type];
            $c = $pipeline['counts'][$type];
            $tiles = [
                ['fase' => 'wacht', 'n' => $c['wacht'], 'text' => $plural($c['wacht'], 'wacht op een nieuw schema', 'wachten op een nieuw schema'), 'alert' => $c['wacht'] > 0],
                ['fase' => 'binnenkort', 'n' => $c['binnenkort'], 'text' => $plural($c['binnenkort'], 'is de komende week toe aan een nieuw schema', 'zijn de komende week toe aan een nieuw schema'), 'alert' => false],
                ['fase' => 'actief', 'n' => $c['actief'], 'text' => $plural($c['actief'], 'heeft een actief schema', 'hebben een actief schema'), 'alert' => false],
            ];
            $extra = array_values(array_filter([
                $c['controleren'] > 0 ? ['fase' => 'controleren', 'text' => $c['controleren'].' te controleren'] : null,
                $c['ingepland'] > 0 ? ['fase' => 'ingepland', 'text' => $plural($c['ingepland'], '1 nieuw schema ingepland', $c['ingepland']." nieuwe schema's ingepland")] : null,
                $c['intake'] > 0 ? ['fase' => 'intake', 'text' => $c['intake'].' '.$plural($c['intake'], 'wacht', 'wachten').' nog op de intake'] : null,
                $c['pauze'] > 0 ? ['fase' => 'pauze', 'text' => $c['pauze'].' gepauzeerd'] : null,
            ]));
        @endphp
        <section aria-labelledby="overzicht-{{ $type }}" class="card overflow-hidden">
          <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5">
            <h2 id="overzicht-{{ $type }}" class="flex items-center gap-2 font-semibold">
              <x-icon :name="AdminLabels::PLAN_ICON[$type]" class="size-5 text-muted" /> {{ $section['title'] }}
            </h2>
            <a href="{{ $section['href'] }}" class="text-sm font-semibold text-accent hover:underline">Alle klanten</a>
          </div>
          <ul class="grid grid-cols-3 divide-x divide-line">
            @foreach ($tiles as $t)
              <li>
                <a href="{{ $section['href'] }}?fase={{ $t['fase'] }}" class="block h-full px-4 py-4 hover:bg-surface sm:px-5">
                  <span class="flex items-center gap-2 text-3xl font-semibold tabular-nums {{ $t['alert'] ? 'text-danger' : '' }}">
                    <span class="size-2 rounded-full {{ PlanLabels::GROUP_TONE[$t['fase']]['dot'] }}" aria-hidden="true"></span>
                    {{ $t['n'] }}
                  </span>
                  <span class="mt-1 block text-sm leading-snug text-muted">{{ $t['text'] }}</span>
                </a>
              </li>
            @endforeach
          </ul>
          @if ($extra)
            <p class="flex flex-wrap gap-x-4 gap-y-1 border-t border-line bg-[#f6f7f8] px-5 py-2.5 text-sm">
              @foreach ($extra as $e)
                <a href="{{ $section['href'] }}?fase={{ $e['fase'] }}" class="text-muted hover:text-ink hover:underline">{{ $e['text'] }}</a>
              @endforeach
            </p>
          @endif
        </section>
      @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
      {{-- Vandaag --}}
      <section aria-labelledby="vandaag" class="card overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
          <h2 id="vandaag" class="flex items-center gap-2 text-lg font-semibold">
            <x-icon name="CalendarDays" class="size-5 text-muted" /> Vandaag
          </h2>
          <a href="{{ AdminCalendar::href($dayView) }}" class="text-sm font-semibold text-accent hover:underline">Dagweergave</a>
        </div>
        @if ($todays->isEmpty())
          <p class="px-5 py-8 text-center text-sm text-muted">Geen afspraken vandaag.</p>
        @else
          <ul class="divide-y divide-line">
            @foreach ($todays as $a)
              @php $past = $a->ends_at->lt($now); @endphp
              <li>
                <a href="{{ AdminCalendar::href($dayView, ['afspraak' => $a->id]) }}" class="flex items-center gap-4 px-5 py-3.5 hover:bg-surface {{ $past ? 'opacity-55' : '' }}">
                  <span class="w-14 shrink-0 text-sm font-semibold tabular-nums">{{ Agenda::formatTime($a->starts_at) }}</span>
                  <span class="h-9 w-1 shrink-0 rounded-full" style="background: {{ AdminCalendar::typeColor($a->type) }}" aria-hidden="true"></span>
                  <span class="min-w-0 flex-1">
                    <span class="block truncate font-semibold">{{ $a->first_name }} {{ $a->last_name }}</span>
                    <span class="block truncate text-sm text-muted">{{ $typeLabel($a->type) }} · {{ $locationLabel($a->location) }}{{ $a->phone ? ' · '.$a->phone : '' }}</span>
                  </span>
                  @if ($past)<span class="text-xs text-muted">Geweest</span>@endif
                </a>
              </li>
            @endforeach
          </ul>
        @endif
        @if ($upcoming->isNotEmpty())
          <div class="border-t border-line bg-[#f6f7f8] px-5 py-4">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-muted">Hierna</h3>
            <ul class="mt-2 grid gap-1.5 text-sm">
              @foreach ($upcoming as $a)
                <li>
                  <a href="{{ AdminCalendar::href(['view' => 'dag', 'day' => Agenda::zonedParts($a->starts_at)['day'], 'cancelled' => false], ['afspraak' => $a->id]) }}" class="flex flex-wrap items-center gap-x-2 rounded-md hover:underline">
                    <span class="size-2 rounded-full" style="background: {{ AdminCalendar::typeColor($a->type) }}" aria-hidden="true"></span>
                    <span class="font-medium first-letter:uppercase">{{ Agenda::formatDayLong($a->starts_at) }} {{ Agenda::formatTime($a->starts_at) }}</span>
                    <span class="text-muted">· {{ $a->first_name }} {{ $a->last_name }}, {{ mb_strtolower(Agenda::getAppointmentType($a->type)['label'] ?? '') }}</span>
                  </a>
                </li>
              @endforeach
            </ul>
          </div>
        @endif
      </section>

      {{-- Te doen --}}
      <section aria-labelledby="te-doen" class="card self-start overflow-hidden">
        <h2 id="te-doen" class="border-b border-line px-5 py-4 text-lg font-semibold">Te doen</h2>
        @if (! $todo && $rewardsDue->isEmpty())
          <p class="flex items-center justify-center gap-2 px-5 py-8 text-sm text-muted">
            <x-icon name="CheckCircle2" class="size-4 text-success" /> Alles is bijgewerkt.
          </p>
        @else
          <ul class="divide-y divide-line">
            @foreach ($todo as $t)
              <li>
                <a href="{{ $t['href'] }}" class="flex items-center gap-3 px-5 py-3.5 hover:bg-surface">
                  <span class="grid size-9 place-items-center rounded-lg bg-accent-tint text-accent">
                    <x-icon :name="$t['icon']" class="size-4" />
                  </span>
                  <span class="flex-1 text-sm"><strong class="tabular-nums">{{ $t['n'] }}</strong> {{ $t['n'] === 1 ? $t['one'] : $t['many'] }}</span>
                  <x-icon name="ArrowRight" class="size-4 text-muted" />
                </a>
              </li>
            @endforeach
            @foreach ($rewardsDue as $r)
              <li class="flex items-center gap-3 px-5 py-3.5">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-accent-tint text-accent">
                  <x-icon name="Gift" class="size-4" />
                </span>
                <span class="flex-1 text-sm">
                  Vriendenkorting verrekenen: <strong>{{ $r->referrer_first }} {{ $r->referrer_last }}</strong> bracht {{ $r->first_name }} {{ $r->last_name }} aan
                  <span class="block text-xs text-muted">{{ $vriendenactie['referrerReward'] }}</span>
                </span>
                <form method="post" action="/admin/vriendenkorting">
                  @csrf
                  <input type="hidden" name="friendId" value="{{ $r->id }}">
                  <button type="submit" class="btn btn-sm btn-outline">Verrekend</button>
                </form>
              </li>
            @endforeach
          </ul>
        @endif
      </section>
    </div>

    <dl class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
      @foreach ($stats as $s)
        <a href="{{ $s['href'] }}" class="card block p-5 transition-colors hover:border-ink/40">
          <dt class="text-sm text-muted">{{ $s['label'] }}</dt>
          <dd class="mt-1 text-3xl font-semibold tabular-nums">{{ $s['value'] }}</dd>
        </a>
      @endforeach
    </dl>
  </x-admin.page>
</x-layouts.admin>
