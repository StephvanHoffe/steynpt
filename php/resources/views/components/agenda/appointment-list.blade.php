@props(['items', 'now' => null, 'compact' => false])
{{-- Komende afspraken van de klant, met agenda-download en afzeggen (tot 24 uur van tevoren). --}}
@php
    use App\Site\Locale;
    use App\Support\Agenda;

    // Namen van afspraaktypes en locaties staan in het Nederlands in App\Support\Agenda; hier in de taal van de pagina.
    $lang = Locale::current();
    $now ??= \Carbon\CarbonImmutable::now('UTC');
    $cancelHours = Agenda::BOOKING_RULES['cancelUntilHours'];
@endphp
@if (count($items) === 0)
  <p class="text-sm text-muted">{{ __('Je hebt geen afspraken gepland.') }}</p>
@else
  <ul class="divide-y divide-line rounded-lg border border-line">
    @foreach ($items as $a)
      @php
          $type = Agenda::getAppointmentType($a->type);
          $location = Agenda::getAgendaLocation($a->location);
          $canCancel = $a->starts_at->getTimestamp() - $now->getTimestamp() >= $cancelHours * 3600;
      @endphp
      <li class="flex flex-col gap-3 p-4 {{ $compact ? '' : 'sm:flex-row sm:items-center sm:justify-between' }}">
        <div>
          <p class="font-semibold">{{ isset($type['label']) ? __($type['label']) : $a->type }}</p>
          <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
            <span class="inline-flex items-center gap-1.5">
              <x-icon name="Clock" class="size-3.5" />
              <span class="first-letter:uppercase">{{ Agenda::formatDayLong($a->starts_at, $lang) }}, {{ Agenda::formatTime($a->starts_at) }}–{{ Agenda::formatTime($a->ends_at) }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5">
              <x-icon name="MapPin" class="size-3.5" />
              {{ isset($location['label']) ? __($location['label']) : $a->location }}
            </span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <a href="/account/agenda/{{ $a->id }}/ics" class="btn btn-sm btn-outline"><x-icon name="CalendarPlus" class="size-4" /> {{ __('In mijn agenda') }}</a>
          @if ($canCancel)
            <form method="post" action="/account/agenda/afzeggen">
              @csrf
              <input type="hidden" name="id" value="{{ $a->id }}">
              <button type="submit" class="btn btn-sm text-muted hover:text-danger">{{ __('Afzeggen') }}</button>
            </form>
          @else
            <span class="text-xs text-muted">{{ __('Afzeggen kan tot :hours uur van tevoren', ['hours' => $cancelHours]) }}</span>
          @endif
        </div>
      </li>
    @endforeach
  </ul>
@endif
