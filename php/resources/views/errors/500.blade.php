{{-- Zonder kop en footer: die lezen uit de database, en die kan juist de oorzaak zijn. --}}
<x-layouts.base :title="__('Er ging iets mis')" :noindex="true">
  <main id="inhoud" class="hero-soft flex flex-1 items-center">
    <div class="container-site py-24 text-center">
      <a href="{{ \App\Site\Locale::path('/') }}" class="inline-block" aria-label="{{ __('SteynPT, naar de homepage') }}"><x-logo class="mx-auto h-11 w-auto" /></a>
      <h1 class="display display-xl mt-12">{{ __('Er ging iets mis') }}</h1>
      <p class="lead mx-auto mt-6 max-w-lg text-ink/75">{{ __('Door een storing kan deze pagina nu niet worden getoond. Probeer het over een paar minuten opnieuw.') }}</p>
      <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
        <x-button-link href="{{ \App\Site\Locale::path('/') }}">{{ __('Naar de homepage') }}</x-button-link>
      </div>
    </div>
  </main>
</x-layouts.base>
