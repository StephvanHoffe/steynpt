@php $topsportIcons = ['Target', 'Activity', 'ShieldCheck', 'HeartPulse', 'Video', 'CalendarRange']; @endphp
<x-layouts.site :title="$t['seo']['title']" :description="$t['seo']['description']">
  <x-page-hero :eyebrow="$t['hero']['eyebrow']" :title="$t['hero']['title']" :intro="$t['hero']['intro']" image="/images/steyn-deadlift-portret.jpg" image-alt="Steyn coacht een sporter tijdens de deadlift">
    <x-button-link href="/contact">{{ $t['hero']['primary'] }} <x-icon name="ArrowRight" class="size-4" /></x-button-link>
    <x-button-link href="#tarieven" variant="outline">{{ $t['hero']['secondary'] }}</x-button-link>
  </x-page-hero>

  <section class="container-site grid gap-14 py-20 lg:grid-cols-2 lg:py-28">
    <div>
      <x-section-heading :eyebrow="$t['leefstijl']['eyebrow']" :title="$t['leefstijl']['title']" />
      <div class="prose-site lead mt-6 text-muted">{{ \App\View\Rich::paragraphs($t['leefstijl']['body']) }}</div>
    </div>
    <div class="card self-start p-8">
      <h3 class="display text-2xl">{{ $t['leefstijl']['cardTitle'] }}</h3>
      <div class="mt-6"><x-check-list :items="$t['leefstijl']['cardList']" /></div>
    </div>
  </section>

  <section id="topsport" class="scroll-mt-28 bg-surface py-20 lg:py-28">
    <div class="container-site">
      <div class="grid items-end gap-10 lg:grid-cols-[1.3fr_1fr]">
        <x-section-heading :eyebrow="$t['topsport']['eyebrow']" :title="$t['topsport']['title']" :intro="$t['topsport']['intro']" />
        <img src="{{ asset('images/steyn-roeien.jpg') }}" alt="Steyn coacht een sporter op de roeimachine" width="1400" height="1014" loading="lazy" decoding="async"
          class="hidden aspect-[4/3] w-full max-w-md justify-self-end rounded-xl object-cover lg:block">
      </div>
      <div class="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($t['topsport']['cards'] as $i => $card)
          <div class="card-soft p-7">
            <x-icon :name="$topsportIcons[$i]" class="size-7 text-accent" />
            <h3 class="mt-5 text-lg font-semibold">{{ $card['title'] }}</h3>
            <p class="mt-2 text-[15px] leading-relaxed text-muted">{{ $card['text'] }}</p>
          </div>
        @endforeach
      </div>
      <div class="mt-10 flex flex-col gap-3 sm:flex-row">
        <x-button-link href="/contact">{{ $t['topsport']['primary'] }}</x-button-link>
        <x-button-link href="/online-coaching" variant="outline">{{ $t['topsport']['secondary'] }}</x-button-link>
      </div>
    </div>
  </section>

  <section id="tarieven" class="container-site scroll-mt-28 py-20 lg:py-28">
    <x-section-heading :eyebrow="$t['tarieven']['eyebrow']" :title="$t['tarieven']['title']" :intro="$t['tarieven']['intro']" />
    <div class="mt-14 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
      @foreach (\App\Site\Texts::ptPrices() as $card)
        <x-price-card :card="$card" :cta="$t['tarieven']['button']" />
      @endforeach
    </div>
  </section>

  <section class="bg-surface py-20 lg:py-28">
    <div class="container-site">
      <x-section-heading :eyebrow="$t['reviews']['eyebrow']" :title="$t['reviews']['title']" />
      <div class="mt-12"><x-reviews /></div>
    </div>
  </section>

  <x-cta-band :title="$t['afsluiter']['title']" :text="$t['afsluiter']['text']"
    :primary="['href' => '/contact', 'label' => $t['afsluiter']['primary']]"
    :secondary="['href' => '/online-coaching', 'label' => $t['afsluiter']['secondary']]" />
</x-layouts.site>
