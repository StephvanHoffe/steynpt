<x-layouts.site :title="__('Even rustig aan')" :noindex="true">
  <section class="hero-soft">
    <div class="container-site py-28 text-center lg:py-40">
      <p class="eyebrow justify-center text-accent">{{ __('Even rustig aan') }}</p>
      <h1 class="display display-xl mt-5">{{ __('Te veel pogingen') }}</h1>
      <p class="lead mx-auto mt-6 max-w-lg text-ink/75">{{ __('Je hebt te vaak achter elkaar iets verstuurd. Probeer het over een minuut opnieuw.') }}</p>
      <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
        <x-button-link href="{{ url()->previous('/') }}">{{ __('Terug') }}</x-button-link>
      </div>
    </div>
  </section>
</x-layouts.site>
