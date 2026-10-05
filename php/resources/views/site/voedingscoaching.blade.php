<x-layouts.site :title="$t['seo']['title']" :description="$t['seo']['description']" :faq="$t['faq']['questions']">
  <x-page-hero :eyebrow="$t['hero']['eyebrow']" :title="$t['hero']['title']" :intro="$t['hero']['intro']" image="/images/steyn-intake.jpg" :image-alt="__('Steyn tijdens een voedingsgesprek')">
    <x-button-link href="{{ \App\Site\Locale::path('/contact') }}">{{ $t['hero']['primary'] }} <x-icon name="ArrowRight" class="size-4" /></x-button-link>
  </x-page-hero>

  <section class="container-site grid gap-14 py-20 lg:grid-cols-2 lg:py-28">
    <div>
      <x-section-heading :eyebrow="$t['begeleiding']['eyebrow']" :title="$t['begeleiding']['title']" />
      <div class="prose-site lead mt-6 text-muted">{{ \App\View\Rich::paragraphs($t['begeleiding']['body']) }}</div>
    </div>
    <div class="card self-start p-8">
      <h3 class="display text-2xl">{{ $t['begeleiding']['cardTitle'] }}</h3>
      <div class="mt-6"><x-check-list :items="$t['begeleiding']['cardList']" /></div>
    </div>
  </section>

  <section class="bg-surface">
    <div class="grid lg:grid-cols-2">
      <x-photo src="/images/meting-huidplooi.jpg" :alt="__('Huidplooimeting met een caliper')" width="1400" height="933" sizes="(min-width: 1024px) 50vw, 100vw"
        class="h-full max-h-[560px] w-full object-cover" />
      <div class="px-4 py-16 sm:px-10 lg:px-16 lg:py-24">
        <x-section-heading :eyebrow="$t['meten']['eyebrow']" :title="$t['meten']['title']" :intro="$t['meten']['intro']" />
        <div class="mt-8"><x-check-list :items="$t['meten']['points']" /></div>
      </div>
    </div>
  </section>

  <section class="container-site py-20 lg:py-28">
    <x-section-heading :eyebrow="$t['faq']['eyebrow']" :title="$t['faq']['title']" />
    <div class="mt-10 max-w-4xl"><x-faq :items="$t['faq']['questions']" /></div>
  </section>

  <x-cta-band :title="$t['afsluiter']['title']" :text="$t['afsluiter']['text']"
    :primary="['href' => '/online-coaching', 'label' => $t['afsluiter']['primary']]"
    :secondary="['href' => '/contact', 'label' => $t['afsluiter']['secondary']]" />
</x-layouts.site>
