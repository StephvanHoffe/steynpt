@props(['today'])
{{-- Vrije dagen en vakanties blokkeren (hele dagen). --}}
@php
    $failed = session()->has('block_error');
    $v = fn (string $key, string $default) => $failed ? (string) old($key, $default) : $default;
@endphp
<form method="post" action="/admin/agenda/instellingen/blokkades" class="grid gap-3">
  @csrf
  <x-form.alert :error="session('block_error')" :success="session('block_success')" />
  <div class="grid grid-cols-2 gap-3">
    <label class="block">
      <span class="label">Van</span>
      <input type="date" name="fromDay" min="{{ $today }}" value="{{ $v('fromDay', $today) }}" class="input" required>
    </label>
    <label class="block">
      <span class="label">Tot en met</span>
      <input type="date" name="toDay" min="{{ $today }}" value="{{ $v('toDay', $today) }}" class="input" required>
    </label>
    <label class="col-span-2 block">
      <span class="label">Reden (alleen voor jou)</span>
      <input name="reason" @if ($v('reason', '') !== '') value="{{ $v('reason', '') }}" @endif class="input" placeholder="Bijv. vakantie" maxlength="200">
    </label>
    <x-form.submit class="btn btn-primary col-span-2 justify-self-start" pending-text="…">Blokkeren</x-form.submit>
  </div>
</form>
