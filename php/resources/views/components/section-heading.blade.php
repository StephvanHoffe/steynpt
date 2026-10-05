@props(['eyebrow' => null, 'title', 'intro' => null, 'align' => 'left', 'accent' => 'text-accent'])
@php $centered = $align === 'center'; @endphp
<div {{ $attributes->class([$centered ? 'mx-auto text-center' : '', 'max-w-3xl']) }}>
  @if ($eyebrow)<p class="eyebrow text-accent {{ $centered ? 'justify-center' : '' }}">{{ $eyebrow }}</p>@endif
  <h2 class="display display-lg mt-4">{{ $title instanceof \Illuminate\Contracts\Support\Htmlable ? $title : \App\View\Rich::html($title, $accent) }}</h2>
  @if ($intro)<p class="lead mt-5 text-muted">{{ $intro }}</p>@endif
</div>
