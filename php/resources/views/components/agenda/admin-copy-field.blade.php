@props(['value', 'label'])
{{-- Alleen-lezen veld met een kopieerknop (valt terug op een venster om zelf te kopiëren). --}}
<div class="flex items-center gap-2 rounded-lg border border-line bg-white p-1.5 pl-3" x-data="adminCopyField(@js($value), @js($label))">
  <input readonly value="{{ $value }}" aria-label="{{ $label }}" class="min-w-0 flex-1 bg-transparent font-mono text-xs outline-none" @focus="$el.select()">
  <button type="button" class="btn btn-sm btn-primary shrink-0" @click="copy()">
    <span x-show="copied" x-cloak class="contents"><x-icon name="Check" class="size-4" /></span>
    <span x-show="!copied" class="contents"><x-icon name="Copy" class="size-4" /></span>
    <span x-text="copied ? 'Gekopieerd' : 'Kopieer'">Kopieer</span>
  </button>
</div>
