@php
    use App\Services\AdminFormat;
    use App\Services\AdminLabels;
    use App\Site\Texts;
    use App\Support\Agenda;

    $nextLabel = fn ($at) => Agenda::formatDayShort($at).', '.Agenda::formatTime($at);
@endphp
<x-layouts.admin title="Leden">
  <x-admin.page>
    <x-admin.page-header title="Leden" :description="$total.' '.($total === 1 ? 'lid' : 'leden')" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <nav aria-label="Filter op coaching" class="flex flex-wrap gap-1.5">
        @foreach ($filters as $f)
          @php $current = $f['id'] === $status; @endphp
          <a href="{{ $f['href'] }}" @if ($current) aria-current="page" @endif
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium {{ $current ? 'border-ink bg-ink text-white' : 'border-line bg-white hover:border-ink' }}">
            {{ $f['id'] === 'alle' ? 'Alle' : AdminLabels::coaching($f['id']) }}
            <span class="tabular-nums {{ $current ? 'text-white/70' : 'text-muted' }}">{{ $f['count'] }}</span>
          </a>
        @endforeach
      </nav>
      <form action="/admin/leden" method="get" class="relative w-full sm:w-72">
        @if ($status !== 'alle')<input type="hidden" name="status" value="{{ $status }}">@endif
        <x-icon name="Search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <label for="zoek" class="sr-only">Zoek op naam, e-mail of telefoon</label>
        <input id="zoek" name="q" value="{{ $q }}" placeholder="Zoek op naam, e-mail of telefoon" class="input h-10 pl-9">
      </form>
    </div>

    @if ($members->isEmpty())
      <x-admin.empty-state>{{ $q !== '' ? "Geen leden gevonden voor “{$q}”." : 'Geen leden in deze groep.' }}</x-admin.empty-state>
    @else
      <div class="overflow-hidden rounded-xl border border-line bg-white">
        {{-- Tabel op grotere schermen --}}
        <table class="hidden w-full text-left text-sm md:table">
          <caption class="sr-only">Leden</caption>
          <thead class="border-b border-line bg-[#f6f7f8] text-xs uppercase tracking-wide text-muted">
            <tr>
              <th class="px-4 py-2.5 font-semibold">Naam</th>
              <th class="px-4 py-2.5 font-semibold">Coaching</th>
              <th class="px-4 py-2.5 font-semibold">Volgende afspraak</th>
              <th class="px-4 py-2.5 font-semibold">Laatste check-in</th>
              <th class="px-4 py-2.5 font-semibold">Lid sinds</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            @foreach ($members as $m)
              @php
                  $nextAt = $nextBy[$m->id] ?? null;
                  $planName = $m->plan ? Texts::onlinePlanName($m->plan) : null;
                  $week = $checkBy[$m->id] ?? null;
              @endphp
              <tr class="relative hover:bg-surface">
                <td class="px-4 py-3">
                  <a href="/admin/leden/{{ $m->id }}" class="font-semibold after:absolute after:inset-0">{{ $m->first_name }} {{ $m->last_name }}</a>
                  <span class="block text-muted">{{ $m->email }}{{ $m->phone ? ' · '.$m->phone : '' }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="flex flex-wrap items-center gap-2">
                    <x-admin.coaching-badge :status="$m->coaching_status" />
                    @if ($planName)<span class="text-muted">{{ $planName }}</span>@endif
                  </span>
                </td>
                <td class="px-4 py-3 tabular-nums">@if ($nextAt){{ $nextLabel($nextAt) }}@else<span class="text-muted">–</span>@endif</td>
                <td class="px-4 py-3 tabular-nums">@if ($week){{ preg_replace('/^(\d{4})-W(\d+)$/', 'week $2', $week) }}@else<span class="text-muted">–</span>@endif</td>
                <td class="px-4 py-3 text-muted">{{ AdminFormat::dayMonthShortYear($m->created_at) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>

        {{-- Kaarten op de telefoon --}}
        <ul class="divide-y divide-line md:hidden">
          @foreach ($members as $m)
            @php $nextAt = $nextBy[$m->id] ?? null; @endphp
            <li>
              <a href="/admin/leden/{{ $m->id }}" class="block px-4 py-3 hover:bg-surface">
                <span class="flex items-center justify-between gap-3">
                  <span class="font-semibold">{{ $m->first_name }} {{ $m->last_name }}</span>
                  <x-admin.coaching-badge :status="$m->coaching_status" />
                </span>
                <span class="mt-0.5 block truncate text-sm text-muted">{{ $m->email }}</span>
                @if ($nextAt)
                  <span class="mt-0.5 block text-sm">Volgende afspraak: {{ $nextLabel($nextAt) }}</span>
                @endif
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    @endif
  </x-admin.page>
</x-layouts.admin>
