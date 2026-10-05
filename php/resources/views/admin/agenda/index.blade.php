@php
    use App\Services\AdminCalendar;
    use App\Support\Agenda;
    use App\Support\AgendaCalendar;

    $nav = 'grid h-9 place-items-center rounded-lg border border-line bg-white px-2.5 text-sm font-medium hover:border-ink';
    $with = fn (array $changes) => AdminCalendar::href([...$params, ...$changes]);
    $periodWord = match ($view) {
        'dag' => 'dag',
        'week' => 'week',
        'maand' => 'maand',
        default => 'periode',
    };
    $grid = ['params' => $params, 'days' => $days, 'events' => $events, 'windows' => $windows, 'blocks' => $dayBlocks, 'hours' => $hours, 'today' => $today, 'now' => $now];
@endphp
<x-layouts.admin title="Agenda">
  <x-admin.page>
    <x-admin.page-header title="Agenda" :description="$planned.' '.($planned === 1 ? 'afspraak' : 'afspraken').' in deze '.$periodWord">
      <x-slot:actions>
        <a href="/admin/agenda/instellingen" class="btn btn-sm btn-outline">
          <x-icon name="Settings2" class="size-4" /> Beschikbaarheid
        </a>
        <a href="{{ $newHref }}" class="btn btn-sm btn-primary">
          <x-icon name="CalendarPlus" class="size-4" /> Nieuwe afspraak
        </a>
      </x-slot:actions>
    </x-admin.page-header>

    @if ($melding)
      <p class="mb-4 flex items-center gap-2 rounded-lg border border-success/30 bg-success/5 px-4 py-2.5 text-sm font-medium" role="status">
        <x-icon name="CheckCircle2" class="size-4 text-success" /> {{ $melding }}
      </p>
    @endif

    {{-- Werkbalk --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <a href="{{ $with(['day' => $today]) }}" class="{{ $nav }}">Vandaag</a>
        <div class="flex">
          <a href="{{ $with(['day' => AgendaCalendar::shiftDay($view, $day, -1)]) }}" class="{{ $nav }} rounded-r-none" aria-label="Vorige periode">
            <x-icon name="ChevronLeft" class="size-4" />
          </a>
          <a href="{{ $with(['day' => AgendaCalendar::shiftDay($view, $day, 1)]) }}" class="{{ $nav }} -ml-px rounded-l-none" aria-label="Volgende periode">
            <x-icon name="ChevronRight" class="size-4" />
          </a>
        </div>
        <div class="ml-1">
          @if ($period['eyebrow'])<p class="text-xs font-medium uppercase tracking-wide text-muted">{{ $period['eyebrow'] }}</p>@endif
          <h2 class="text-lg font-semibold leading-tight first-letter:uppercase">{{ $period['title'] }}</h2>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <x-admin.calendar.date-jump :day="$day" :view="$view" :cancelled="$cancelled" />
        <nav aria-label="Weergave" class="flex rounded-lg border border-line bg-white p-0.5">
          @foreach (AgendaCalendar::CALENDAR_VIEWS as $v)
            <a href="{{ $with(['view' => $v['id']]) }}" @if ($v['id'] === $view) aria-current="page" @endif
              class="rounded-md px-3 py-1.5 text-sm font-medium {{ $v['id'] === $view ? 'bg-ink text-white' : 'text-ink/80 hover:bg-surface' }}">{{ $v['label'] }}</a>
          @endforeach
        </nav>
      </div>
    </div>

    {{-- Weergave --}}
    @if ($view === 'maand')
      <x-admin.calendar.month-grid :params="$params" :days="$days" :events="$events" :blocked-days="$blockedDays" :today="$today" />
    @elseif ($view === 'lijst')
      <x-admin.calendar.agenda-list :days="$days" :events="$events" :today="$today" :blocked-days="$blockedDays" />
    @elseif ($view === 'dag')
      <div class="grid gap-4 xl:grid-cols-[1fr_22rem]">
        <x-admin.calendar.time-grid :grid="$grid" />
        <div class="min-w-0">
          <x-admin.calendar.agenda-list :days="$days" :events="$events" :today="$today" :blocked-days="$blockedDays" />
        </div>
      </div>
    @else
      <x-admin.calendar.time-grid :grid="$grid" />
    @endif

    {{-- Legenda en filter --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-muted">
      <ul class="flex flex-wrap items-center gap-x-4 gap-y-1.5">
        @foreach (Agenda::APPOINTMENT_TYPES as $t)
          <li class="flex items-center gap-1.5">
            <span class="size-2.5 rounded-full" style="background: {{ AdminCalendar::typeColor($t['id']) }}" aria-hidden="true"></span> {{ $t['label'] }}
          </li>
        @endforeach
        @if ($view !== 'maand' && $view !== 'lijst')
          <li class="flex items-center gap-1.5">
            <span class="size-2.5 rounded-sm border border-line bg-white" aria-hidden="true"></span> Beschikbaar
            <span class="ml-2 size-2.5 rounded-sm bg-[#e9ebed]" aria-hidden="true"></span> Niet beschikbaar
          </li>
        @endif
      </ul>
      <a href="{{ $with(['cancelled' => ! $cancelled]) }}" class="inline-flex items-center gap-2 font-medium text-ink hover:underline" role="switch" aria-checked="{{ $cancelled ? 'true' : 'false' }}">
        <span class="relative h-4 w-7 rounded-full transition-colors {{ $cancelled ? 'bg-ink' : 'bg-line' }}" aria-hidden="true">
          <span class="absolute top-0.5 size-3 rounded-full bg-white transition-all {{ $cancelled ? 'left-3.5' : 'left-0.5' }}"></span>
        </span>
        Geannuleerde afspraken tonen
      </a>
    </div>

    @if ($selected)
      <x-admin.calendar.appointment-drawer :appointment="$selected" :client="$selected->user" :close-href="AdminCalendar::href($params)" :warnings="$warnings" :now="$now" />
    @endif
  </x-admin.page>
</x-layouts.admin>
