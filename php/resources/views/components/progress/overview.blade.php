@props(['rows', 'charts' => 'all', 'table' => false])
{{-- Stat tiles + grafieken (en optioneel de volledige tabel) van de metingen van één klant. --}}
@php
    use App\Support\Progress;
    use App\View\Fmt;

    $data = collect($rows)->map(fn ($m) => [
        'id' => $m->id,
        'measuredAt' => $m->measured_at,
        'weight' => $m->weight,
        'bodyFat' => $m->body_fat,
        'muscleMass' => $m->muscle_mass,
        'waist' => $m->waist,
        'hip' => $m->hip,
        'chest' => $m->chest,
        'arm' => $m->arm,
        'thigh' => $m->thigh,
        'note' => $m->note,
    ])->all();
    $summary = Progress::progressSummary($data);
    $withTrend = array_values(array_filter($summary, fn ($s) => count($s['points']) > 1));
    $shown = $charts === 'main' ? array_slice($withTrend, 0, 2) : $withTrend;
    $sorted = collect($data)->sortByDesc(fn ($r) => $r['measuredAt']->getTimestamp())->values()->all();
    $columns = array_values(array_filter(Progress::MEASUREMENT_FIELDS, fn ($f) => collect($data)->contains(fn ($r) => $r[$f['key']] !== null)));
@endphp
<div class="grid gap-6">
  <dl class="grid grid-cols-2 gap-3 {{ $charts === 'main' ? '' : 'sm:grid-cols-4' }}">
    @foreach (array_slice($summary, 0, 4) as $s)
      @php $icon = $s['change'] === null || $s['change'] == 0 ? 'Minus' : ($s['change'] < 0 ? 'ArrowDownRight' : 'ArrowUpRight'); @endphp
      <div class="rounded-lg border border-line p-4">
        <dt class="text-xs text-muted">{{ $s['label'] }}</dt>
        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ Progress::formatNumber($s['latest']['value']) }} <span class="text-sm font-normal text-muted">{{ $s['unit'] }}</span></dd>
        <dd class="mt-1 flex items-center gap-1 text-xs text-muted">
          @if ($s['change'] !== null)
            <x-icon :name="$icon" class="size-3.5" />{{ $s['change'] > 0 ? '+' : '' }}{{ Progress::formatNumber($s['change']) }} {{ $s['unit'] }} sinds {{ Fmt::shortDateNear($s['since']) }}
          @else
            Gemeten op {{ Fmt::shortDateNear($s['latest']['date']) }}
          @endif
        </dd>
      </div>
    @endforeach
  </dl>

  @if (count($shown) > 0)
    <div class="grid gap-6 md:grid-cols-2">
      @foreach ($shown as $s)
        <div class="rounded-lg border border-line p-4">
          <x-progress.chart :label="$s['label']" :unit="$s['unit']" :points="$s['points']" />
        </div>
      @endforeach
    </div>
  @endif

  @if ($table)
    <div class="relative overflow-x-auto rounded-lg border border-line">
      <table class="w-full min-w-[560px] text-left text-sm">
        <caption class="sr-only">Alle metingen</caption>
        <thead class="bg-surface text-xs uppercase tracking-wider text-muted">
          <tr>
            <th class="px-4 py-2.5 font-semibold">Datum</th>
            @foreach ($columns as $c)
              <th class="px-4 py-2.5 font-semibold">{{ $c['label'] }} <span class="normal-case">({{ $c['unit'] }})</span></th>
            @endforeach
            <th class="px-4 py-2.5 font-semibold">Notitie</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($sorted as $r)
            <tr class="border-t border-line align-top">
              <td class="whitespace-nowrap px-4 py-2.5 font-medium">{{ Fmt::shortDate($r['measuredAt'], true) }}</td>
              @foreach ($columns as $c)
                <td class="px-4 py-2.5 tabular-nums">{{ $r[$c['key']] !== null ? Progress::formatNumber($r[$c['key']]) : '–' }}</td>
              @endforeach
              <td class="px-4 py-2.5 text-muted">{{ $r['note'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
