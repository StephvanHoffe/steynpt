@props(['userId', 'today'])
{{-- Meting toevoegen (beheer). Fouten per veld staan in de error bag 'measurement'; na opslaan is het formulier weer leeg. --}}
@php
    $e = $errors->getBag('measurement');
    $failed = $e->any();
    $v = fn (string $key, $default = null) => $failed ? old($key, $default) : $default;
@endphp
<form method="post" action="/admin/leden/{{ $userId }}/metingen" class="grid gap-4">
  @csrf
  <input type="hidden" name="userId" value="{{ $userId }}">
  <x-form.alert :error="session('measurement_error')" :success="session('measurement_success')" />
  <label class="block max-w-xs">
    <span class="label">Datum</span>
    <input type="date" name="measuredAt" value="{{ $v('measuredAt', $today) }}" max="{{ $today }}" class="input" @if ($e->has('measuredAt')) aria-invalid="true" @endif>
    @if ($e->has('measuredAt'))<span class="field-error block">{{ $e->first('measuredAt') }}</span>@endif
  </label>
  <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
    @foreach (\App\Support\Progress::MEASUREMENT_FIELDS as $f)
      <label class="block">
        <span class="label">{{ $f['label'] }} <span class="font-normal text-muted">({{ $f['unit'] }})</span></span>
        <input name="{{ $f['key'] }}" inputmode="decimal" @if (($current = $v($f['key'])) !== null && $current !== '') value="{{ $current }}" @endif class="input" @if ($e->has($f['key'])) aria-invalid="true" @endif>
        @if ($e->has($f['key']))<span class="field-error block">{{ $e->first($f['key']) }}</span>@endif
      </label>
    @endforeach
  </div>
  <label class="block">
    <span class="label">Notitie <span class="font-normal text-muted">(zichtbaar voor de klant)</span></span>
    <textarea name="note" class="input min-h-16" placeholder="Bijv. gemeten met huidplooimeter, nuchter">{{ $v('note') }}</textarea>
  </label>
  <x-form.submit class="btn btn-primary justify-self-start" pending-text="Opslaan…">Meting opslaan</x-form.submit>
</form>
