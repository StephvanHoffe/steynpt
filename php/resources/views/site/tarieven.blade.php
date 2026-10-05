@php
    $jump = [
        ['href' => '#online', 'label' => $t['menu']['online']],
        ['href' => '#personal-training', 'label' => $t['menu']['pt']],
        ['href' => '#ademcoaching', 'label' => $t['menu']['adem']],
    ];
@endphp
<x-layouts.site :title="$t['seo']['title']" :description="$t['seo']['description']">
  <x-page-hero :eyebrow="$t['hero']['eyebrow']" :title="$t['hero']['title']" :intro="$t['hero']['intro']">
    <x-button-link href="/contact">{{ $t['hero']['primary'] }}</x-button-link>
  </x-page-hero>

  <nav aria-label="Tarieven per dienst" class="border-b border-line bg-paper">
    <ul class="container-site flex gap-2 overflow-x-auto py-3">
      @foreach ($jump as $j)
        <li><a href="{{ $j['href'] }}" class="block whitespace-nowrap rounded-full border border-line px-4 py-2 text-sm font-medium hover:border-ink/40">{{ $j['label'] }}</a></li>
      @endforeach
    </ul>
  </nav>

  <section id="online" class="scroll-mt-28 bg-surface py-20 lg:py-24">
    <div class="container-site">
      <x-section-heading :eyebrow="$t['online']['eyebrow']" :title="$t['online']['title']" :intro="$t['online']['intro']" />
      <div class="mt-12"><x-online-plans /></div>
    </div>
  </section>

  <section id="personal-training" class="container-site scroll-mt-28 py-20 lg:py-24">
    <x-section-heading :eyebrow="$t['pt']['eyebrow']" :title="$t['pt']['title']" :intro="$t['pt']['intro']" />
    <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
      @foreach (\App\Site\Texts::ptPrices() as $card)
        <x-price-card :card="$card" :cta="$t['pt']['button']" />
      @endforeach
    </div>
    @if ($t['pt']['note'] !== '')<p class="mt-6 text-sm text-muted">{{ $t['pt']['note'] }}</p>@endif
  </section>

  <section id="ademcoaching" class="container-site scroll-mt-28 py-20 lg:py-24">
    <x-section-heading :eyebrow="$t['adem']['eyebrow']" :title="$t['adem']['title']" :intro="$t['adem']['intro']" />
    <div class="mt-12 grid max-w-4xl gap-5 md:grid-cols-2">
      <x-price-card :card="\App\Site\Texts::breathworkPrice()" :cta="$t['adem']['soloButton']" href="/contact?onderwerp=ademcoaching" />
      <article class="flex flex-col rounded-xl border border-line bg-white p-7">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">{{ $t['adem']['groupLabel'] }}</p>
        <h3 class="display mt-2 text-2xl">{{ $t['adem']['groupTitle'] }}</h3>
        <p class="display mt-6 text-4xl">{{ $t['adem']['groupPrice'] }}</p>
        <p class="mt-6 flex-1 text-sm text-ink/85">{{ $t['adem']['groupText'] }}</p>
        <x-button-link href="/contact?onderwerp=ademcoaching-groep" variant="outline" class="mt-7 w-full bg-white">{{ $t['adem']['groupButton'] }}</x-button-link>
        <a href="/ademcoaching" class="mt-4 text-center text-sm font-semibold underline decoration-accent underline-offset-4">{{ $t['adem']['moreLink'] }}</a>
      </article>
    </div>
  </section>

  <section class="bg-surface py-20 lg:py-24">
    <div class="container-site">
      <x-section-heading :eyebrow="$t['reviews']['eyebrow']" :title="$t['reviews']['title']" />
      <div class="mt-12"><x-reviews /></div>
    </div>
  </section>

  <x-cta-band />
</x-layouts.site>
