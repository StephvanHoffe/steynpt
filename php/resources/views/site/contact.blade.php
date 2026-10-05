@php $locatie = $shared['locatie']; @endphp
<x-layouts.site :title="$t['seo']['title']" :description="$t['seo']['description']">
  <section class="hero-soft">
    <div class="container-site grid gap-12 py-16 lg:grid-cols-[1fr_1.1fr] lg:py-24">
      <div class="animate-rise">
        <x-hero-heading :eyebrow="$t['hero']['eyebrow']" :title="$t['hero']['title']" />
        <p class="lead mt-6 max-w-xl text-ink/75">{{ $t['hero']['intro'] }}</p>
        <p class="mt-8 flex items-center gap-3 text-ink/85">
          <x-icon name="Clock" class="size-5 text-accent" />
          {{ $locatie['responseTime'] }}
        </p>

        <div class="mt-10 grid gap-4 sm:grid-cols-2">
          <a href="{{ \App\Site\Site::mapsUrl($locatie['street'], $locatie['city']) }}" target="_blank" rel="noopener noreferrer" class="card-soft block p-5 transition-colors hover:border-accent/60">
            <x-icon name="MapPin" class="size-5 text-accent" />
            <p class="mt-3 font-semibold">{{ $locatie['name'] }}</p>
            <p class="mt-1 text-sm text-muted">{{ $locatie['street'] }}<br>{{ $locatie['city'] }}@if ($locatie['area'] !== '')<br>{{ $locatie['area'] }}@endif</p>
          </a>
          @if ($locatie['directions'] !== '')
            <div class="card-soft p-5">
              <x-icon name="TramFront" class="size-5 text-accent" />
              <p class="mt-3 font-semibold">{{ __('Bereikbaarheid') }}</p>
              <p class="mt-1 text-sm text-muted">{{ $locatie['directions'] }}</p>
            </div>
          @endif
          @if ($locatie['phone'] !== '' || $locatie['email'] !== '')
            <div class="card-soft p-5 sm:col-span-2">
              <p class="font-semibold">{{ __('Direct contact') }}</p>
              <ul class="mt-2 space-y-1.5 text-sm text-muted">
                @if ($locatie['phone'] !== '')
                  <li><a href="tel:{{ \App\Site\Site::telHref($locatie['phone']) }}" class="inline-flex items-center gap-2 hover:text-ink"><x-icon name="Phone" class="size-4 text-accent" /> {{ $locatie['phone'] }}</a></li>
                @endif
                @if ($locatie['email'] !== '')
                  <li><a href="mailto:{{ $locatie['email'] }}" class="inline-flex items-center gap-2 hover:text-ink"><x-icon name="Mail" class="size-4 text-accent" /> {{ $locatie['email'] }}</a></li>
                @endif
              </ul>
            </div>
          @endif
        </div>
        <p class="mt-6 max-w-xl text-sm leading-relaxed text-muted"><strong class="text-ink">{{ $t['hero']['tipTitle'] }}</strong> {{ $t['hero']['tipText'] }} {{ $locatie['onLocation'] }}</p>
        <a href="{{ $locatie['instagramUrl'] }}" target="_blank" rel="noopener noreferrer" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-ink hover:text-accent">
          <x-instagram-icon class="size-4" /> Volg {{ $locatie['instagramHandle'] }} op Instagram
        </a>
      </div>

      <div class="rounded-xl bg-paper p-6 text-ink sm:p-10">
        <h2 class="display display-sm">{{ \App\View\Rich::html($t['formulier']['title']) }}</h2>
        <p class="mt-2 text-sm text-muted">{{ $t['formulier']['intro'] }}</p>
        <div class="relative mt-8">
          @if (session('contact_success'))
            <div class="space-y-4">
              <x-form.alert :success="session('contact_success')" />
              <p class="text-sm text-muted">{{ __('Benieuwd naar online coaching?') }} <a href="/registreren" class="font-semibold underline">{{ __('Maak alvast je gratis account aan') }}</a>{{ __(', dan sta je direct klaar.') }}</p>
            </div>
          @else
            <form method="post" action="{{ \App\Site\Locale::path('/contact') }}" class="grid gap-5" novalidate>
              @csrf
              <x-form.field label="Volledige naam" name="name" autocomplete="name" required />
              <div class="grid gap-5 sm:grid-cols-2">
                <x-form.field label="E-mailadres" name="email" type="email" autocomplete="email" required />
                <x-form.field label="Telefoonnummer" name="phone" type="tel" autocomplete="tel" hint="Optioneel, dan bellen we je" />
              </div>
              <x-form.select label="Waar heb je interesse in?" name="interest" :options="\App\Site\Site::INTERESTS" placeholder="Maak een keuze" :value="$defaultInterest" required />
              <div>
                <label for="f-message" class="label">{{ __('Bericht') }} <span class="font-normal text-muted">{{ __('(optioneel)') }}</span></label>
                <textarea id="f-message" name="message" class="input" placeholder="{{ __('Vertel kort over je doel of vraag') }}">{{ old('message') }}</textarea>
              </div>
              <div aria-hidden="true" class="absolute left-[-9999px]">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
              </div>
              <x-form.submit pending-text="Versturen…">{{ __('Verstuur aanvraag') }} <x-icon name="Send" class="size-4" /></x-form.submit>
              <p class="text-xs text-muted">{{ __('We gebruiken je gegevens alleen om contact met je op te nemen. Zie onze') }} <a href="{{ \App\Site\Locale::path('/privacy') }}" class="underline">{{ __('privacyverklaring') }}</a>.</p>
            </form>
          @endif
        </div>
      </div>
    </div>
  </section>
</x-layouts.site>
