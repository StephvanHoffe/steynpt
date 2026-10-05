@props(['members', 'defaults', 'minDay', 'lockedMember' => null])
{{-- Afspraak inplannen of verplaatsen door Steyn. Na een fout staan de ingevulde waarden er weer (old input). --}}
@php
    use App\Support\Agenda;

    $e = $errors->getBag('appointment');
    $failed = $e->any() || session()->has('appointment_error');
    $v = fn (string $key) => $failed ? old($key, $defaults[$key] ?? null) : ($defaults[$key] ?? null);
    $typeId = $v('type') ?? Agenda::APPOINTMENT_TYPES[0]['id'];
    $type = Agenda::getAppointmentType($typeId) ?? Agenda::APPOINTMENT_TYPES[0];
    $locationId = $v('location') ?? $type['locations'][0];
    $err = fn (string $name) => $e->first($name);
@endphp
<form method="post" action="/admin/agenda/nieuw" class="grid gap-5"
  x-data="adminAppointmentForm(@js(['types' => Agenda::APPOINTMENT_TYPES, 'locations' => Agenda::AGENDA_LOCATIONS, 'typeId' => $type['id'], 'locationId' => $locationId]))">
  @csrf
  <x-form.alert :error="session('appointment_error')" />
  @if ($defaults['replaces'])<input type="hidden" name="replaces" value="{{ $defaults['replaces'] }}">@endif

  <label class="block">
    <span class="label">Klant</span>
    @if ($lockedMember)
      <input type="hidden" name="userId" value="{{ $lockedMember['id'] }}">
      <span class="input flex items-center bg-surface">{{ $lockedMember['name'] }}</span>
    @else
      @php $userId = (string) ($v('userId') ?? ''); @endphp
      <select name="userId" class="input" required @if ($err('userId')) aria-invalid="true" @endif>
        <option value="" disabled @selected($userId === '')>Kies een klant</option>
        @foreach ($members as $m)
          <option value="{{ $m['id'] }}" @selected($userId === $m['id'])>{{ $m['name'] }}{{ $m['note'] ? ' · '.$m['note'] : '' }}</option>
        @endforeach
      </select>
    @endif
    @if ($err('userId'))<span class="mt-1 block text-sm text-danger">{{ $err('userId') }}</span>@endif
  </label>

  <fieldset>
    <legend class="label">Soort afspraak</legend>
    <div class="grid gap-2 sm:grid-cols-2">
      @foreach (Agenda::APPOINTMENT_TYPES as $t)
        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-line bg-white px-3 py-2.5 text-sm has-[:checked]:border-ink has-[:checked]:ring-1 has-[:checked]:ring-ink">
          <span class="flex items-center gap-2.5">
            <input type="radio" name="type" value="{{ $t['id'] }}" @checked($type['id'] === $t['id']) x-model="typeId" class="accent-ink">
            <span class="font-medium">{{ $t['label'] }}</span>
          </span>
          <span class="text-xs text-muted">{{ $t['minutes'] }} min</span>
        </label>
      @endforeach
    </div>
    @if ($err('type'))<span class="mt-1 block text-sm text-danger">{{ $err('type') }}</span>@endif
  </fieldset>

  <div class="grid gap-4 sm:grid-cols-3">
    <label class="block">
      <span class="label">Locatie</span>
      {{-- Alleen de locaties van het gekozen type; zonder JavaScript die van het type bij het laden. --}}
      <select name="location" class="input" x-ref="location" @change="locationId = $event.target.value">
        @foreach (Agenda::AGENDA_LOCATIONS as $l)
          @if (in_array($l['id'], $type['locations'], true))
            <option value="{{ $l['id'] }}" @selected($locationId === $l['id'])>{{ $l['label'] }}</option>
          @endif
        @endforeach
      </select>
      @if ($err('location'))<span class="mt-1 block text-sm text-danger">{{ $err('location') }}</span>@endif
    </label>
    <label class="block">
      <span class="label">Datum</span>
      <input type="date" name="day" min="{{ $minDay }}" value="{{ $v('day') }}" class="input" required @if ($err('day')) aria-invalid="true" @endif>
      @if ($err('day'))<span class="mt-1 block text-sm text-danger">{{ $err('day') }}</span>@endif
    </label>
    <label class="block">
      <span class="label">Begintijd</span>
      <input type="time" name="time" step="900" value="{{ $v('time') }}" class="input" required @if ($err('time')) aria-invalid="true" @endif>
      @if ($err('time'))<span class="mt-1 block text-sm text-danger">{{ $err('time') }}</span>@endif
    </label>
  </div>
  <p class="-mt-2 text-sm text-muted">
    Duurt <span x-text="type.minutes">{{ $type['minutes'] }}</span> minuten. Je kunt ook buiten je vaste beschikbaarheid plannen; dubbel boeken kan niet.
  </p>

  <label class="block">
    <span class="label">Opmerking <span class="font-normal text-muted">(de klant ziet deze niet)</span></span>
    <textarea name="note" maxlength="500" class="input min-h-20" placeholder="Bijv. focus op techniek of het adres bij een training op locatie">{{ $v('note') ?? '' }}</textarea>
    @if ($err('note'))<span class="mt-1 block text-sm text-danger">{{ $err('note') }}</span>@endif
  </label>

  <div class="flex flex-wrap items-center gap-3">
    <x-form.submit class="btn btn-primary" pending-text="Bezig…">{{ $defaults['replaces'] ? 'Afspraak verplaatsen' : 'Afspraak inplannen' }}</x-form.submit>
  </div>
</form>
