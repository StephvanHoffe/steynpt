@props(['intake', 'plans', 'coachingStatus'])
@php
    $types = [
        ['type' => 'training', 'label' => 'Trainingsschema', 'icon' => 'Dumbbell'],
        ['type' => 'voeding', 'label' => 'Voedingsschema', 'icon' => 'Utensils'],
    ];
@endphp
<section class="card p-6 sm:p-8" aria-labelledby="plans-title">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h2 id="plans-title" class="display flex items-center gap-2 text-3xl"><x-icon name="ClipboardList" class="size-7" /> Mijn schema's</h2>
    @if ($intake)
      <a href="/account/intake" class="text-sm font-semibold underline decoration-accent underline-offset-4">Intake bijwerken</a>
    @endif
  </div>

  @if (! $intake)
    <div class="mt-5 rounded-xl bg-accent-tint p-5">
      <p class="font-semibold">Vul je intake in voor je persoonlijke schema</p>
      <p class="mt-1 text-sm text-ink/80">Vertel over je doel, hoe vaak je traint en wat je wel en niet eet (zoals allergieën). Daarmee maken we je trainings- en voedingsschema op maat, gecontroleerd door Steyn.</p>
      <a href="/account/intake" class="btn btn-primary btn-sm mt-4">Intake invullen</a>
    </div>
  @else
    <ul class="mt-5 grid gap-3 sm:grid-cols-2">
      @foreach ($types as $t)
        @php
            $type = $t['type'];
            $published = $plans->first(fn ($p) => $p->type === $type && $p->status === 'gepubliceerd');
            $pending = $plans->contains(fn ($p) => $p->type === $type && in_array($p->status, ['genereren', 'concept', 'fout'], true));
            // Een ingepland schema blijft verborgen tot de startdag; de klant ziet alleen wanneer het komt.
            $scheduled = $plans->first(fn ($p) => $p->type === $type && $p->status === 'gepland' && $p->starts_on);
            $wanted = in_array($type, $intake['wants'] ?? [], true);
            $status = match (true) {
                (bool) $scheduled => 'Je nieuwe schema staat klaar vanaf '.\App\View\Fmt::dayMonth($scheduled->starts_on.'T12:00:00Z').'.',
                (bool) $published => $pending ? 'Er komt binnenkort een vernieuwde versie.' : 'Klaar sinds '.($published->published_at ? \App\View\Fmt::dayMonth($published->published_at) : 'kort').'.',
                $pending => 'Wordt gemaakt en daarna door Steyn gecontroleerd.',
                ! $wanted => 'Niet aangevraagd in je intake.',
                in_array($coachingStatus, ['geen', 'gestopt'], true) => 'Start online coaching, dan maken we je schema.',
                default => 'Steyn maakt je schema binnenkort.',
            };
        @endphp
        <li class="flex flex-col rounded-xl border p-4 {{ $published ? 'border-ink' : 'border-line' }}">
          <p class="flex items-center gap-2 font-semibold"><x-icon :name="$t['icon']" class="size-4 text-accent" /> {{ $t['label'] }}</p>
          <p class="mt-1 flex-1 text-sm text-muted">@if (! $published && ($pending || $scheduled))<x-icon name="Hourglass" class="mr-1 inline size-3.5" />@endif{{ $status }}</p>
          @if ($published)
            <a href="/account/schema/{{ $published->id }}" class="btn btn-primary btn-sm mt-3 self-start">Bekijk schema</a>
          @endif
          @if (! $published && ! $wanted)
            <a href="/account/intake" class="mt-3 text-sm font-semibold underline decoration-accent underline-offset-4">Toevoegen</a>
          @endif
        </li>
      @endforeach
    </ul>
  @endif
</section>
