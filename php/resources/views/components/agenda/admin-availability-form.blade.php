{{-- Tijden toevoegen aan de wekelijkse beschikbaarheid. --}}
@php
    use App\Support\Agenda;

    $failed = session()->has('availability_error');
    $v = fn (string $key, string $default) => $failed ? (string) old($key, $default) : $default;
@endphp
<form method="post" action="/admin/agenda/instellingen/beschikbaarheid" class="grid gap-3">
  @csrf
  <x-form.alert :error="session('availability_error')" :success="session('availability_success')" />
  <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
    <label class="block">
      <span class="label">Dag</span>
      <select name="weekday" class="input">
        @foreach (Agenda::WEEKDAYS as $i => $d)
          <option value="{{ $i + 1 }}" @selected($v('weekday', '1') === (string) ($i + 1))>{{ $d }}</option>
        @endforeach
      </select>
    </label>
    <label class="block">
      <span class="label">Van</span>
      <input type="time" name="startTime" value="{{ $v('startTime', '07:00') }}" step="1800" class="input" required>
    </label>
    <label class="block">
      <span class="label">Tot</span>
      <input type="time" name="endTime" value="{{ $v('endTime', '12:00') }}" step="1800" class="input" required>
    </label>
    <label class="block">
      <span class="label">Locatie</span>
      <select name="location" class="input">
        @foreach (Agenda::AGENDA_LOCATIONS as $l)
          <option value="{{ $l['id'] }}" @selected($v('location', 'gymbase') === $l['id'])>{{ $l['label'] }}</option>
        @endforeach
      </select>
    </label>
    <x-form.submit class="btn btn-primary col-span-2 justify-self-start sm:col-span-4" pending-text="…">Toevoegen</x-form.submit>
  </div>
</form>
