@php
    $benefitIcons = ['Brain', 'Moon', 'Battery', 'Trophy'];
    $groupIcons = ['Building2', 'Trophy', 'Users'];
    $adem = \App\Site\Texts::get('pakketten')['adem'];
@endphp
<x-layouts.site :title="$t['seo']['title']" :description="$t['seo']['description']">
  <x-page-hero :eyebrow="$t['hero']['eyebrow']" :title="$t['hero']['title']" :intro="$t['hero']['intro']" image="/images/steyn-team-gym.jpg" image-alt="Steyn met sporters in de studio">
    <x-button-link href="/contact?onderwerp=ademcoaching">{{ $t['hero']['primary'] }} <x-icon name="ArrowRight" class="size-4" /></x-button-link>
    <x-button-link href="/contact?onderwerp=ademcoaching-groep" variant="outline">{{ $t['hero']['secondary'] }}</x-button-link>
  </x-page-hero>

  <section class="container-site py-20 lg:py-28">
    <x-section-heading :eyebrow="$t['voordelen']['eyebrow']" :title="$t['voordelen']['title']" :intro="$t['voordelen']['intro']" />
    <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach ($t['voordelen']['cards'] as $i => $card)
        <div class="card p-6">
          <span class="grid size-11 place-items-center rounded-xl bg-accent-tint text-accent"><x-icon :name="$benefitIcons[$i]" class="size-5" /></span>
          <h3 class="mt-5 text-lg font-semibold">{{ $card['title'] }}</h3>
          <p class="mt-2 text-[15px] text-muted">{{ $card['text'] }}</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="bg-surface py-20 lg:py-28">
    <div class="container-site">
      <x-section-heading :eyebrow="$t['vormen']['eyebrow']" :title="$t['vormen']['title']" />
      <div class="mt-12 grid gap-5 lg:grid-cols-2">
        <article class="flex flex-col rounded-xl border border-ink bg-white p-7 ring-1 ring-ink sm:p-9">
          <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-accent"><x-icon name="User" class="size-4" /> {{ $t['vormen']['soloLabel'] }}</p>
          <h3 class="display mt-2 text-3xl">{{ $adem['name'] }}</h3>
          <p class="mt-6 flex items-baseline gap-1">
            <span class="text-lg font-semibold">€</span>
            <span class="display text-6xl">{{ $adem['price'] }}</span>
            <span class="text-lg font-semibold text-muted">,-</span>
            <span class="ml-1 text-sm text-muted">{{ $t['vormen']['perSession'] }}</span>
          </p>
          <p class="mt-2 flex items-center gap-2 text-sm font-medium"><x-icon name="Clock" class="size-4 text-accent" /> {{ $adem['duration'] }}</p>
          <div class="mt-6 flex-1"><x-check-list :items="$adem['features']" /></div>
          <x-button-link href="/contact?onderwerp=ademcoaching" variant="ink" class="mt-8">{{ $t['vormen']['soloButton'] }}</x-button-link>
        </article>

        <article class="flex flex-col rounded-xl border border-line bg-white p-7 sm:p-9">
          <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-muted"><x-icon name="Users" class="size-4" /> {{ $t['vormen']['groupLabel'] }}</p>
          <h3 class="display mt-2 text-3xl">{{ $t['vormen']['groupTitle'] }}</h3>
          <p class="display mt-6 text-4xl">{{ $t['vormen']['groupPrice'] }}</p>
          <p class="mt-2 text-sm text-muted">{{ $t['vormen']['groupText'] }}</p>
          <ul class="mt-6 grid flex-1 content-start gap-4">
            @foreach ($t['vormen']['groups'] as $i => $group)
              <li class="flex gap-4">
                <x-icon :name="$groupIcons[$i]" class="size-6 shrink-0 text-accent" />
                <span>
                  <span class="block font-semibold">{{ $group['title'] }}</span>
                  <span class="mt-0.5 block text-sm text-muted">{{ $group['text'] }}</span>
                </span>
              </li>
            @endforeach
          </ul>
          <x-button-link href="/contact?onderwerp=ademcoaching-groep" variant="outline" class="mt-8">{{ $t['vormen']['groupButton'] }}</x-button-link>
        </article>
      </div>

      <div class="mt-16 max-w-3xl">
        <h3 class="display text-2xl">{{ \App\View\Rich::html($t['sessie']['title']) }}</h3>
        <ol class="mt-8 grid gap-6 sm:grid-cols-2">
          @foreach ($t['sessie']['steps'] as $i => $step)
            <li class="flex gap-5">
              <span class="display grid size-11 shrink-0 place-items-center rounded-full border border-accent/50 text-xl text-accent">{{ $i + 1 }}</span>
              <span>
                <span class="block text-lg font-semibold">{{ $step['title'] }}</span>
                <span class="mt-1 block text-muted">{{ $step['text'] }}</span>
              </span>
            </li>
          @endforeach
        </ol>
      </div>
    </div>
  </section>

  <section class="container-site grid gap-14 py-20 lg:grid-cols-[1fr_1.4fr] lg:py-28">
    <div>
      <x-section-heading :eyebrow="$t['faq']['eyebrow']" :title="$t['faq']['title']" />
      <div class="mt-8"><x-check-list :items="$t['faq']['points']" /></div>
    </div>
    <x-faq :items="$t['faq']['questions']" />
  </section>

  <x-cta-band :title="$t['afsluiter']['title']" :text="$t['afsluiter']['text']"
    :primary="['href' => '/contact?onderwerp=ademcoaching', 'label' => $t['afsluiter']['primary']]"
    :secondary="['href' => '/contact?onderwerp=ademcoaching-groep', 'label' => $t['afsluiter']['secondary']]" />
</x-layouts.site>
