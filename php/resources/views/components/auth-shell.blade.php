@props(['title', 'aside'])
<section class="bg-paper">
  <div class="container-site grid gap-10 py-12 lg:grid-cols-[1fr_1.15fr] lg:gap-16 lg:py-20">
    <aside class="relative order-2 overflow-hidden rounded-xl bg-accent-tint p-8 text-ink lg:order-1 lg:p-12">
      <x-logo-mark class="pointer-events-none absolute -bottom-16 -right-8 h-80 w-auto opacity-[0.06]" />
      <p class="eyebrow text-accent">SteynPT account</p>
      <h2 class="display display-md mt-4">{{ $aside['title'] }}</h2>
      <div class="mt-8"><x-check-list :items="$aside['items']" /></div>
    </aside>
    <div class="order-1 lg:order-2">
      <h1 class="display display-lg">{{ $title }}</h1>
      @isset($intro)<div class="mt-4 text-muted">{{ $intro }}</div>@endisset
      <div class="mt-8">{{ $slot }}</div>
    </div>
  </div>
</section>
