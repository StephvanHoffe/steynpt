<x-layouts.site :title="__('Pagina bestaat niet meer')" :noindex="true">
  <section class="hero-soft">
    <div class="container-site py-28 text-center lg:py-40">
      <p class="eyebrow justify-center text-accent">410</p>
      <h1 class="display display-xl mt-5">{{ __('Deze pagina bestaat niet meer') }}</h1>
      <p class="lead mx-auto mt-6 max-w-lg text-ink/75">{{ __('SteynPT heeft een nieuwe website. Alles over personal training, online coaching, voeding en ademcoaching vind je hier.') }}</p>
      <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
        <x-button-link href="{{ \App\Site\Locale::path('/') }}">{{ __('Naar de homepage') }}</x-button-link>
        <x-button-link href="{{ \App\Site\Locale::path('/personal-training') }}" variant="outline">{{ __('Personal training') }}</x-button-link>
      </div>
    </div>
  </section>
</x-layouts.site>
