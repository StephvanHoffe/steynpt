@props(['label', 'unit', 'points'])
{{--
  Lijngrafiek voor één meetwaarde (één reeks, één as). Kleuren volgen de ontwerptokens: lijn en punten in accent,
  tekst in ink/muted, raster als haarlijn. De teksten worden hier gemaakt; de geometrie rekent Alpine uit op de
  werkelijke breedte (resources/js/components/progress-chart.js), zodat tekst altijd op ware grootte blijft.
--}}
@php
    use App\Site\Locale;
    use App\Support\Progress;
    use App\View\Fmt;

    // $label komt al vertaald binnen (x-progress.overview); getallen en datums in de taal van de pagina.
    $lang = Locale::current();
    $values = array_column($points, 'value');
    $first = $points[0];
    $last = $points[count($points) - 1];
    $chart = [
        'points' => array_map(fn ($p) => [
            't' => $p['date']->getTimestamp() * 1000,
            'value' => (float) $p['value'],
            'label' => Progress::formatNumber($p['value'], 1, $lang),
            'short' => Fmt::shortDate($p['date']),
            'long' => Fmt::date($p['date']),
        ], $points),
        'ticks' => array_map(fn ($t) => ['value' => (float) $t, 'label' => Progress::formatNumber($t, 1, $lang)], Progress::niceTicks(min($values), max($values))),
        'desc' => __(':label: :count metingen, van :from :unit op :fromDate naar :to :unit op :toDate. Gebruik de pijltjestoetsen om metingen te bekijken.', [
            'label' => $label,
            'count' => count($points),
            'from' => Progress::formatNumber($first['value'], 1, $lang),
            'fromDate' => Fmt::date($first['date']),
            'to' => Progress::formatNumber($last['value'], 1, $lang),
            'toDate' => Fmt::date($last['date']),
            'unit' => $unit,
        ]),
    ];
    $id = 'grafiek-'.\Illuminate\Support\Str::random(8);
@endphp
<figure class="relative" x-data="progressChart(@js($chart), @js($id))">
  <figcaption id="{{ $id }}" class="flex items-baseline justify-between gap-3">
    <span class="font-semibold">{{ $label }}</span>
    <span class="text-xs text-muted">{{ $unit }}</span>
  </figcaption>
  <svg viewBox="0 0 560 210" :view-box.camel="`0 0 ${W} 210`" class="mt-2 block h-auto w-full touch-pan-y outline-none focus-visible:ring-2 focus-visible:ring-accent"
    role="img" aria-labelledby="{{ $id }}" aria-describedby="{{ $id }}-desc" tabindex="0"
    @pointermove="move($event)" @pointerleave="active = null" @focus="active = points.length - 1" @blur="active = null" @keydown="key($event)"
    x-html="svg()"><desc id="{{ $id }}-desc">{{ $chart['desc'] }}</desc></svg>
  <div x-show="active !== null" x-cloak class="pointer-events-none absolute top-8 z-10 -translate-x-1/2 rounded-md border border-line bg-white px-3 py-2 text-sm shadow-md"
    :style="active !== null && { left: tipLeft() + '%' }" role="status">
    <span class="block font-semibold text-ink" x-text="active !== null ? points[active].label + ' {{ $unit }}' : ''"></span>
    <span class="block text-xs text-muted" x-text="active !== null ? points[active].long : ''"></span>
  </div>
</figure>
