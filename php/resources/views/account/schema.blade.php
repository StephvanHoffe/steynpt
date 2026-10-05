{{-- Schema van de klant (alleen gepubliceerd), met een knop om te printen of als pdf te bewaren. --}}
<x-layouts.account title="Mijn schema">
  <div class="container-site max-w-4xl py-10 lg:py-14">
    <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
      <a href="/account" class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <x-icon name="ArrowLeft" class="size-4" /> Terug naar mijn omgeving
      </a>
      <x-plans.print-button />
    </div>
    <p class="mt-6 text-sm text-muted">
      {{ $plan->type === 'training' ? 'Trainingsschema' : 'Voedingsschema' }} · gecontroleerd door Steyn{{ $plan->published_at ? ' · '.\App\View\Fmt::date($plan->published_at) : '' }}
    </p>
    <div class="mt-3">
      @if ($plan->type === 'training')
        <x-plans.training-view :plan="$content" />
      @else
        <x-plans.nutrition-view :plan="$content" />
      @endif
    </div>
    <p class="mt-10 rounded-xl bg-accent-tint p-5 text-sm print:hidden">
      Vragen over je schema of loopt iets niet lekker? Laat het weten in je wekelijkse check-in, dan stuurt Steyn bij.
    </p>
  </div>
</x-layouts.account>
