{{-- Rechts boven een veld: "Aangepast" en de knop om de standaardtekst terug te zetten. --}}
@php $p = json_encode($path); @endphp
<span class="flex shrink-0 items-center gap-2">
  <template x-if="custom({{ $p }})">
    <span class="rounded-full bg-accent-tint px-2 py-0.5 text-[11px] font-semibold text-accent" title="{{ $changedAt ? "Opgeslagen op {$changedAt}" : 'Nog niet opgeslagen' }}">Aangepast</span>
  </template>
  <template x-if="custom({{ $p }})">
    <button type="button" @click="resetField({{ $p }})" class="inline-flex items-center gap-1 text-xs font-medium text-muted hover:text-ink" aria-label="{{ $label }}: standaardtekst terugzetten">
      <x-icon name="RotateCcw" class="size-3" /> Standaardtekst
    </button>
  </template>
</span>
