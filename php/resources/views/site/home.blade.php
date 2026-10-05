@php
    // Links en iconen horen vast bij de kaarten; de teksten komen uit Website-teksten (zelfde volgorde).
    $serviceMeta = [
        ['href' => '/online-coaching', 'icon' => 'Smartphone', 'highlight' => true],
        ['href' => '/personal-training', 'icon' => 'Dumbbell', 'highlight' => false],
        ['href' => '/personal-training#topsport', 'icon' => 'Trophy', 'highlight' => false],
        ['href' => '/ademcoaching', 'icon' => 'Wind', 'highlight' => false],
        ['href' => '/voedingscoaching', 'icon' => 'Apple', 'highlight' => false],
    ];
    $onlineIcons = ['ClipboardList', 'Apple', 'CalendarCheck', 'MessageCircle'];
@endphp
<x-layouts.site :title="$t['seo']['title']" :absolute-title="true" :description="$t['seo']['description']">
  {{-- Hero --}}
  <section class="hero-soft relative overflow-hidden">
    <x-logo-mark class="pointer-events-none absolute -right-20 top-10 hidden h-[640px] w-auto opacity-[0.035] lg:block" />
    <div class="container-site grid items-center gap-12 pb-16 pt-12 lg:grid-cols-[1.3fr_1fr] lg:pb-24 lg:pt-20">
      <div class="animate-rise">
        <p class="eyebrow text-accent">{{ $t['hero']['eyebrow'] }}</p>
        <h1 class="display display-xl mt-6">{{ \App\View\Rich::html($t['hero']['title']) }}</h1>
        <p class="lead mt-7 max-w-xl text-ink/75">{{ $t['hero']['intro'] }}</p>
        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
          <x-button-link href="/online-coaching">{{ $t['hero']['primary'] }} <x-icon name="ArrowRight" class="size-4" /></x-button-link>
          <x-button-link href="/contact" variant="outline">{{ $t['hero']['secondary'] }}</x-button-link>
        </div>
        <dl class="mt-12 grid max-w-xl grid-cols-2 gap-x-6 gap-y-5 border-t border-ink/10 pt-8 sm:grid-cols-4">
          @foreach ($t['hero']['stats'] as $stat)
            <div>
              <dt class="sr-only">{{ $stat['label'] }}</dt>
              <dd>
                <span class="display block text-3xl text-ink">{{ $stat['value'] }}</span>
                <span class="mt-1 block text-xs text-muted">{{ $stat['label'] }}</span>
              </dd>
            </div>
          @endforeach
        </dl>
      </div>

      <div class="relative mx-auto w-full max-w-md lg:max-w-none">
        <div class="absolute -inset-3 rounded-2xl border border-line" aria-hidden="true"></div>
        <img src="{{ asset('images/steyn-glimlach.jpg') }}" alt="Steyn van Leeuwen lacht tijdens een intakegesprek" width="900" height="1350" fetchpriority="high" decoding="async"
          class="relative aspect-[4/5] w-full rounded-xl object-cover">
        <a href="/online-coaching" class="absolute -bottom-6 left-4 right-4 flex items-center justify-between gap-4 rounded-2xl bg-accent-tint p-4 text-ink shadow-xl transition-transform hover:-translate-y-0.5 sm:left-auto sm:right-[-1rem] sm:w-72">
          <span>
            <span class="block text-[11px] font-bold uppercase tracking-wider">{{ $t['hero']['badgeLabel'] }}</span>
            <span class="block font-semibold">{{ $t['hero']['badgeText'] }}</span>
          </span>
          <x-icon name="ArrowUpRight" class="size-5 shrink-0" />
        </a>
      </div>
    </div>
  </section>

  {{-- Diensten --}}
  <div class="border-b border-line bg-white">
    <ul class="container-site flex flex-wrap items-center justify-center gap-x-8 gap-y-2 py-5 text-sm font-medium text-muted">
      @foreach ($t['diensten']['list'] as $item)
        <li class="flex items-center gap-2"><span class="size-1.5 rounded-full bg-accent" aria-hidden="true"></span>{{ $item }}</li>
      @endforeach
    </ul>
  </div>

  {{-- Online coaching spotlight --}}
  <section class="container-site py-20 lg:py-28">
    <div class="grid items-center gap-14 lg:grid-cols-[1.1fr_1fr]">
      <div>
        <x-section-heading :eyebrow="$t['online']['eyebrow']" :title="$t['online']['title']" accent="marker" :intro="$t['online']['intro']" />
        <div class="mt-10 grid gap-4 sm:grid-cols-2">
          @foreach ($t['online']['features'] as $i => $feature)
            <div class="card flex gap-4 p-5">
              <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-accent-tint text-accent">
                <x-icon :name="$onlineIcons[$i]" class="size-5" />
              </span>
              <span>
                <span class="block font-semibold">{{ $feature['title'] }}</span>
                <span class="mt-1 block text-sm text-muted">{{ $feature['text'] }}</span>
              </span>
            </div>
          @endforeach
        </div>
        <div class="mt-10 flex flex-col gap-3 sm:flex-row">
          <x-button-link href="/online-coaching" variant="ink">{{ $t['online']['primary'] }} <x-icon name="ArrowRight" class="size-4" /></x-button-link>
          <x-button-link href="/registreren" variant="outline">{{ $t['online']['secondary'] }}</x-button-link>
        </div>
      </div>
      <x-dashboard-preview />
    </div>
  </section>

  {{-- Aanbod --}}
  <section class="bg-surface py-20 lg:py-28">
    <div class="container-site">
      <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
        <x-section-heading :eyebrow="$t['aanbod']['eyebrow']" :title="$t['aanbod']['title']" :intro="$t['aanbod']['intro']" />
        <x-button-link href="/tarieven" variant="outline" class="self-start lg:self-auto">{{ $t['aanbod']['button'] }}</x-button-link>
      </div>
      <div class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($serviceMeta as $i => $meta)
          @php
              $service = $t['aanbod']['services'][$i];
              $points = $meta['highlight'] ? $t['aanbod']['onlinePoints'] : null;
          @endphp
          <a href="{{ $meta['href'] }}" class="group relative flex min-h-64 flex-col rounded-xl border p-7 transition-all duration-300 hover:-translate-y-1 {{ $meta['highlight'] ? 'border-ink bg-white ring-1 ring-ink md:row-span-2' : 'border-line bg-paper hover:border-ink/40' }}">
            <div class="flex items-start justify-between">
              <span class="grid size-12 place-items-center rounded-xl {{ $meta['highlight'] ? 'bg-ink text-white' : 'bg-accent-tint text-accent' }}">
                <x-icon :name="$meta['icon']" class="size-6" />
              </span>
              @if ($meta['highlight'])
                <span class="rounded-full bg-accent-tint px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-ink">{{ $t['aanbod']['badge'] }}</span>
              @endif
            </div>
            <h3 class="display mt-8 text-3xl">{{ $service['title'] }}</h3>
            <p class="mt-3 text-[15px] leading-relaxed text-muted {{ $points ? '' : 'flex-1' }}">{{ $service['text'] }}</p>
            @if ($points)
              <div class="mt-6 flex-1 text-sm"><x-check-list :items="$points" /></div>
            @endif
            <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold">{{ $t['aanbod']['more'] }}<x-icon name="ArrowRight" class="size-4 transition-transform group-hover:translate-x-1" /></span>
          </a>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Over Steyn --}}
  <section class="bg-surface py-20 lg:py-28">
    <div class="container-site grid items-center gap-14 lg:grid-cols-2">
      <div class="relative">
        <img src="{{ asset('images/steyn-coaching-dumbbell.jpg') }}" alt="Steyn begeleidt een sporter bij een dumbbell press" width="900" height="1350" loading="lazy" decoding="async"
          class="aspect-[4/5] w-full rounded-xl object-cover lg:max-w-lg">
        <div class="absolute -bottom-6 right-0 max-w-[16rem] rounded-2xl bg-paper p-5 text-ink shadow-xl sm:right-6 lg:right-0">
          <p class="display text-4xl">{{ $t['over']['cardTitle'] }}</p>
          <p class="mt-1 text-sm text-muted">{{ $t['over']['cardText'] }}</p>
        </div>
      </div>
      <div>
        <x-section-heading :eyebrow="$t['over']['eyebrow']" :title="$t['over']['title']" :intro="$t['over']['intro']" />
        <ul class="mt-8 flex flex-wrap gap-2">
          @foreach ($shared['expertises']['list'] as $e)
            <li class="rounded-full border border-ink/15 px-3.5 py-1.5 text-sm text-ink/85">{{ $e }}</li>
          @endforeach
        </ul>
        <x-button-link href="/over-steyn" class="mt-10">{{ $t['over']['button'] }} <x-icon name="ArrowRight" class="size-4" /></x-button-link>
      </div>
    </div>
  </section>

  {{-- Werkwijze --}}
  <section class="container-site py-20 lg:py-28">
    <x-section-heading :eyebrow="$t['werkwijze']['eyebrow']" :title="$t['werkwijze']['title']" :intro="$t['werkwijze']['intro']" />
    <ol class="mt-14 grid gap-px overflow-hidden rounded-xl border border-line bg-line md:grid-cols-2 lg:grid-cols-4">
      @foreach ($shared['werkwijze']['steps'] as $i => $step)
        <li class="bg-paper p-7">
          <span class="display text-6xl text-accent">0{{ $i + 1 }}</span>
          <h3 class="display mt-6 text-2xl">{{ $step['title'] }}</h3>
          <p class="mt-3 text-[15px] leading-relaxed text-muted">{{ $step['text'] }}</p>
        </li>
      @endforeach
    </ol>
  </section>

  {{-- Vriendenactie --}}
  <section class="bg-surface py-20 lg:py-28">
    <div class="container-site grid gap-14 lg:grid-cols-[1fr_1.1fr] lg:items-center">
      <div>
        <x-section-heading :eyebrow="$t['vriendenactie']['eyebrow']" :title="$t['vriendenactie']['title']" :intro="$t['vriendenactie']['intro']" />
        <div class="mt-10 flex flex-col gap-3 sm:flex-row">
          <x-button-link href="/registreren">{{ $t['vriendenactie']['primary'] }} <x-icon name="ArrowRight" class="size-4" /></x-button-link>
          <x-button-link href="/vriend-uitnodigen" variant="outline">{{ $t['vriendenactie']['secondary'] }}</x-button-link>
        </div>
      </div>
      <x-referral-steps />
    </div>
  </section>

  {{-- Reviews --}}
  <section class="container-site py-20 lg:py-28">
    <x-section-heading :eyebrow="$t['reviews']['eyebrow']" :title="$t['reviews']['title']" />
    <div class="mt-12"><x-reviews /></div>
  </section>

  {{-- Locaties --}}
  <section class="bg-surface py-20 lg:py-28">
    <div class="container-site">
      <x-section-heading :eyebrow="$t['locaties']['eyebrow']" :title="$t['locaties']['title']" :intro="$t['locaties']['intro']" />
      <div class="mt-12"><x-locations /></div>
    </div>
  </section>

  <x-cta-band />
</x-layouts.site>
