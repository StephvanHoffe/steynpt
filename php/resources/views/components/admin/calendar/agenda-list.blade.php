@props(['days', 'events', 'today', 'blockedDays'])
{{-- Lijstweergave: afspraken per dag onder elkaar. --}}
@php
    use App\Services\AdminCalendar;
    use App\Support\Agenda;

    $withContent = array_values(array_filter($days, fn ($d) => collect($events)->contains('day', $d) || array_key_exists($d, $blockedDays)));
@endphp
@if (! $withContent)
  <x-admin.empty-state>Geen afspraken in deze periode.</x-admin.empty-state>
@else
  <div class="grid gap-4">
    @foreach ($withContent as $day)
      @php $dayEvents = array_values(array_filter($events, fn ($e) => $e['day'] === $day)); @endphp
      <section class="overflow-hidden rounded-xl border border-line bg-white">
        <h2 class="flex items-center justify-between gap-3 border-b border-line bg-[#f6f7f8] px-4 py-2.5 text-sm font-semibold">
          <span class="first-letter:uppercase">{{ Agenda::formatDayLong(Agenda::dayToDate($day)) }}</span>
          <span class="text-xs font-medium text-muted">{{ $day === $today ? 'Vandaag · ' : '' }}{{ array_key_exists($day, $blockedDays)
              ? 'Vrij'.($blockedDays[$day] ? " ({$blockedDays[$day]})" : '')
              : AdminCalendar::appointments(count(array_filter($dayEvents, fn ($e) => ! $e['cancelled']))) }}</span>
        </h2>
        @if ($dayEvents)
          <ul class="divide-y divide-line">
            @foreach ($dayEvents as $e)
              <li>
                <a href="{{ $e['href'] }}" data-keep-scroll class="flex items-center gap-4 px-4 py-3 hover:bg-surface {{ $e['cancelled'] ? 'opacity-60' : '' }}">
                  <span class="w-24 shrink-0 text-sm font-semibold tabular-nums">{{ AdminCalendar::hhmm($e['start']) }}–{{ AdminCalendar::hhmm($e['end']) }}</span>
                  <span class="h-9 w-1 shrink-0 rounded-full" style="background: {{ AdminCalendar::typeColor($e['type']) }}" aria-hidden="true"></span>
                  <span class="min-w-0 flex-1">
                    <span class="block truncate font-semibold {{ $e['cancelled'] ? 'line-through' : '' }}">{{ $e['client'] }}</span>
                    <span class="flex flex-wrap gap-x-3 text-sm text-muted">
                      <span class="inline-flex items-center gap-1"><x-icon name="Clock" class="size-3.5" /> {{ $e['typeLabel'] }}</span>
                      <span class="inline-flex items-center gap-1"><x-icon name="MapPin" class="size-3.5" /> {{ Agenda::getAgendaLocation($e['location'])['label'] ?? $e['location'] }}</span>
                      @if ($e['cancelled'])<span class="font-medium text-danger">Geannuleerd</span>@endif
                    </span>
                  </span>
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </section>
    @endforeach
  </div>
@endif
