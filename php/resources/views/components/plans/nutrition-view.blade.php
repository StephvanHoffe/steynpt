@props(['plan'])
{{-- Voedingsschema zoals de klant het ziet. Ook gebruikt als voorbeeld in de editor en in het beheer (oude versies). --}}
@php
    $round = fn ($v) => \App\Support\Js::numberToString(\App\Support\Js::num(\App\Support\Js::round($v)));
    $targets = [
        ['label' => __('Energie per dag'), 'value' => $round($plan['targets']['calories']).' kcal'],
        ['label' => __('Eiwit per dag'), 'value' => $round($plan['targets']['protein']).' g'],
        ['label' => __('Koolhydraten per dag'), 'value' => $round($plan['targets']['carbs']).' g'],
        ['label' => __('Vet per dag'), 'value' => $round($plan['targets']['fat']).' g'],
        ['label' => __('Water per dag'), 'value' => $plan['targets']['water']],
    ];
@endphp
<article class="space-y-6">
  <header>
    <h2 class="display text-3xl sm:text-4xl">{{ $plan['title'] }}</h2>
    @if ($plan['summary'] !== '')
      <p class="mt-4 whitespace-pre-line leading-relaxed text-ink/85">{{ $plan['summary'] }}</p>
    @endif
  </header>

  <dl class="grid grid-cols-2 gap-3 sm:grid-cols-5">
    @foreach ($targets as $t)
      <div class="rounded-xl bg-accent-tint p-4">
        <dt class="text-xs text-muted">{{ $t['label'] }}</dt>
        <dd class="mt-1 font-semibold">{{ $t['value'] }}</dd>
      </div>
    @endforeach
  </dl>

  @if (count($plan['avoid']) > 0)
    <section class="flex gap-3 rounded-xl border border-accent/30 bg-white p-5 break-inside-avoid">
      <x-icon name="Ban" class="size-5 shrink-0 text-accent" />
      <div>
        <h3 class="font-semibold">{{ __('Vermijden') }}</h3>
        <ul class="mt-1 list-disc pl-5 text-sm">
          @foreach ($plan['avoid'] as $a)
            <li>{{ $a }}</li>
          @endforeach
        </ul>
      </div>
    </section>
  @endif

  @foreach ($plan['meals'] as $meal)
    <section class="card break-inside-avoid overflow-hidden">
      <div class="flex items-center justify-between gap-3 border-b border-line bg-surface px-5 py-4">
        <h3 class="flex items-center gap-2 text-lg font-semibold">
          <x-icon name="Utensils" class="size-5 text-accent" /> {{ $meal['name'] }}
        </h3>
        @if ($meal['time'] !== '')
          <span class="flex items-center gap-1.5 text-sm text-muted">
            <x-icon name="Clock" class="size-4" /> {{ $meal['time'] }}
          </span>
        @endif
      </div>
      <ul class="divide-y divide-line">
        @foreach ($meal['options'] as $j => $option)
          <li class="flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
            <div>
              <p class="font-semibold">@if (count($meal['options']) > 1)<span class="mr-2 text-xs font-bold uppercase tracking-wider text-accent">{{ __('Optie :n', ['n' => $j + 1]) }}</span>@endif{{ $option['title'] }}</p>
              <p class="mt-1 text-sm text-muted">{{ $option['ingredients'] }}</p>
            </div>
            <p class="shrink-0 text-sm font-semibold">± {{ $round($option['kcal']) }} kcal · {{ __(':n g eiwit', ['n' => $round($option['protein'])]) }}</p>
          </li>
        @endforeach
      </ul>
    </section>
  @endforeach
  <x-plans.tip-list :tips="$plan['tips']" />
</article>
