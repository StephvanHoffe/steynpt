@props(['full' => false, 'dark' => false])
{{-- Taalknop: dezelfde pagina in het Nederlands of Engels (zie App\Site\Locale::switchUrl). --}}
@php
    $current = \App\Site\Locale::current();
    $options = [
        'nl' => ['short' => 'NL', 'name' => 'Nederlands'],
        'en' => ['short' => 'EN', 'name' => 'English'],
    ];
    $size = $full ? 'px-4 py-2' : 'px-2.5 py-1.5';
@endphp
<nav aria-label="{{ __('Taal') }} / Language" {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center rounded-full border p-0.5 font-semibold '.($full ? 'text-sm ' : 'text-xs ').($dark ? 'border-white/20' : 'border-line bg-white')]) }}>
  @foreach ($options as $code => $option)
    @if ($code === $current)
      <span aria-current="true" lang="{{ $code }}" class="rounded-full {{ $size }} {{ $dark ? 'bg-white text-ink' : 'bg-ink text-white' }}">
        {{ $full ? $option['name'] : $option['short'] }}@unless ($full)<span class="sr-only"> ({{ $option['name'] }})</span>@endunless
      </span>
    @else
      <a href="{{ \App\Site\Locale::switchUrl(request(), $code) }}" hreflang="{{ $code }}" lang="{{ $code }}"
        class="rounded-full transition-colors {{ $size }} {{ $dark ? 'text-white/70 hover:text-white' : 'text-ink/70 hover:text-ink' }}">
        {{ $full ? $option['name'] : $option['short'] }}@unless ($full)<span class="sr-only"> ({{ $option['name'] }})</span>@endunless
      </a>
    @endif
  @endforeach
</nav>
