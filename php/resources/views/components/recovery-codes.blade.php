@props(['codes'])
{{-- Herstelcodes één keer tonen, met kopiëren en downloaden. --}}
@php $text = "Herstelcodes SteynPT (elke code werkt één keer)\n\n".implode("\n", $codes)."\n"; @endphp
<div class="rounded-xl border border-line bg-white p-5" x-data="{ copied: false, text: @js($text) }">
  <p class="flex items-center gap-2 font-semibold"><x-icon name="KeyRound" class="size-5 text-accent" /> Je herstelcodes</p>
  <p class="mt-1 text-sm text-muted">Bewaar ze op een veilige plek, bijvoorbeeld in je wachtwoordbeheerder. Ben je je telefoon kwijt, dan log je met een van deze codes in. Je ziet ze maar één keer.</p>
  <ul class="mt-4 grid grid-cols-2 gap-2 font-mono text-base" aria-label="Herstelcodes">
    @foreach ($codes as $c)
      <li class="rounded-md bg-surface px-3 py-2 text-center tracking-wider">{{ $c }}</li>
    @endforeach
  </ul>
  <div class="mt-4 flex flex-wrap gap-2">
    <button type="button" class="btn btn-sm btn-outline"
      @click="navigator.clipboard.writeText(text).then(() => { copied = true; setTimeout(() => copied = false, 2000) }).catch(() => window.prompt('Herstelcodes', @js(implode(' ', $codes))))">
      <span x-show="!copied" class="contents"><x-icon name="Copy" class="size-4" /> Kopiëren</span>
      <span x-show="copied" x-cloak class="contents"><x-icon name="Check" class="size-4" /> Gekopieerd</span>
    </button>
    <a class="btn btn-sm btn-outline" download="steynpt-herstelcodes.txt" href="data:text/plain;charset=utf-8,{{ rawurlencode($text) }}"><x-icon name="Download" class="size-4" /> Downloaden</a>
  </div>
</div>
