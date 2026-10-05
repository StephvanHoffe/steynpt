@php
    $audienceIcons = ['Dumbbell', 'Briefcase', 'Plane', 'Trophy'];
    $ref = $invitation['code'] ?? null;
    $registerHref = '/registreren'.($ref ? '?ref='.$ref : '');
@endphp
<x-layouts.site :title="$t['seo']['title']" :description="$t['seo']['description']" :faq="$t['faq']['questions']">
  <section class="hero-soft relative overflow-hidden">
    <div class="container-site grid items-center gap-14 py-16 lg:grid-cols-[1.2fr_1fr] lg:py-24">
      <div class="animate-rise">
        @if ($invitation)
          <p class="mb-6 inline-flex items-center gap-2 rounded-full bg-accent-tint px-4 py-2 text-sm font-semibold text-ink">
            <x-icon name="Gift" class="size-4" />
            {{ $invitation['firstName'] }} nodigt je uit: je krijgt {{ $shared['vriendenactie']['friendReward'] }}
          </p>@endif
        <x-hero-heading :eyebrow="$t['hero']['eyebrow']" :title="$t['hero']['title']" />
        <p class="lead mt-6 max-w-xl text-ink/75">{{ $t['hero']['intro'] }}</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
          <x-button-link href="#pakketten">{{ $t['hero']['primary'] }} <x-icon name="ArrowRight" class="size-4" /></x-button-link>
          <x-button-link :href="$registerHref" variant="outline">{{ $t['hero']['secondary'] }}</x-button-link>
        </div>
        @if ($t['hero']['note'] !== '')
          <p class="mt-5 text-sm text-muted">{{ $t['hero']['note'] }}</p>
        @endif
      </div>
      <x-dashboard-preview />
    </div>
  </section>

  <section class="container-site py-20 lg:py-28">
    <x-section-heading :eyebrow="$t['voorWie']['eyebrow']" :title="$t['voorWie']['title']" :intro="$t['voorWie']['intro']" />
    <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      @foreach ($t['voorWie']['cards'] as $i => $card)
        <div class="card p-6">
          <span class="grid size-11 place-items-center rounded-xl bg-accent-tint text-accent"><x-icon :name="$audienceIcons[$i]" class="size-5" /></span>
          <h3 class="mt-5 text-lg font-semibold">{{ $card['title'] }}</h3>
          <p class="mt-2 text-[15px] text-muted">{{ $card['text'] }}</p>
        </div>
      @endforeach
    </div>
  </section>

  <section class="bg-surface py-20 lg:py-28">
    <div class="container-site">
      <x-section-heading :eyebrow="$t['stappen']['eyebrow']" :title="$t['stappen']['title']" />
      <ol class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        @foreach ($t['stappen']['steps'] as $i => $step)
          <li class="relative rounded-xl bg-paper p-7">
            <span class="display grid size-12 place-items-center rounded-full bg-ink text-2xl text-white">{{ $i + 1 }}</span>
            <h3 class="display mt-6 text-2xl">{{ $step['title'] }}</h3>
            <p class="mt-3 text-[15px] leading-relaxed text-muted">{{ $step['text'] }}</p>
          </li>
        @endforeach
      </ol>
    </div>
  </section>

  <section id="pakketten" class="scroll-mt-28 bg-surface py-20 lg:py-28">
    <div class="container-site">
      <x-section-heading align="center" :eyebrow="$t['pakketten']['eyebrow']" :title="$t['pakketten']['title']" :intro="$t['pakketten']['intro']" />
      <div class="mt-14"><x-online-plans :referral="$ref" /></div>
      <p class="mt-8 text-center text-sm text-muted">{{ $t['pakketten']['note'] }} <a href="{{ \App\Site\Locale::path('/contact') }}" class="font-semibold text-ink underline decoration-accent underline-offset-4">{{ $t['pakketten']['noteLink'] }}</a></p>
    </div>
  </section>

  <section class="container-site grid gap-14 py-20 lg:grid-cols-2 lg:items-center lg:py-28">
    <div>
      <x-section-heading :eyebrow="$t['vriendenactie']['eyebrow']" :title="$t['vriendenactie']['title']" :intro="$t['vriendenactie']['intro']" />
      <x-button-link href="{{ \App\Site\Locale::path('/vriend-uitnodigen') }}" variant="outline" class="mt-8">{{ $t['vriendenactie']['button'] }}</x-button-link>
    </div>
    <x-referral-steps />
  </section>

  <section class="container-site pb-8">
    <x-section-heading :eyebrow="$t['faq']['eyebrow']" :title="$t['faq']['title']" />
    <div class="mt-10 max-w-4xl"><x-faq :items="$t['faq']['questions']" /></div>
  </section>

  <x-cta-band :title="$t['afsluiter']['title']" :text="$t['afsluiter']['text']"
    :primary="['href' => $registerHref, 'label' => $t['afsluiter']['primary']]"
    :secondary="['href' => '/contact', 'label' => $t['afsluiter']['secondary']]" />
</x-layouts.site>
