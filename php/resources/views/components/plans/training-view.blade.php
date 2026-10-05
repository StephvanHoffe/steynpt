@props(['plan'])
{{-- Trainingsschema zoals de klant het ziet. Ook gebruikt als voorbeeld in de editor en in het beheer (oude versies). --}}
@php
    $n = \App\Support\Js::numberToString(...);
    $filled = fn ($v) => $v !== '' && $v !== null;
@endphp
<article class="space-y-6">
  <header>
    <h2 class="display text-3xl sm:text-4xl">{{ $plan['title'] }}</h2>
    <p class="mt-2 flex flex-wrap gap-2 text-sm">
      <span class="rounded-full bg-accent-tint px-3 py-1 font-semibold">{{ count($plan['days']) }}× per week</span>
      <span class="rounded-full bg-accent-tint px-3 py-1 font-semibold">{{ $n($plan['durationWeeks']) }} weken</span>
    </p>
    @if ($filled($plan['summary']))
      <p class="mt-4 whitespace-pre-line leading-relaxed text-ink/85">{{ $plan['summary'] }}</p>
    @endif
  </header>

  @foreach ($plan['days'] as $day)
    <section class="card break-inside-avoid overflow-hidden">
      <div class="border-b border-line bg-surface px-5 py-4">
        <h3 class="flex items-center gap-2 text-lg font-semibold">
          <x-icon name="Dumbbell" class="size-5 text-accent" /> {{ $day['name'] }}
        </h3>
        @if ($filled($day['focus']))
          <p class="mt-0.5 text-sm text-muted">{{ $day['focus'] }}</p>
        @endif
      </div>
      <div class="space-y-4 p-5">
        @if ($filled($day['warmup']))
          <p class="text-sm"><strong>Warming-up:</strong> {{ $day['warmup'] }}</p>
        @endif
        <div class="relative overflow-x-auto">
          <table class="w-full min-w-[520px] text-left text-sm">
            <thead class="text-xs uppercase tracking-wider text-muted">
              <tr class="border-b border-line">
                <th class="py-2 pr-3 font-semibold">Oefening</th>
                <th class="py-2 pr-3 font-semibold">Sets</th>
                <th class="py-2 pr-3 font-semibold">Herhalingen</th>
                <th class="py-2 pr-3 font-semibold">Rust</th>
                <th class="py-2 font-semibold">Toelichting</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($day['exercises'] as $ex)
                <tr class="border-b border-line/70 align-top last:border-0">
                  <td class="py-2.5 pr-3 font-semibold">{{ $ex['name'] }}</td>
                  <td class="py-2.5 pr-3">{{ $ex['sets'] }}</td>
                  <td class="py-2.5 pr-3">{{ $ex['reps'] }}</td>
                  <td class="py-2.5 pr-3 whitespace-nowrap">{{ $ex['rest'] }}</td>
                  <td class="py-2.5 text-muted">{{ $ex['notes'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @if ($filled($day['cooldown']))
          <p class="text-sm"><strong>Cooling-down:</strong> {{ $day['cooldown'] }}</p>
        @endif
      </div>
    </section>
  @endforeach

  @if ($filled($plan['progression']))
    <section class="card break-inside-avoid p-5">
      <h3 class="flex items-center gap-2 font-semibold">
        <x-icon name="TrendingUp" class="size-5 text-accent" /> Progressie
      </h3>
      <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">{{ $plan['progression'] }}</p>
    </section>
  @endif
  <x-plans.tip-list :tips="$plan['tips']" />
</article>
