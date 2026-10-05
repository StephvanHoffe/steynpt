@props(['eyebrow', 'title', 'intro' => null, 'image' => null, 'imageAlt' => ''])
<section class="hero-soft relative overflow-hidden">
  <x-logo-mark class="pointer-events-none absolute -bottom-24 -left-10 h-[420px] w-auto opacity-[0.03]" />
  <div class="container-site grid items-center gap-10 py-16 lg:py-24 {{ $image ? 'lg:grid-cols-[1.25fr_1fr]' : '' }}">
    <div class="animate-rise">
      <x-hero-heading :eyebrow="$eyebrow" :title="$title" />
      @if ($intro)<div class="lead mt-6 max-w-2xl text-ink/75">{{ $intro }}</div>@endif
      @if ($slot->isNotEmpty())<div class="mt-8 flex flex-col gap-3 sm:flex-row">{{ $slot }}</div>@endif
    </div>
    @if ($image)
      <div class="relative mx-auto w-full max-w-md lg:max-w-none">
        <div class="absolute -inset-3 -z-0 rounded-2xl border border-line" aria-hidden="true"></div>
        <x-photo :src="$image" :alt="$imageAlt" width="900" height="1350" :priority="true" sizes="(min-width: 1024px) 40vw, 90vw"
          class="relative aspect-[4/5] w-full rounded-xl object-cover" />
      </div>
    @endif
  </div>
</section>
