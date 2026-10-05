{{-- Visuele preview van Mijn omgeving voor de marketingpagina's (voorbeeldgegevens). --}}
@php
    $weights = [82.4, 81.9, 81.1, 80.6, 80.2, 79.4];
    $min = 79;
    $max = 83;
    $fmt = fn ($n) => rtrim(rtrim(sprintf('%.12F', $n), '0'), '.');
    $points = implode(' ', array_map(fn ($w, $i) => $fmt(($i / (count($weights) - 1)) * 100).','.$fmt((($max - $w) / ($max - $min)) * 40 + 4), $weights, array_keys($weights)));
@endphp
<div class="relative mx-auto w-full max-w-md" aria-hidden="true">
  <div class="rounded-xl border border-line bg-white p-5 shadow-xl shadow-ink/5">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs text-muted">{{ __('Mijn omgeving') }}</p>
        <p class="display text-xl">{{ __('Hoi Lisa') }}</p>
      </div>
      <span class="rounded bg-ink px-2 py-1 text-[11px] font-semibold uppercase tracking-wider text-white">Pro</span>
    </div>

    <div class="mt-4 rounded-lg border border-line p-4">
      <p class="flex items-center gap-2 text-xs font-semibold text-muted">
        <x-icon name="LineChart" class="size-3.5" /> {{ __('Gewicht') }}
      </p>
      <div class="mt-1 flex items-end justify-between gap-4">
        <p class="text-2xl font-semibold tabular-nums">{{ __('79,4') }} <span class="text-sm font-normal text-muted">kg</span></p>
        <p class="text-xs text-muted">{{ __('−3,0 kg sinds 1 aug') }}</p>
      </div>
      <svg viewBox="0 0 100 48" preserveAspectRatio="none" class="mt-2 h-14 w-full">
        <polygon points="0,48 {{ $points }} 100,48" fill="var(--color-accent)" opacity="0.1"></polygon>
        <polyline points="{{ $points }}" fill="none" stroke="var(--color-accent)" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round"></polyline>
      </svg>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3">
      <div class="rounded-lg bg-surface p-3">
        <p class="flex items-center gap-1.5 text-xs text-muted"><x-icon name="CalendarDays" class="size-3.5" /> {{ __('Volgende afspraak') }}</p>
        <p class="mt-1 text-sm font-semibold">{{ __('Ma 12 okt, 07:30') }}</p>
        <p class="text-xs text-muted">Personal training · Gymbase</p>
      </div>
      <div class="rounded-lg bg-surface p-3">
        <p class="flex items-center gap-1.5 text-xs text-muted"><x-icon name="CalendarCheck" class="size-3.5" /> {{ __('Check-in week 41') }}</p>
        <p class="mt-1 text-sm font-semibold">{{ __('Energie 4/5') }}</p>
        <p class="text-xs text-muted">{{ __('3 trainingen') }}</p>
      </div>
    </div>

    <p class="mt-3 rounded-lg border border-accent/30 bg-accent-tint p-3 text-xs leading-relaxed">
      <span class="font-semibold text-accent">Steyn:</span> {{ __('Sterke week. We voeren het gewicht bij de squat op met 2,5 kg.') }}
    </p>
  </div>
</div>
