@props(['intake', 'updatedAt' => null])
{{-- Intake van een lid voor Steyn, met de belangrijkste aandachtspunten uitgelicht.
     Gebruik: <x-plans.intake-panel :intake="$intakeData" :updated-at="$intake->updated_at" /> met een gevalideerde intake (App\Support\Intake::validate). --}}
@php
    $rows = \App\Support\Intake::intakeSummary($intake);
    $t = \App\Support\Intake::estimateTargets($intake);
    $n = \App\Support\Js::numberToString(...);
    $sections = [['algemeen', 'Algemeen'], ['training', 'Training'], ['voeding', 'Voeding']];
    $important = ['Allergieën en intoleranties', 'Medische aandachtspunten', 'Blessures of beperkingen', 'Eetstijl'];
@endphp
<div class="grid gap-5">
  @if ($updatedAt)
    <p class="text-xs text-muted">Laatst bijgewerkt {{ \App\Services\Plans\PlanView::dateTime($updatedAt) }}</p>
  @endif
  @foreach ($sections as [$id, $title])
    <section>
      <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-accent">{{ $title }}</h3>
      <dl class="mt-2 divide-y divide-line text-sm">
        @foreach ($rows as $r)
          @continue($r['section'] !== $id)
          @php $marked = in_array($r['label'], $important, true); @endphp
          <div class="grid grid-cols-[9rem_1fr] gap-3 py-2 {{ $marked ? 'rounded-md bg-accent-tint px-2' : '' }}">
            <dt class="text-muted">@if ($marked)<x-icon name="AlertTriangle" class="mr-1 inline size-3.5 text-accent" />@endif{{ $r['label'] }}</dt>
            <dd class="whitespace-pre-line font-medium">{{ $r['value'] }}</dd>
          </div>
        @endforeach
      </dl>
    </section>
  @endforeach
  <section class="rounded-xl bg-surface p-4 text-sm">
    <h3 class="font-semibold">Berekende richtwaarden</h3>
    <p class="mt-1 text-muted">
      Rust {{ $n($t['bmr']) }} kcal · onderhoud {{ $n($t['maintenance']) }} kcal · doel {{ $n($t['calories']) }} kcal · eiwit {{ $n($t['protein']) }} g · koolhydraten {{ $n($t['carbs']) }} g · vet {{ $n($t['fat']) }} g
    </p>
  </section>
</div>
