{{-- Overzicht per schematype: alle klanten met coaching of een schema, per fase en meest dringende bovenaan. --}}
@php
    use App\Services\AdminLabels;
    use App\Services\Plans\PlanView;
    use App\Site\Texts;
    use App\Support\Plans\Pipeline;
    use App\View\PlanLabels;

    $plural = fn (int $n) => $n === 1 ? 'klant' : 'klanten';
    $isLate = fn (array $r) => $r['dueOn'] <= $today && $r['stage'] !== 'pauze';
@endphp
<x-layouts.admin :title="$section['title']">
  <x-admin.page>
    <x-admin.page-header :title="$section['title']" description="Alle klanten met coaching of een schema: in welke fase ze zitten en wie toe is aan een nieuw schema.">
      <x-slot:actions>
        <a href="{{ AdminLabels::newPlanHref($type) }}" class="btn btn-sm btn-primary">
          <x-icon name="Plus" class="size-4" /> Nieuw {{ $section['one'] }}
        </a>
      </x-slot:actions>
    </x-admin.page-header>

    {{-- Fases als filter --}}
    <nav aria-label="Filter op fase" class="mb-5 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
      @foreach (Pipeline::STAGE_GROUPS as $g)
        @php $active = $fase === $g['id']; @endphp
        <a href="{{ $active ? $href(null) : $href($g['id']) }}" @if ($active) aria-current="page" @endif
          class="rounded-xl border p-3.5 transition-colors {{ $active ? 'border-ink bg-ink text-white' : 'border-line bg-white hover:border-ink/40' }}">
          <span class="flex items-baseline gap-2 text-sm font-medium leading-snug">
            <span class="size-2 shrink-0 -translate-y-px rounded-full {{ PlanLabels::GROUP_TONE[$g['id']]['dot'] }}" aria-hidden="true"></span>
            {{ $g['label'] }}
          </span>
          <span class="mt-1 block text-2xl font-semibold tabular-nums">{{ $counts[$g['id']] }}</span>
          <span class="block text-xs leading-snug {{ $active ? 'text-white/70' : 'text-muted' }}">{{ $g['hint'] }}</span>
        </a>
      @endforeach
    </nav>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm text-muted">
        @if ($fase)
          {{ collect(Pipeline::STAGE_GROUPS)->firstWhere('id', $fase)['label'] }}: {{ count($rows) }} {{ $plural(count($rows)) }} ·
          <a href="{{ $href(null) }}" class="font-semibold text-accent hover:underline">alle {{ $total }} tonen</a>
        @else
          {{ count($rows) }} {{ $plural(count($rows)) }}, meest dringende bovenaan
        @endif
      </p>
      <form action="{{ $section['href'] }}" method="get" class="relative w-full sm:w-72">
        @if ($fase)
          <input type="hidden" name="fase" value="{{ $fase }}">
        @endif
        <x-icon name="Search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <label for="zoek" class="sr-only">Zoek op naam of e-mail</label>
        <input id="zoek" name="q" value="{{ $q }}" placeholder="Zoek op naam of e-mail" class="input h-10 pl-9">
      </form>
    </div>

    @if (count($rows) === 0)
      <x-admin.empty-state>{{ $q !== '' ? "Geen klanten gevonden voor “{$q}”." : ($fase ? 'Niemand in deze fase.' : 'Nog geen klanten met coaching of een schema.') }}</x-admin.empty-state>
    @else
      <div class="overflow-hidden rounded-xl border border-line bg-white">
        {{-- Tabel op grotere schermen --}}
        <table class="hidden w-full table-fixed text-left text-sm lg:table">
          <caption class="sr-only">{{ $section['title'] }} per klant</caption>
          <colgroup>
            <col class="w-[26%]">
            <col class="w-[17%]">
            <col class="w-[25%]">
            <col class="w-[14%]">
            <col class="w-[18%]">
          </colgroup>
          <thead class="border-b border-line bg-[#f6f7f8] text-xs uppercase tracking-wide text-muted">
            <tr>
              <th class="px-4 py-2.5 font-semibold">Klant</th>
              <th class="px-4 py-2.5 font-semibold">Fase</th>
              <th class="px-4 py-2.5 font-semibold">Huidig schema</th>
              <th class="px-4 py-2.5 font-semibold">Nieuw schema op</th>
              <th class="px-4 py-2.5 font-semibold"><span class="sr-only">Volgende stap</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            @foreach ($rows as $r)
              @php
                  $step = $steps[$r['member']['id']];
                  $planName = Texts::onlinePlanName($r['member']['plan']);
              @endphp
              <tr class="align-middle hover:bg-surface/60">
                <td class="px-4 py-3">
                  <a href="/admin/leden/{{ $r['member']['id'] }}" class="block truncate font-semibold hover:underline">{{ $r['member']['firstName'] }} {{ $r['member']['lastName'] }}</a>
                  <span class="block truncate text-xs text-muted">{{ $planName ? "Online {$planName}" : $r['member']['email'] }}</span>
                </td>
                <td class="px-4 py-3">
                  <x-plans.stage-badge :stage="$r['stage']" :coaching-status="$r['member']['coachingStatus']" />
                  @if ($r['open'] && $r['current'])
                    <span class="mt-1 block text-xs text-muted">vervangt huidig schema</span>
                  @endif
                </td>
                <td class="px-4 py-3">
                  @if ($r['current'])
                    <a href="{{ AdminLabels::planHref($type, $r['current']['id']) }}" class="block min-w-0 hover:underline">
                      <span class="block truncate">{{ $r['current']['title'] ?: $section['one'] }}</span>
                      <span class="block text-xs text-muted">sinds {{ PlanView::shortDate($r['current']['publishedAt']) }}</span>
                    </a>
                  @else
                    <span class="text-muted">{{ $r['stage'] === 'intake' ? 'Intake nog niet ingevuld' : 'Nog geen schema' }}</span>
                  @endif
                </td>
                <td class="px-4 py-3">
                  @if ($r['dueOn'])
                    {{-- Bij gepauzeerde coaching is een verstreken datum geen actiepunt. --}}
                    <span class="whitespace-nowrap">
                      <span class="font-medium tabular-nums {{ $isLate($r) ? 'text-danger' : '' }}">{{ PlanLabels::formatPlanDay($r['dueOn']) }}</span>
                      <span class="block text-xs {{ $isLate($r) ? 'text-danger' : 'text-muted' }}">{{ $r['stage'] === 'gepland' ? 'start '.Pipeline::relativeDay($today, $r['dueOn']) : Pipeline::relativeDay($today, $r['dueOn']) }}</span>
                    </span>
                  @else
                    <span class="text-muted">–</span>
                  @endif
                </td>
                <td class="px-4 py-3 text-right">
                  @if ($step)
                    <a href="{{ $step['href'] }}" class="btn btn-sm {{ $step['primary'] ? 'btn-primary' : 'btn-outline' }}">{{ $step['label'] }}</a>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        {{-- Kaarten op tablet en telefoon --}}
        <ul class="divide-y divide-line lg:hidden">
          @foreach ($rows as $r)
            @php $step = $steps[$r['member']['id']]; @endphp
            <li class="grid gap-2 px-4 py-3.5">
              <div class="flex items-start justify-between gap-3">
                <a href="/admin/leden/{{ $r['member']['id'] }}" class="min-w-0 font-semibold hover:underline">{{ $r['member']['firstName'] }} {{ $r['member']['lastName'] }}</a>
                <x-plans.stage-badge :stage="$r['stage']" :coaching-status="$r['member']['coachingStatus']" />
              </div>
              <p class="text-sm text-muted">
                {{ $r['current'] ? 'Schema sinds '.PlanView::shortDate($r['current']['publishedAt']) : ($r['stage'] === 'intake' ? 'Intake nog niet ingevuld' : 'Nog geen schema') }}@if ($r['dueOn']){{ $r['stage'] === 'gepland' ? ' · nieuw schema start ' : ' · nieuw schema ' }}<span class="{{ $isLate($r) ? 'font-semibold text-danger' : 'text-ink' }}">{{ PlanLabels::formatPlanDay($r['dueOn']) }} ({{ Pipeline::relativeDay($today, $r['dueOn']) }})</span>@endif
              </p>
              @if ($step)
                <a href="{{ $step['href'] }}" class="btn btn-sm justify-self-start {{ $step['primary'] ? 'btn-primary' : 'btn-outline' }}">{{ $step['label'] }}</a>
              @endif
            </li>
          @endforeach
        </ul>
      </div>
    @endif
    <p class="mt-4 text-xs text-muted">
      Komende week: het nieuwe schema is binnen {{ Pipeline::SOON_DAYS }} dagen nodig. De datum stel je in bij het publiceren (standaard de looptijd van het schema) en kun je later aanpassen.
    </p>
  </x-admin.page>
</x-layouts.admin>
