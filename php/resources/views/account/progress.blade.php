<x-layouts.account title="Mijn voortgang">
  <div class="container-site max-w-5xl py-10 lg:py-14">
    <p class="eyebrow text-accent">Mijn omgeving</p>
    <h1 class="display display-lg mt-3">Mijn voortgang</h1>
    <p class="lead mt-3 text-muted">Alle metingen die Steyn met je heeft gedaan, met per onderdeel het verloop sinds je eerste meting.</p>
    <div class="mt-10">
      @if ($rows->isEmpty())
        <p class="rounded-lg bg-surface p-5 text-sm">Er zijn nog geen metingen. <a href="/account/agenda?type=meting" class="font-semibold underline decoration-accent underline-offset-4">Plan een meting</a></p>
      @else
        <x-progress.overview :rows="$rows" :table="true" />
      @endif
    </div>
    <a href="/account" class="mt-10 inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink"><x-icon name="ArrowLeft" class="size-4" /> Terug naar mijn omgeving</a>
  </div>
</x-layouts.account>
