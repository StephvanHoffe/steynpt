<x-layouts.site title="Pagina verlopen" :noindex="true">
  <section class="hero-soft">
    <div class="container-site py-28 text-center lg:py-40">
      <p class="eyebrow justify-center text-accent">Even opnieuw</p>
      <h1 class="display display-xl mt-5">Deze pagina is verlopen</h1>
      <p class="lead mx-auto mt-6 max-w-lg text-ink/75">Je hebt de pagina te lang open laten staan. Ga terug, ververs de pagina en probeer het nog eens.</p>
      <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
        <x-button-link href="{{ url()->previous('/') }}">Terug</x-button-link>
      </div>
    </div>
  </section>
</x-layouts.site>
