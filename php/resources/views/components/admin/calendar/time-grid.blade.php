@props(['grid'])
{{-- Tijdrooster voor de dag- en weekweergave. $grid: params, days, events, windows, blocks, hours, today, now. --}}
@php
    use App\Services\AdminCalendar;
    use App\Services\AdminFormat;
    use App\Support\Agenda;
    use App\Support\AgendaCalendar;

    ['params' => $params, 'days' => $days, 'events' => $events, 'windows' => $windows, 'blocks' => $blocks, 'hours' => $hours, 'today' => $today, 'now' => $now] = $grid;
    $hourPx = 56;
    $px = $hourPx / 60;
    $gridStart = $hours['start'] * 60;
    $gridEnd = $hours['end'] * 60;
    $height = ($hours['end'] - $hours['start']) * $hourPx;
    $single = count($days) === 1;
    $columns = '3.5rem repeat('.count($days).', minmax('.($single ? '0' : '7rem').', 1fr))';
    $nowMinutes = AgendaCalendar::minutesOfDay($now);
    // Getallen in een style-attribuut zoals React ze schrijft (geen 12.000000001).
    $n = fn (int|float $v) => rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
@endphp
<div class="overflow-auto rounded-xl border border-line bg-white" style="max-height: calc(100dvh - 15rem); min-height: 26rem">
  <div class="grid" style="grid-template-columns: {{ $columns }};{{ $single ? '' : ' min-width: 52rem' }}">
    {{-- Kop met dagen --}}
    <div class="sticky left-0 top-0 z-40 border-b border-line bg-white"></div>
    @foreach ($days as $day)
      @php $isToday = $day === $today; @endphp
      <div class="sticky top-0 z-30 border-b border-l border-line bg-white px-2 py-2">
        @if ($single)
          <div class="flex items-center gap-2">
        @else
          <a href="{{ AdminCalendar::href([...$params, 'view' => 'dag', 'day' => $day]) }}" class="flex items-center gap-2 rounded-md hover:bg-surface" title="Bekijk deze dag">
        @endif
            <span class="text-xs font-medium uppercase tracking-wide text-muted">{{ AdminFormat::weekdayShort($day) }}</span>
            <span class="grid size-8 place-items-center rounded-full text-lg font-semibold tabular-nums {{ $isToday ? 'bg-ink text-white' : '' }}">{{ (int) substr($day, 8) }}</span>
        @if ($single)
          </div>
        @else
          </a>
        @endif
      </div>
    @endforeach

    {{-- Tijdkolom --}}
    <div class="sticky left-0 z-20 bg-white" style="height: {{ $height }}px">
      @for ($i = 0; $i < $hours['end'] - $hours['start']; $i++)
        <span class="absolute right-2 text-[11px] tabular-nums text-muted {{ $i === 0 ? 'top-1' : '-translate-y-1/2' }}" @if ($i > 0) style="top: {{ $i * $hourPx }}px" @endif>{{ AdminCalendar::hhmm(($hours['start'] + $i) * 60) }}</span>
      @endfor
    </div>

    {{-- Dagkolommen --}}
    @foreach ($days as $day)
      @php
          $weekday = Agenda::weekdayOf($day);
          $dayWindows = array_filter($windows, fn ($w) => $w['weekday'] === $weekday);
          $dayBlocks = array_filter($blocks, fn ($b) => $b['day'] === $day);
          $dayEvents = array_values(array_filter($events, fn ($e) => $e['day'] === $day));
          $lanes = AgendaCalendar::layoutLanes($dayEvents);
      @endphp
      <div class="relative border-l border-line bg-[#f6f7f8]" style="height: {{ $height }}px">
        {{-- Beschikbare tijd (wit) --}}
        @foreach ($dayWindows as $w)
          @php
              $s = max(AgendaCalendar::timeToMinutes($w['startTime']), $gridStart);
              $e = min(AgendaCalendar::timeToMinutes($w['endTime']), $gridEnd);
          @endphp
          @if ($e > $s)
            <div class="absolute inset-x-0 bg-white" style="top: {{ $n(($s - $gridStart) * $px) }}px; height: {{ $n(($e - $s) * $px) }}px">
              @if ($single)
                <span class="absolute right-2 top-1 text-[11px] text-muted">Beschikbaar · {{ Agenda::getAgendaLocation($w['location'])['label'] ?? $w['location'] }}</span>
              @endif
            </div>
          @endif
        @endforeach

        {{-- Geblokkeerd --}}
        @foreach ($dayBlocks as $b)
          @php
              $s = max($b['start'], $gridStart);
              $e = min($b['end'], $gridEnd);
          @endphp
          @if ($e > $s)
            <div class="absolute inset-x-0 z-[1] border-y border-line"
              style="top: {{ $n(($s - $gridStart) * $px) }}px; height: {{ $n(($e - $s) * $px) }}px; background-image: repeating-linear-gradient(135deg, rgb(17 19 21 / 0.06) 0 8px, transparent 8px 16px)">
              <span class="m-1.5 inline-block rounded bg-white/90 px-1.5 py-0.5 text-[11px] font-medium text-muted">Vrij{{ $b['reason'] ? ' · '.$b['reason'] : '' }}</span>
            </div>
          @endif
        @endforeach

        {{-- Rasterlijnen --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 z-[2]"
          style="background-image: linear-gradient(to bottom, var(--color-line) 1px, transparent 1px), linear-gradient(to bottom, rgb(226 229 232 / 0.55) 1px, transparent 1px); background-size: 100% {{ $hourPx }}px, 100% {{ $hourPx / 2 }}px"></div>

        {{-- Klik op een leeg moment om een afspraak in te plannen (met toetsenbord: knop Nieuwe afspraak) --}}
        @for ($i = 0; $i < ($hours['end'] - $hours['start']) * 2; $i++)
          @php $min = $gridStart + $i * 30; @endphp
          @if (! Agenda::zonedTimeToUtc($day, AdminCalendar::hhmm($min))->lt($now))
            <a href="/admin/agenda/nieuw?datum={{ $day }}&amp;tijd={{ AdminCalendar::hhmm($min) }}" tabindex="-1" aria-hidden="true"
              class="group absolute inset-x-0 z-[3] flex items-start px-1.5 pt-0.5 text-[11px] font-medium text-accent opacity-0 hover:bg-accent-tint/70 hover:opacity-100"
              style="top: {{ $n($i * 30 * $px) }}px; height: {{ $n(30 * $px) }}px">+ {{ AdminCalendar::hhmm($min) }}</a>
          @endif
        @endfor

        {{-- Afspraken --}}
        @foreach ($dayEvents as $ev)
          @php
              $lane = $lanes[$ev['id']] ?? ['lane' => 0, 'lanes' => 1];
              $top = (max($ev['start'], $gridStart) - $gridStart) * $px;
              $h = max((min($ev['end'], $gridEnd) - max($ev['start'], $gridStart)) * $px - 2, 20);
              $color = AdminCalendar::typeColor($ev['type']);
              $compact = $h < 40;
          @endphp
          <a href="{{ $ev['href'] }}"
            class="absolute z-10 overflow-hidden rounded-md border-l-[3px] px-1.5 py-1 text-xs leading-tight shadow-sm ring-1 ring-black/5 transition hover:z-20 hover:shadow-md focus-visible:z-20 {{ $ev['cancelled'] ? 'border-dashed opacity-60' : '' }}"
            style="top: {{ $n($top + 1) }}px; height: {{ $n($h) }}px; left: calc({{ $n($lane['lane'] / $lane['lanes'] * 100) }}% + 2px); width: calc({{ $n(100 / $lane['lanes']) }}% - 4px); border-left-color: {{ $color }}; background: {{ $ev['cancelled'] ? '#fff' : "color-mix(in srgb, {$color} 9%, white)" }}">
            <span class="block truncate font-semibold {{ $ev['cancelled'] ? 'line-through' : '' }}">{{ $compact ? AdminCalendar::hhmm($ev['start']).' '.$ev['client'] : $ev['client'] }}</span>
            @if (! $compact)
              <span class="block truncate text-muted">{{ AdminCalendar::hhmm($ev['start']) }}–{{ AdminCalendar::hhmm($ev['end']) }} · {{ $ev['typeLabel'] }}</span>
            @endif
            @if (! $compact && $h > 70)
              <span class="block truncate text-muted">{{ Agenda::getAgendaLocation($ev['location'])['label'] ?? $ev['location'] }}</span>
            @endif
            <span class="sr-only">{{ $ev['typeLabel'] }}, {{ $ev['cancelled'] ? 'geannuleerd' : '' }}</span>
          </a>
        @endforeach

        {{-- Nu --}}
        @if ($day === $today && $nowMinutes >= $gridStart && $nowMinutes <= $gridEnd)
          <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 z-[25] border-t-2 border-[#d92d20]" style="top: {{ $n(($nowMinutes - $gridStart) * $px) }}px">
            <span class="absolute -left-1 -top-[5px] size-2 rounded-full bg-[#d92d20]"></span>
          </div>
        @endif
      </div>
    @endforeach
  </div>
</div>
