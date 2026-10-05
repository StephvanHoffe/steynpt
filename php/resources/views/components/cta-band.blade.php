@props(['title' => null, 'text' => null, 'primary' => null, 'secondary' => 'default'])
{{-- Zwart blok onderaan een pagina. Zonder teksten: de standaard afsluiter uit Website-teksten ("Op elke pagina"). --}}
@php
    $fallback = \App\Site\Texts::get('algemeen')['afsluiter'];
    $title ??= $fallback['title'];
    $text ??= $fallback['text'];
    $primary ??= ['href' => '/online-coaching', 'label' => $fallback['primary']];
    if ($secondary === 'default') {
        $secondary = ['href' => '/contact', 'label' => $fallback['secondary']];
    }
@endphp
<section class="container-site py-16 lg:py-24">
  <div class="relative overflow-hidden rounded-2xl bg-ink px-6 py-14 text-white sm:px-12 lg:px-16 lg:py-20">
    <x-logo-mark variant="light" class="pointer-events-none absolute -right-6 -top-10 h-[130%] w-auto opacity-[0.06]" />
    <div class="relative max-w-2xl">
      <h2 class="display display-lg">{{ $title }}</h2>
      <p class="lead mt-5 text-white/75">{{ $text }}</p>
      <div class="mt-8 flex flex-col gap-3 sm:flex-row">
        <a href="{{ $primary['href'] }}" class="btn bg-white text-ink hover:bg-surface">
          {{ $primary['label'] }} <x-icon name="ArrowRight" class="size-4" />
        </a>
        @if ($secondary)
          <a href="{{ $secondary['href'] }}" class="btn btn-on-dark">{{ $secondary['label'] }}</a>
        @endif
      </div>
    </div>
  </div>
</section>
