<x-layouts.site :title="__('Pagina niet gevonden')" :noindex="true">
  <section class="hero-soft">
    <div class="container-site py-28 text-center lg:py-40">
      <p class="eyebrow justify-center text-accent">404</p>
      <h1 class="display display-xl mt-5">{{ __('Deze set bestaat niet') }}</h1>
      <p class="lead mx-auto mt-6 max-w-lg text-ink/75">{{ __('De pagina die je zoekt is verplaatst of bestaat niet meer.') }}</p>
      <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
        <x-button-link href="{{ \App\Site\Locale::path('/') }}">{{ __('Naar de homepage') }}</x-button-link>
        <x-button-link href="{{ \App\Site\Locale::path('/online-coaching') }}" variant="outline">{{ __('Bekijk online coaching') }}</x-button-link>
      </div>
    </div>
  </section>
</x-layouts.site>
