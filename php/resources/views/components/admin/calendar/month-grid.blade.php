@props(['params', 'days', 'events', 'blockedDays', 'today'])
{{-- Maandoverzicht: per dag de eerste afspraken, de rest via de dagweergave. --}}
@php
    use App\Services\AdminCalendar;

    $max = 3;
    $month = substr($params['day'], 0, 7);
@endphp
<div class="overflow-hidden rounded-xl border border-line bg-white">
  <div class="grid grid-cols-7 border-b border-line bg-white text-center text-xs font-medium uppercase tracking-wide text-muted">
    @foreach (['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'] as $d)
      <div class="py-2">{{ $d }}</div>
    @endforeach
  </div>
  <div class="grid grid-cols-7">
    @foreach ($days as $i => $day)
      @php
          $inMonth = substr($day, 0, 7) === $month;
          $dayEvents = array_values(array_filter($events, fn ($e) => $e['day'] === $day));
          $blocked = array_key_exists($day, $blockedDays);
          $dayHref = AdminCalendar::href([...$params, 'view' => 'dag', 'day' => $day]);
      @endphp
      <div class="relative min-h-20 border-line p-1 sm:min-h-32 sm:p-1.5 {{ $i % 7 ? 'border-l' : '' }} {{ $i >= 7 ? 'border-t' : '' }} {{ $inMonth ? 'bg-white' : 'bg-[#f6f7f8]' }}"
        @if ($blocked) style="background-image: repeating-linear-gradient(135deg, rgb(17 19 21 / 0.05) 0 8px, transparent 8px 16px)" @endif>
        <div class="flex items-center justify-between gap-1">
          <a href="{{ $dayHref }}" aria-label="Bekijk {{ $day }}"
            class="grid size-7 place-items-center rounded-full text-sm tabular-nums hover:bg-surface {{ $day === $today ? 'bg-ink font-semibold text-white hover:bg-ink' : ($inMonth ? 'font-medium' : 'text-muted') }}">{{ (int) substr($day, 8) }}</a>
          @if ($blocked)<span class="hidden truncate text-[11px] text-muted sm:block">{{ $blockedDays[$day] ?? 'Vrij' }}</span>@endif
        </div>

        {{-- Telefoon: alleen gekleurde stipjes --}}
        @if ($dayEvents)
          <a href="{{ $dayHref }}" class="mt-1 flex flex-wrap gap-1 px-1 sm:hidden" aria-label="{{ count($dayEvents) }} afspraken">
            @foreach ($dayEvents as $e)
              <span class="size-2 rounded-full {{ $e['cancelled'] ? 'opacity-40' : '' }}" style="background: {{ AdminCalendar::typeColor($e['type']) }}"></span>
            @endforeach
          </a>
        @endif

        <ul class="mt-1 hidden space-y-0.5 sm:block">
          @foreach (array_slice($dayEvents, 0, $max) as $e)
            <li>
              <a href="{{ $e['href'] }}" data-keep-scroll title="{{ AdminCalendar::hhmm($e['start']) }} {{ $e['typeLabel'] }} · {{ $e['client'] }}"
                class="flex items-center gap-1.5 rounded px-1 py-0.5 text-xs hover:bg-surface {{ $e['cancelled'] ? 'text-muted line-through' : '' }}">
                <span class="size-2 shrink-0 rounded-full" style="background: {{ AdminCalendar::typeColor($e['type']) }}" aria-hidden="true"></span>
                <span class="tabular-nums text-muted">{{ AdminCalendar::hhmm($e['start']) }}</span>
                <span class="truncate font-medium">{{ $e['client'] }}</span>
              </a>
            </li>
          @endforeach
          @if (count($dayEvents) > $max)
            <li><a href="{{ $dayHref }}" class="block px-1 text-xs font-semibold text-accent hover:underline">+{{ count($dayEvents) - $max }} meer</a></li>
          @endif
        </ul>
      </div>
    @endforeach
  </div>
</div>
