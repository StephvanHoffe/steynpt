@php
    use App\Services\AdminFormat;
@endphp
<x-layouts.admin title="Website-teksten">
  <x-admin.page>
    <div class="max-w-5xl">
      <x-admin.page-header title="Website-teksten"
        description="Pas de teksten aan die bezoekers en klanten op de website zien. Opslaan is meteen zichtbaar; wat je niet aanpast blijft de standaardtekst." />

      @foreach ($groups as $group)
        <section class="mb-8">
          <h2 class="text-xs font-semibold uppercase tracking-[0.12em] text-muted">{{ $group['title'] }}</h2>
          @if ($group['intro'])<p class="mt-1 text-sm text-muted">{{ $group['intro'] }}</p>@endif
          <ul class="mt-3 grid gap-2 md:grid-cols-2">
            @foreach ($group['pages'] as $page)
              @php $stat = $stats[$page['slug']] ?? null; @endphp
              <li class="card relative flex items-center gap-4 p-4 transition-colors hover:border-ink/40">
                <div class="min-w-0 flex-1">
                  <a href="/admin/teksten/{{ $page['slug'] }}" class="font-semibold after:absolute after:inset-0 after:rounded-xl">{{ $page['title'] }}</a>
                  <p class="mt-0.5 text-sm text-muted">{{ $page['description'] }}</p>
                  <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                    @if ($stat)
                      <span class="rounded-full bg-accent-tint px-2 py-0.5 font-semibold text-accent">{{ $stat['count'] === 1 ? '1 tekst aangepast' : $stat['count'].' teksten aangepast' }}</span>
                      <span class="text-muted">Laatst op {{ $stat['last'] ? AdminFormat::dayMonth($stat['last']) : '' }}{{ $stat['by'] ? ' door '.$stat['by'] : '' }}</span>
                    @else
                      <span class="rounded-full bg-surface px-2 py-0.5 font-medium text-muted">Standaardteksten</span>
                    @endif
                    @if ($page['path'])
                      <a href="{{ $page['path'] }}" target="_blank" rel="noopener noreferrer"
                        class="relative z-10 inline-flex items-center gap-1 font-medium text-muted underline-offset-2 hover:text-ink hover:underline">
                        {{ $page['path'] }} <x-icon name="ExternalLink" class="size-3" />
                        <span class="sr-only">(bekijk op de website, opent in een nieuw tabblad)</span>
                      </a>
                    @endif
                  </p>
                  @php $enStat = $stats[$englishPrefix.$page['slug']] ?? null; @endphp
                  <p class="mt-2 text-xs">
                    <a href="/admin/teksten/{{ $englishPrefix.$page['slug'] }}" class="relative z-10 inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-2.5 py-1 font-semibold text-ink hover:border-ink/40">
                      <x-icon name="Languages" class="size-3.5" /> Engelse versie
                      <span class="font-normal text-muted">{{ $enStat ? ($enStat['count'] === 1 ? '· 1 tekst aangepast' : '· '.$enStat['count'].' teksten aangepast') : '· standaard' }}</span>
                    </a>
                  </p>
                </div>
                <x-icon name="ChevronRight" class="size-5 shrink-0 text-muted" />
              </li>
            @endforeach
          </ul>
        </section>
      @endforeach

      <section class="card mt-8 p-5 text-sm sm:p-6">
        <h2 class="font-semibold">Handig om te weten</h2>
        <ul class="mt-3 grid gap-2 text-muted">
          <li>
            In titels zet je <span class="font-medium text-ink">*sterretjes*</span> om woorden die de petrolkleur moeten krijgen, zoals
            <span class="font-medium text-ink">Voeding die *werkt* voor jou</span>. Met Enter begin je een nieuwe regel.
          </li>
          <li>In lange teksten begint een lege regel een nieuwe alinea. In opsommingen staat elk punt op een eigen regel.</li>
          <li>
            Codes tussen accolades, zoals <span class="font-medium text-ink">{ademprijs}</span>, worden automatisch ingevuld. Verander je de prijs onder
            &lsquo;Prijzen en pakketten&rsquo;, dan klopt hij meteen overal.
          </li>
          <li>Spijt van een wijziging? Met &lsquo;Standaardtekst&rsquo; bij een veld zet je de oorspronkelijke tekst terug.</li>
        </ul>
      </section>
    </div>
  </x-admin.page>
</x-layouts.admin>
