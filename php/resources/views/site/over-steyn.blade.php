<x-layouts.site :title="$t['seo']['title']" :description="$t['seo']['description']">
  <x-page-hero :eyebrow="$t['hero']['eyebrow']" :title="$t['hero']['title']" :intro="$t['hero']['intro']" image="/images/steyn-headshot.jpg" :image-alt="__('Portret van Steyn van Leeuwen')">
    <x-button-link href="{{ \App\Site\Locale::path('/contact') }}">{{ $t['hero']['primary'] }} <x-icon name="ArrowRight" class="size-4" /></x-button-link>
  </x-page-hero>

  <section class="container-site grid gap-14 py-20 lg:grid-cols-[1.4fr_1fr] lg:py-28">
    <div>
      <x-section-heading :eyebrow="$t['verhaal']['eyebrow']" :title="$t['verhaal']['title']" />
      <div class="prose-site lead mt-6 text-muted">{{ \App\View\Rich::paragraphs($t['verhaal']['body']) }}</div>
    </div>
    <div class="space-y-5">
      <x-photo src="/images/steyn-deadlift.jpg" :alt="__('Steyn coacht een sporter bij de deadlift')" width="500" height="500" sizes="(min-width: 1024px) 500px, 90vw" class="aspect-square w-full rounded-xl object-cover" />
      <div class="card p-7">
        <h3 class="display text-2xl">{{ $t['verhaal']['expertisesTitle'] }}</h3>
        <ul class="mt-5 flex flex-wrap gap-2">
          @foreach ($shared['expertises']['list'] as $e)
            <li class="rounded-full bg-surface px-3.5 py-1.5 text-sm">{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </section>

  <section class="bg-surface py-20 lg:py-28">
    <div class="container-site">
      <x-section-heading :eyebrow="$t['werkwijze']['eyebrow']" :title="$t['werkwijze']['title']" :intro="$t['werkwijze']['intro']" />
      <ol class="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        @foreach ($shared['werkwijze']['steps'] as $i => $step)
          <li class="card-soft p-7">
            <span class="display text-5xl text-accent">0{{ $i + 1 }}</span>
            <h3 class="display mt-5 text-2xl">{{ $step['title'] }}</h3>
            <p class="mt-3 text-[15px] leading-relaxed text-muted">{{ $step['text'] }}</p>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  <x-cta-band :title="$t['afsluiter']['title']" :text="$t['afsluiter']['text']"
    :primary="['href' => '/contact', 'label' => $t['afsluiter']['primary']]"
    :secondary="['href' => '/online-coaching', 'label' => $t['afsluiter']['secondary']]" />
</x-layouts.site>
