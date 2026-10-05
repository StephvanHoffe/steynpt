@props(['pendingText' => 'Bezig…', 'disabledWhen' => null])
{{-- Verstuurknop die tijdens het versturen "Bezig…" toont en niet nog eens ingedrukt kan worden.
     disabledWhen: extra Alpine-voorwaarde, bijvoorbeeld "!saved". Een eigen class vervangt de standaard. --}}
<button type="submit" class="{{ $attributes->get('class', 'btn btn-primary w-full') }}" {{ $attributes->except('class') }}
  x-data="{ pending: false }" x-init="$el.form?.addEventListener('submit', () => setTimeout(() => pending = true))"
  :disabled="pending{{ $disabledWhen ? ' || ('.$disabledWhen.')' : '' }}" @if ($disabledWhen) disabled @endif>
  <span x-show="!pending" class="contents">{{ $slot }}</span>
  <span x-show="pending" x-cloak class="contents"><x-icon name="LoaderCircle" class="size-4 animate-spin" /> {{ $pendingText }}</span>
</button>
