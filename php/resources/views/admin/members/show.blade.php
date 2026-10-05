@php
    use App\Services\AdminCalendar;
    use App\Services\AdminFormat;
    use App\Services\AdminLabels;
    use App\Site\Site;
    use App\Support\Agenda;
    use App\Support\Plans\Pipeline;
    use App\Support\Progress;
    use App\Support\Totp;
    use App\View\PlanLabels;
    use Illuminate\Support\Str;

    $name = "{$member->first_name} {$member->last_name}";
@endphp
<x-layouts.admin title="Lid">
  <x-admin.page>
    <x-admin.page-header :title="$name" :back="['href' => '/admin/leden', 'label' => 'Leden']">
      <x-slot:description>
        <span class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
          <x-admin.coaching-badge :status="$member->coaching_status" />
          @if ($member->plan)<span class="font-medium text-ink">{{ $planName }}</span>@endif
          <a href="mailto:{{ $member->email }}" class="hover:text-ink hover:underline">{{ $member->email }}</a>
          @if ($member->phone)
            <a href="tel:{{ preg_replace('/\s/', '', $member->phone) }}" class="hover:text-ink hover:underline">{{ $member->phone }}</a>
          @endif
          <span>Doel: {{ Site::goalLabel($member->goal) ?? '–' }}</span>
          <span>Lid sinds {{ AdminFormat::dayMonthYear($member->created_at) }}</span>
        </span>
      </x-slot:description>
      <x-slot:actions>
        <a href="#metingen" class="btn btn-sm btn-outline">
          <x-icon name="LineChart" class="size-4" /> Meting toevoegen
        </a>
        <a href="/admin/agenda/nieuw?lid={{ $member->id }}" class="btn btn-sm btn-primary">
          <x-icon name="CalendarPlus" class="size-4" /> Afspraak inplannen
        </a>
      </x-slot:actions>
    </x-admin.page-header>
    @if ($inviter)
      <p class="mb-6 inline-flex rounded-lg bg-accent-tint px-3 py-2 text-sm">
        Uitgenodigd door {{ $inviter->first_name }} {{ $inviter->last_name }}: krijgt {{ $vriendenactie['friendReward'] }}.
      </p>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
      <div class="grid min-w-0 grid-cols-1 content-start gap-6">
        <section class="card p-6" aria-labelledby="coaching">
          <h2 id="coaching" class="text-lg font-semibold">Coaching</h2>
          <div class="mt-4">
            <x-admin.member-coaching-form :user-id="$member->id" :status="$member->coaching_status" :note="$member->coach_note" />
          </div>
        </section>

        <section class="card p-6" aria-labelledby="schemas">
          <h2 id="schemas" class="text-lg font-semibold">Schema's</h2>
          <ul class="mt-3 divide-y divide-line">
            @foreach (['training', 'voeding'] as $type)
              @php
                  $row = $pipeline['rows'][$type][0] ?? null;
                  $today = $pipeline['today'];
                  $open = $row && $row['open'] ? AdminLabels::planHref($type, $row['open']['id']) : null;
              @endphp
              <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-3.5">
                <x-icon :name="AdminLabels::PLAN_ICON[$type]" class="size-5 shrink-0 text-muted" />
                <div class="min-w-0 flex-1">
                  <p class="flex flex-wrap items-center gap-2 font-semibold">
                    {{ PlanLabels::TYPE_LABEL[$type] }} @if ($row)<x-plans.stage-badge :stage="$row['stage']" :coaching-status="$member->coaching_status" />@endif
                  </p>
                  <p class="text-sm text-muted">
                    {{ $row && $row['current'] ? ($row['current']['title'] ?: 'Schema').' · sinds '.AdminFormat::dayMonthShort($row['current']['publishedAt']) : 'Nog geen schema' }}
                    @if ($row && $row['dueOn'])
                      <span class="{{ $row['dueOn'] <= $today && $row['stage'] !== 'pauze' ? 'text-danger' : '' }}">
                        · nieuw schema {{ $row['stage'] === 'gepland' ? 'start ' : '' }}{{ PlanLabels::formatPlanDay($row['dueOn']) }} ({{ Pipeline::relativeDay($today, $row['dueOn']) }})
                      </span>
                    @endif
                  </p>
                </div>
                <div class="flex flex-wrap gap-2">
                  @if ($open)
                    <a href="{{ $open }}" class="btn btn-sm btn-primary">{{ $row['stage'] === 'controleren' ? 'Controleren' : 'Concept openen' }}</a>
                  @else
                    @if ($row && $row['current'])
                      <a href="{{ AdminLabels::planHref($type, $row['current']['id']) }}" class="btn btn-sm btn-outline">Bekijken</a>
                    @endif
                    @if ($row && $row['scheduled'])
                      <a href="{{ AdminLabels::planHref($type, $row['scheduled']['id']) }}" class="btn btn-sm btn-outline">Ingepland</a>
                    @endif
                    <a href="{{ AdminLabels::newPlanHref($type, $member->id) }}" class="btn btn-sm btn-outline">Nieuw schema</a>
                  @endif
                </div>
              </li>
            @endforeach
          </ul>
        </section>

        <section id="metingen-blok" class="card scroll-mt-20 p-6" aria-labelledby="metingen">
          <h2 id="metingen" class="scroll-mt-20 text-lg font-semibold">Metingen</h2>
          <p class="mt-1 text-sm text-muted">Wat je hier invoert, ziet de klant direct onder Mijn voortgang.</p>
          <div class="mt-5">
            <x-progress.measurement-form :user-id="$member->id" :today="$today" />
          </div>
          @if ($measurements->isNotEmpty())
            <div class="mt-8">
              <x-progress.overview :rows="$measurements" />
            </div>
            <div class="relative mt-6 overflow-x-auto rounded-lg border border-line">
              <table class="w-full min-w-[640px] text-left text-sm">
                <caption class="sr-only">Metingen van {{ $member->first_name }}</caption>
                <thead class="bg-surface text-xs uppercase tracking-wider text-muted">
                  <tr>
                    <th class="px-3 py-2 font-semibold">Datum</th>
                    @foreach (Progress::MEASUREMENT_FIELDS as $f)
                      <th class="px-3 py-2 font-semibold">{{ $f['label'] }}</th>
                    @endforeach
                    <th class="px-3 py-2 font-semibold"><span class="sr-only">Acties</span></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($measurements->reverse() as $r)
                    <tr class="border-t border-line">
                      <td class="whitespace-nowrap px-3 py-2 font-medium">{{ Agenda::formatDayLong($r->measured_at) }}</td>
                      @foreach (Progress::MEASUREMENT_FIELDS as $f)
                        @php $value = $r->{Str::snake($f['key'])}; @endphp
                        <td class="px-3 py-2 tabular-nums">{{ $value !== null ? Progress::formatNumber($value) : '–' }}</td>
                      @endforeach
                      <td class="px-3 py-2 text-right">
                        <form method="post" action="/admin/leden/{{ $member->id }}/metingen/verwijderen">
                          @csrf
                          <input type="hidden" name="id" value="{{ $r->id }}">
                          <input type="hidden" name="userId" value="{{ $member->id }}">
                          <button type="submit" aria-label="Meting van {{ Agenda::formatDayLong($r->measured_at) }} verwijderen" class="grid size-8 place-items-center rounded-md text-muted hover:bg-surface hover:text-danger">
                            <x-icon name="Trash2" class="size-4" />
                          </button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </section>
      </div>

      <aside class="grid min-w-0 grid-cols-1 content-start gap-6">
        <section class="card p-6" aria-labelledby="afspraken">
          <h2 id="afspraken" class="text-lg font-semibold">Komende afspraken</h2>
          @if ($upcoming->isEmpty())
            <p class="mt-3 text-sm text-muted">Geen afspraken gepland.</p>
          @else
            <ul class="mt-3 divide-y divide-line text-sm">
              @foreach ($upcoming as $a)
                <li>
                  <a href="{{ AdminCalendar::href(['view' => 'dag', 'day' => Agenda::zonedParts($a->starts_at)['day'], 'cancelled' => false], ['afspraak' => $a->id]) }}" class="-mx-2 block rounded-md px-2 py-2.5 hover:bg-surface">
                    <span class="block font-medium first-letter:uppercase">{{ Agenda::formatDayLong($a->starts_at) }}, {{ Agenda::formatTime($a->starts_at) }}</span>
                    <span class="text-muted">{{ Agenda::getAppointmentType($a->type)['label'] ?? $a->type }} · {{ Agenda::getAgendaLocation($a->location)['label'] ?? $a->location }}</span>
                  </a>
                </li>
              @endforeach
            </ul>
          @endif
        </section>
        <section class="card p-6" aria-labelledby="beveiliging">
          <h2 id="beveiliging" class="text-lg font-semibold">Inloggen en beveiliging</h2>
          <dl class="mt-3 grid gap-2 text-sm">
            <div class="flex justify-between gap-3">
              <dt class="text-muted">Tweestapsverificatie</dt>
              <dd class="text-right font-medium">
                @if ($member->totp_enabled_at)
                  aan sinds {{ AdminFormat::dayMonthShort($member->totp_enabled_at) }}
                  <span class="block font-normal text-muted">{{ $codesLeft }} {{ $codesLeft === 1 ? 'herstelcode' : 'herstelcodes' }} over</span>
                @else
                  nog niet ingesteld
                  <span class="block font-normal text-muted">gebeurt bij de volgende keer inloggen</span>
                @endif
              </dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-muted">Wachtwoord</dt>
              <dd class="text-right font-medium">
                {{ Totp::passwordDaysLeft($passwordChanged) <= 0 ? 'verlopen' : 'verloopt '.AdminFormat::dayMonthShort(Totp::passwordExpiresAt($passwordChanged)) }}
                <span class="block font-normal text-muted">gewijzigd {{ AdminFormat::dayMonthShort($passwordChanged) }}</span>
              </dd>
            </div>
          </dl>
          @if ($member->totp_enabled_at)
            <details class="mt-4 rounded-lg border border-line p-3 text-sm">
              <summary class="cursor-pointer font-semibold">Tweestapsverificatie resetten…</summary>
              <p class="mt-2 text-muted">
                Alleen als {{ $member->first_name }} de telefoon én de herstelcodes kwijt is. {{ $member->first_name }} wordt overal uitgelogd en koppelt bij de volgende keer
                inloggen een nieuwe telefoon. Controleer eerst of je echt met {{ $member->first_name }} zelf spreekt.
              </p>
              <form method="post" action="/admin/leden/{{ $member->id }}/tweestaps-resetten" class="mt-3">
                @csrf
                <input type="hidden" name="userId" value="{{ $member->id }}">
                <button type="submit" class="btn btn-sm btn-outline border-danger/40 text-danger hover:border-danger">Ja, resetten</button>
              </form>
            </details>
          @endif
        </section>

        <section class="card p-6" aria-labelledby="intake">
          <h2 id="intake" class="text-lg font-semibold">Intake</h2>
          <div class="mt-4">
            @if ($intake)
              <x-plans.intake-panel :intake="$intake" :updated-at="$intakeUpdatedAt" />
            @else
              <p class="text-sm text-muted">Dit lid heeft nog geen intake ingevuld.</p>
            @endif
          </div>
        </section>
      </aside>
    </div>
  </x-admin.page>
</x-layouts.admin>
