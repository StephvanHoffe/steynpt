{{-- Ook tijdens onderhoud (php artisan down): zonder kop en footer, want die lezen uit de database. --}}
<x-layouts.base :title="__('Even onderhoud')" :noindex="true">
  <main id="inhoud" class="hero-soft flex flex-1 items-center">
    <div class="container-site py-24 text-center">
      <x-logo class="mx-auto h-11 w-auto" />
      <h1 class="display display-xl mt-12">{{ __('Even onderhoud') }}</h1>
      <p class="lead mx-auto mt-6 max-w-lg text-ink/75">{{ __('We werken aan de website. Over een paar minuten ben je weer welkom.') }}</p>
    </div>
  </main>
</x-layouts.base>
