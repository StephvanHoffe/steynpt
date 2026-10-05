@php
    use App\Site\Locale;
    use App\Support\Agenda;

    $qs = fn (array $params) => '?'.http_build_query(array_filter($params));
    $blockedForUser = fn (array $t) => ($t['requiresCoaching'] ?? false) && $user->coaching_status !== 'actief';
    $loc = $location ? Agenda::getAgendaLocation($location) : null;
@endphp
<x-layouts.account :title="__('Agenda')">
  <div class="container-site max-w-4xl py-10 lg:py-14">
    <p class="eyebrow text-accent">{{ __('Mijn omgeving') }}</p>
    <h1 class="display display-lg mt-3">{{ __('Agenda') }}</h1>

    @if ($booked)
      <p role="status" class="mt-6 flex items-start gap-3 rounded-lg border border-success/30 bg-success/5 p-4 text-sm text-success">
        <x-icon name="CalendarCheck2" class="size-5 shrink-0" />
        <span>{{ __('Je afspraak is bevestigd: :type op :day om :time.', ['type' => __(Agenda::getAppointmentType($booked->type)['label'] ?? $booked->type), 'day' => Agenda::formatDayLong($booked->starts_at, Locale::current()), 'time' => Agenda::formatTime($booked->starts_at)]) }} <a href="/account/agenda/{{ $booked->id }}/ics" class="font-semibold underline">{{ __('Zet hem in je agenda') }}</a>.</span>
      </p>
    @endif

    <section class="mt-8" aria-labelledby="komend">
      <h2 id="komend" class="display display-sm">{{ __('Komende afspraken') }}</h2>
      <div class="mt-4">
        <x-agenda.appointment-list :items="$upcoming" :now="$now" />
      </div>
    </section>

    <section class="mt-12 grid gap-4" aria-labelledby="nieuw">
      <h2 id="nieuw" class="display display-sm">{{ __('Afspraak maken') }}</h2>

      <x-agenda.step :n="1" :title="__('Wat wil je plannen?')">
        <div class="grid gap-2 sm:grid-cols-2">
          @foreach (Agenda::APPOINTMENT_TYPES as $t)
            @php
                $selected = ($type['id'] ?? null) === $t['id'];
                $disabled = $blockedForUser($t);
                $class = 'block rounded-lg border p-4 transition-colors '.($selected ? 'border-ink ring-1 ring-ink' : 'border-line').' '.($disabled ? 'cursor-not-allowed opacity-60' : 'hover:border-ink');
            @endphp
            @if ($disabled)
              <div class="{{ $class }}" aria-disabled="true">
            @else
              <a href="{{ $qs(['type' => $t['id']]) }}" @if ($selected) aria-current="true" @endif class="{{ $class }}">
            @endif
                <span class="flex items-baseline justify-between gap-2">
                  <span class="font-semibold">{{ __($t['label']) }}</span>
                  <span class="text-xs text-muted">{{ $t['minutes'] }} min</span>
                </span>
                <span class="mt-1 block text-sm text-muted">{{ $disabled ? __('Alleen voor klanten met actieve online coaching.') : __($t['description']) }}</span>
            @if ($disabled)
              </div>
            @else
              </a>
            @endif
          @endforeach
        </div>
      </x-agenda.step>

      @if ($type && count($locations) === 0)
        <p class="flex gap-2 rounded-lg bg-surface p-4 text-sm">
          <x-icon name="Info" class="size-5 shrink-0" />
          {{ __('Er zijn op dit moment geen tijden beschikbaar voor dit soort afspraak.') }} <a href="{{ Locale::path('/contact') }}" class="font-semibold underline">{{ __('Neem contact op') }}</a>.
        </p>
      @endif

      @if ($type && count($locations) > 1)
        <x-agenda.step :n="2" :title="__('Waar?')">
          <div class="flex flex-wrap gap-2">
            @foreach ($locations as $l)
              <a href="{{ $qs(['type' => $type['id'], 'locatie' => $l]) }}" @if ($location === $l) aria-current="true" @endif
                class="rounded-lg border px-4 py-2.5 text-sm font-medium transition-colors {{ $location === $l ? 'border-ink bg-ink text-white' : 'border-line hover:border-ink' }}">{{ __(Agenda::getAgendaLocation($l)['label']) }}</a>
            @endforeach
          </div>
        </x-agenda.step>
      @endif

      @if ($type && $location)
        <x-agenda.step :n="count($locations) > 1 ? 3 : 2" :title="__('Welke dag?')">
          @if (count($days) === 0)
            <p class="text-sm text-muted">{{ __('De komende :weeks weken zijn er geen tijden vrij op deze locatie.', ['weeks' => Agenda::BOOKING_RULES['horizonDays'] / 7]) }}</p>
          @else
            <div class="flex flex-wrap gap-2">
              @foreach ($days as $d)
                <a href="{{ $qs(['type' => $type['id'], 'locatie' => $location, 'datum' => $d['day']]) }}" @if ($day === $d['day']) aria-current="date" @endif
                  class="rounded-lg border px-3 py-2 text-sm transition-colors {{ $day === $d['day'] ? 'border-ink bg-ink text-white' : 'border-line hover:border-ink' }}"><span class="font-medium first-letter:uppercase">{{ Agenda::formatDayShort(Agenda::dayToDate($d['day']), Locale::current()) }}</span></a>
              @endforeach
            </div>
          @endif
        </x-agenda.step>
      @endif

      @if ($type && $location && $day)
        <x-agenda.step :n="count($locations) > 1 ? 4 : 3" :title="__('Tijd op :day', ['day' => Agenda::formatDayLong(Agenda::dayToDate($day), Locale::current())])">
          @if (count($slots) === 0)
            <p class="text-sm text-muted">{{ __('Deze dag is inmiddels vol. Kies een andere dag.') }}</p>
          @else
            <form method="post" action="/account/agenda/boeken" class="grid gap-5">
              @csrf
              <input type="hidden" name="type" value="{{ $type['id'] }}">
              <input type="hidden" name="location" value="{{ $location }}">
              <x-form.alert :error="session('booking_error')" />
              <fieldset>
                <legend class="label">{{ __('Kies een tijd') }}</legend>
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                  @foreach ($slots as $slot)
                    <label class="grid h-11 cursor-pointer place-items-center rounded-lg border border-line bg-white text-sm font-semibold tabular-nums transition-colors hover:border-ink has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent">
                      <input type="radio" name="start" value="{{ $slot->utc()->format('Y-m-d\TH:i:s.v\Z') }}" class="sr-only" required>
                      {{ Agenda::formatTime($slot) }}
                    </label>
                  @endforeach
                </div>
              </fieldset>
              <label class="block">
                <span class="label">{{ __('Opmerking voor Steyn') }} <span class="font-normal text-muted">{{ __('(optioneel)') }}</span></span>
                <textarea name="note" maxlength="500" class="input min-h-20" placeholder="{{ __('Bijv. waar je aan wilt werken of het adres bij een training op locatie') }}"></textarea>
              </label>
              <x-form.submit class="btn btn-primary justify-self-start" :pending-text="__('Bezig met boeken…')">{{ __('Afspraak bevestigen') }}</x-form.submit>
            </form>
          @endif
          <p class="mt-4 text-xs text-muted">{{ __($loc['label']) }}: {{ __(\App\Site\Texts::agendaAddress($loc)) }}. {{ __('Afzeggen kan tot :hours uur van tevoren.', ['hours' => Agenda::BOOKING_RULES['cancelUntilHours']]) }}</p>
        </x-agenda.step>
      @endif
    </section>

    <a href="/account" class="mt-10 inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink"><x-icon name="ArrowLeft" class="size-4" /> {{ __('Terug naar mijn omgeving') }}</a>
  </div>
</x-layouts.account>
