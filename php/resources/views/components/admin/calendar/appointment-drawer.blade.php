@props(['appointment', 'client', 'closeHref', 'warnings' => [], 'now'])
{{-- Paneel met alle gegevens van één afspraak, met annuleren en verplaatsen. Escape sluit het paneel. --}}
@php
    use App\Services\AdminCalendar;
    use App\Site\Texts;
    use App\Support\Agenda;

    $a = $appointment;
    $type = Agenda::getAppointmentType($a->type);
    $location = Agenda::getAgendaLocation($a->location);
    $color = AdminCalendar::typeColor($a->type);
    $upcoming = $a->status === 'gepland' && $a->ends_at->gt($now);
    $name = "{$client->first_name} {$client->last_name}";
    $minutes = $type['minutes'] ?? (int) round(($a->ends_at->getTimestamp() - $a->starts_at->getTimestamp()) / 60);
@endphp
<div class="fixed inset-0 z-50 flex items-end justify-end sm:items-stretch" role="dialog" aria-modal="true" aria-labelledby="afspraak-titel"
  x-data @keydown.escape.window="$refs.close.click()">
  <a href="{{ $closeHref }}" data-keep-scroll x-ref="close" class="absolute inset-0 bg-ink/30" aria-label="Sluiten" tabindex="-1"></a>
  <div class="relative flex max-h-[88dvh] w-full flex-col overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:max-h-none sm:max-w-md sm:rounded-none">
    <div class="h-1.5 shrink-0" style="background: {{ $color }}" aria-hidden="true"></div>
    <div class="flex items-start justify-between gap-3 border-b border-line px-6 py-5">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider" style="color: {{ $color }}">{{ $type['label'] ?? $a->type }}</p>
        <h2 id="afspraak-titel" class="display mt-1 text-2xl">{{ $name }}</h2>
        @if ($a->status === 'geannuleerd')
          <p class="mt-2 inline-flex rounded-full bg-danger/10 px-2.5 py-0.5 text-xs font-semibold text-danger">Geannuleerd door {{ $a->cancelled_by === 'klant' ? 'de klant' : 'jou' }}</p>
        @endif
      </div>
      <a href="{{ $closeHref }}" data-keep-scroll class="grid size-9 shrink-0 place-items-center rounded-lg text-muted hover:bg-surface hover:text-ink" aria-label="Sluiten">
        <x-icon name="X" class="size-5" />
      </a>
    </div>

    <dl class="grid gap-4 px-6 py-5 text-sm">
      <div class="flex gap-3">
        <dt><x-icon name="CalendarClock" class="size-5 text-muted" /><span class="sr-only">Wanneer</span></dt>
        <dd>
          <span class="block font-semibold first-letter:uppercase">{{ Agenda::formatDayLong($a->starts_at) }}</span>
          <span class="tabular-nums text-muted">{{ Agenda::formatTime($a->starts_at) }}–{{ Agenda::formatTime($a->ends_at) }} · {{ $minutes }} minuten</span>
        </dd>
      </div>
      <div class="flex gap-3">
        <dt><x-icon name="MapPin" class="size-5 text-muted" /><span class="sr-only">Waar</span></dt>
        <dd>
          <span class="block font-semibold">{{ $location['label'] ?? $a->location }}</span>
          @if ($location)<span class="text-muted">{{ $location['address'] }}</span>@endif
        </dd>
      </div>
      <div class="flex gap-3">
        <dt><x-icon name="UserRound" class="size-5 text-muted" /><span class="sr-only">Klant</span></dt>
        <dd class="grid gap-1">
          <a href="/admin/leden/{{ $client->id }}" class="font-semibold underline decoration-accent underline-offset-4">{{ $name }}</a>
          <span class="flex flex-wrap items-center gap-2">
            <x-admin.coaching-badge :status="$client->coaching_status" />
            @if ($client->plan)<span class="text-xs text-muted">{{ Texts::onlinePlanName($client->plan) }}</span>@endif
          </span>
          @if ($client->phone)
            <a href="tel:{{ preg_replace('/\s/', '', $client->phone) }}" class="inline-flex items-center gap-1.5 text-ink hover:underline">
              <x-icon name="Phone" class="size-3.5 text-muted" /> {{ $client->phone }}
            </a>
          @endif
          <a href="mailto:{{ $client->email }}" class="inline-flex items-center gap-1.5 break-all text-ink hover:underline">
            <x-icon name="Mail" class="size-3.5 shrink-0 text-muted" /> {{ $client->email }}
          </a>
        </dd>
      </div>
      @if ($a->note)
        <div class="flex gap-3">
          <dt><x-icon name="StickyNote" class="size-5 text-muted" /><span class="sr-only">Opmerking</span></dt>
          <dd class="whitespace-pre-line rounded-lg bg-surface px-3 py-2">{{ $a->note }}</dd>
        </div>
      @endif
    </dl>

    @if ($warnings)
      <ul class="mx-6 mb-5 grid gap-1.5 rounded-lg border border-[#b45309]/30 bg-[#fdf6ec] p-3 text-sm">
        @foreach ($warnings as $w)
          <li class="flex gap-2"><x-icon name="AlertTriangle" class="mt-0.5 size-4 shrink-0 text-[#b45309]" /> {{ $w }}</li>
        @endforeach
      </ul>
    @endif

    <div class="mt-auto grid gap-2 border-t border-line px-6 py-5">
      @if ($upcoming)
        <a href="/admin/agenda/nieuw?verplaats={{ $a->id }}" class="btn btn-primary">
          <x-icon name="CalendarClock" class="size-4" /> Verplaatsen
        </a>
      @endif
      <a href="/admin/agenda/nieuw?lid={{ $client->id }}" class="btn btn-outline">
        <x-icon name="CalendarPlus" class="size-4" /> Nieuwe afspraak met {{ $client->first_name }}
      </a>
      @if ($upcoming)
        <details class="group rounded-lg border border-line">
          <summary class="cursor-pointer list-none px-4 py-2.5 text-center text-sm font-semibold text-danger [&::-webkit-details-marker]:hidden">Afspraak annuleren…</summary>
          <div class="border-t border-line p-4 text-sm">
            <p class="text-muted">
              {{ $client->first_name }} ziet de afspraak daarna als geannuleerd in Mijn omgeving. Er gaat geen e-mail uit, dus laat het {{ $client->first_name }} ook zelf weten.
            </p>
            <form method="post" action="/admin/agenda/annuleren" class="mt-3">
              @csrf
              <input type="hidden" name="id" value="{{ $a->id }}">
              <button type="submit" class="btn btn-sm w-full border border-danger bg-danger text-white hover:bg-danger/90">Ja, annuleer deze afspraak</button>
            </form>
          </div>
        </details>
      @endif
    </div>
  </div>
</div>
