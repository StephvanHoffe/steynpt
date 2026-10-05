<x-layouts.site :title="$t['seo']['title']" :description="$t['seo']['description']">
  <x-page-hero :eyebrow="$t['hero']['eyebrow']" :title="$t['hero']['title']" :intro="$t['hero']['intro']">
    <x-button-link href="/account">{{ $t['hero']['primary'] }} <x-icon name="ArrowRight" class="size-4" /></x-button-link>
    <x-button-link href="/registreren" variant="outline">{{ $t['hero']['secondary'] }}</x-button-link>
  </x-page-hero>

  <section class="container-site grid gap-14 py-20 lg:grid-cols-2 lg:py-28">
    <x-section-heading :eyebrow="$t['stappen']['eyebrow']" :title="$t['stappen']['title']" :intro="$t['stappen']['intro']" />
    <x-referral-steps />
  </section>

  <section id="voorwaarden" class="scroll-mt-28 bg-surface py-20">
    <div class="container-site max-w-3xl">
      <h2 class="display display-sm">{{ \App\View\Rich::html($t['voorwaarden']['title']) }}</h2>
      <div class="mt-6 text-sm text-muted"><x-check-list :items="$t['voorwaarden']['points']" /></div>
    </div>
  </section>

  <x-cta-band :title="$t['afsluiter']['title']" :text="$t['afsluiter']['text']"
    :primary="['href' => '/online-coaching', 'label' => $t['afsluiter']['primary']]"
    :secondary="['href' => '/contact', 'label' => $t['afsluiter']['secondary']]" />
</x-layouts.site>
