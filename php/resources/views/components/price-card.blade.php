@props(['card', 'cta', 'href' => '/contact'])
@php
    $featured = $card['featured'] ?? false;
    $labels = \App\Site\Texts::get('pakketten')['labels'];
@endphp
<article class="relative flex flex-col rounded-xl border p-7 transition-transform duration-300 hover:-translate-y-1 {{ $featured ? 'border-ink bg-white ring-1 ring-ink shadow-lg shadow-ink/5' : 'border-line bg-white' }}">
  @if ($featured)
    <span class="absolute -top-3 left-7 rounded bg-ink px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-white">{{ $labels['featured'] }}</span>
  @endif
  <p class="text-xs font-semibold uppercase tracking-[0.14em] {{ $featured ? 'text-accent' : 'text-muted' }}">{{ $card['label'] }}</p>
  <h3 class="display mt-2 text-2xl hyphens-auto">{{ $card['name'] }}</h3>
  <p class="mt-6 flex items-baseline gap-1">
    <span class="text-lg font-semibold">€</span>
    <span class="display text-6xl">{{ $card['price'] }}</span>
    <span class="text-lg font-semibold text-muted">,-</span>
    @if (! empty($card['unit']))<span class="ml-1 text-sm text-muted">{{ $card['unit'] }}</span>@endif
  </p>
  <ul class="mt-6 flex-1 space-y-2.5 text-sm">
    @foreach ($card['features'] as $f)
      <li class="flex gap-2.5">
        <x-icon name="Check" class="mt-0.5 size-4 shrink-0 text-accent" stroke-width="2.5" />
        <span class="text-ink/85">{{ $f }}</span>
      </li>
    @endforeach
  </ul>
  @if (! empty($card['note']))<p class="mt-5 text-xs text-muted">{{ $card['note'] }}</p>@endif
  <a href="{{ $href }}" class="btn mt-7 w-full {{ $featured ? 'btn-primary' : 'btn-outline bg-white' }}">{{ $cta }}</a>
</article>
