@php
    use App\Support\Agenda;

    $rules = Agenda::BOOKING_RULES;
@endphp
<x-layouts.admin title="Agenda-instellingen">
  <x-admin.page>
    <x-admin.page-header title="Instellingen" description="Wanneer klanten kunnen boeken, je vrije dagen en de koppeling met je eigen agenda." />

    <div class="grid gap-6 xl:grid-cols-[1.25fr_1fr]">
      <section aria-labelledby="beschikbaar" class="card p-5 sm:p-6">
        <h2 id="beschikbaar" class="text-lg font-semibold">Beschikbaarheid per week</h2>
        <p class="mt-1 text-sm text-muted">Klanten kunnen alleen binnen deze tijden boeken, minimaal {{ $rules['minNoticeHours'] }} uur van tevoren en maximaal {{ $rules['horizonDays'] / 7 }} weken vooruit.</p>
        <div class="mt-5">
          <div class="overflow-hidden rounded-lg border border-line">
            <table class="w-full text-sm">
              <caption class="sr-only">Beschikbaarheid per weekdag</caption>
              <tbody class="divide-y divide-line">
                @foreach (Agenda::WEEKDAYS as $i => $name)
                  @php $dayWindows = $windows->where('weekday', $i + 1); @endphp
                  <tr class="align-top">
                    <th scope="row" class="w-28 bg-[#f6f7f8] px-3 py-2.5 text-left font-medium first-letter:uppercase">{{ $name }}</th>
                    <td class="px-3 py-2">
                      @if ($dayWindows->isEmpty())
                        <span class="inline-block py-1 text-muted">Niet beschikbaar</span>
                      @else
                        <ul class="flex flex-wrap gap-2">
                          @foreach ($dayWindows as $w)
                            <li class="inline-flex items-center gap-1 rounded-full border border-line bg-white py-0.5 pl-3 pr-1">
                              <span class="tabular-nums">{{ $w->start_time }}–{{ $w->end_time }}</span>
                              <span class="text-muted">· {{ Agenda::getAgendaLocation($w->location)['label'] ?? $w->location }}</span>
                              <form method="post" action="/admin/agenda/instellingen/beschikbaarheid/verwijderen">
                                @csrf
                                <input type="hidden" name="id" value="{{ $w->id }}">
                                <button type="submit" aria-label="Verwijder {{ $name }} {{ $w->start_time }}–{{ $w->end_time }}"
                                  class="grid size-7 place-items-center rounded-full text-muted hover:bg-danger/10 hover:text-danger">
                                  <x-icon name="Trash2" class="size-3.5" />
                                </button>
                              </form>
                            </li>
                          @endforeach
                        </ul>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @if ($windows->isEmpty())
            <p class="mt-3 text-sm font-medium text-danger">Er zijn nog geen tijden ingesteld: klanten kunnen nog niets boeken.</p>
          @endif
          <h3 class="mb-3 mt-6 text-sm font-semibold">Tijden toevoegen</h3>
          <x-agenda.admin-availability-form />
        </div>
      </section>

      <div class="grid content-start gap-6">
        <section aria-labelledby="geblokkeerd" class="card p-5 sm:p-6">
          <h2 id="geblokkeerd" class="text-lg font-semibold">Vrije dagen en vakanties</h2>
          <p class="mt-1 text-sm text-muted">Op deze dagen kunnen klanten niet boeken. Bestaande afspraken blijven staan.</p>
          <div class="mt-5">
            <ul class="divide-y divide-line rounded-lg border border-line text-sm">
              @if ($blocks->isEmpty())
                <li class="p-3 text-muted">Geen vrije dagen gepland.</li>
              @endif
              @foreach ($blocks as $b)
                @php $lastDay = $b->ends_at->subMinute(); @endphp
                <li class="flex items-center justify-between gap-3 px-3 py-2">
                  <span>
                    <span class="font-medium first-letter:uppercase">{{ Agenda::formatDayLong($b->starts_at) }}</span>@if (Agenda::zonedParts($b->starts_at)['day'] !== Agenda::zonedParts($lastDay)['day']) t/m {{ Agenda::formatDayLong($lastDay) }}@endif
                    @if ($b->reason)<span class="text-muted"> · {{ $b->reason }}</span>@endif
                  </span>
                  <form method="post" action="/admin/agenda/instellingen/blokkades/verwijderen">
                    @csrf
                    <input type="hidden" name="id" value="{{ $b->id }}">
                    <button type="submit" aria-label="Verwijder blokkade" class="grid size-8 place-items-center rounded-md text-muted hover:bg-danger/10 hover:text-danger">
                      <x-icon name="Trash2" class="size-4" />
                    </button>
                  </form>
                </li>
              @endforeach
            </ul>
            <div class="mt-5">
              <x-agenda.admin-block-form :today="$today" />
            </div>
          </div>
        </section>

        <section aria-labelledby="ical" class="card p-5 sm:p-6">
          <h2 id="ical" class="text-lg font-semibold">Koppelen met je eigen agenda</h2>
          <p class="mt-1 text-sm text-muted">Abonneer je op deze link; nieuwe en geannuleerde afspraken verschijnen dan vanzelf in je agenda.</p>
          <div class="mt-5">
            <x-agenda.admin-copy-field :value="$feedUrl" label="iCal-link" />
            <div class="mt-5 grid gap-4 text-sm">
              <div>
                <p class="font-semibold">Google Agenda</p>
                <p class="text-muted">
                  Ga op een computer naar calendar.google.com › Andere agenda's › + › Via URL, plak de link en kies Agenda toevoegen. Google ververst
                  geabonneerde agenda's zelf, meestal enkele keren per dag.
                </p>
              </div>
              <div>
                <p class="font-semibold">Apple Agenda (iPhone, iPad, Mac)</p>
                <p class="text-muted">
                  Open
                  <a href="{{ $webcalUrl }}" class="font-medium text-ink underline decoration-accent underline-offset-4">deze link op je iPhone of Mac</a>
                  en kies Abonneer. Zet bij Vernieuw automatisch bijvoorbeeld &ldquo;Elk uur&rdquo;.
                </p>
              </div>
              <p class="rounded-lg bg-surface p-3 text-xs text-muted">
                De link bevat namen en telefoonnummers van klanten. Deel hem met niemand. Uitgelekt? Maak een nieuwe link; de oude werkt dan direct niet meer.
              </p>
              <form method="post" action="/admin/agenda/instellingen/ical-link">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline">Nieuwe link maken</button>
              </form>
            </div>
          </div>
        </section>
      </div>
    </div>
  </x-admin.page>
</x-layouts.admin>
