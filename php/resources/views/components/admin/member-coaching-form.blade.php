@props(['userId', 'status', 'note' => null])
{{-- Coachingstatus en bericht voor het dashboard van de klant. --}}
@php
    $failed = session()->has('coaching_error');
    $current = $failed ? old('coachingStatus', $status) : $status;
@endphp
<form method="post" action="/admin/leden/{{ $userId }}/coaching" class="grid gap-4">
  @csrf
  <input type="hidden" name="userId" value="{{ $userId }}">
  <x-form.alert :error="session('coaching_error')" :success="session('coaching_success')" />
  <label class="block">
    <span class="label">Coachingstatus</span>
    <select name="coachingStatus" class="input">
      @foreach (\App\Services\AdminLabels::COACHING_LABEL as $s => $label)
        <option value="{{ $s }}" @selected($current === $s)>{{ $label }}</option>
      @endforeach
    </select>
    <span class="mt-1 block text-xs text-muted">Zet op Actief zodra de klant betaald start. Kwam de klant via een vriend, dan verschijnt de vriendenkorting in je overzicht.</span>
  </label>
  <label class="block">
    <span class="label">Bericht in het dashboard van de klant</span>
    <textarea name="coachNote" class="input min-h-28" placeholder="Bijv. feedback op de laatste check-in" maxlength="2000">{{ $failed ? old('coachNote', $note) : $note }}</textarea>
  </label>
  <x-form.submit class="btn btn-primary justify-self-start" pending-text="Opslaan…">Opslaan</x-form.submit>
</form>
