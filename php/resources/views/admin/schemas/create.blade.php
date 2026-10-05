{{-- Nieuw schema maken: klant kiezen, startdatum, en dan AI-concept, verder met het huidige schema of leeg beginnen. --}}
@php
    use App\Services\AdminLabels;
    use App\Support\Plans\Pipeline;
    use App\View\Fmt;
    use App\View\PlanLabels;
@endphp
<x-layouts.admin :title="'Nieuw '.$section['one']">
  <x-admin.page>
    <x-admin.page-header :back="['href' => $section['href'], 'label' => $section['title']]" :title="'Nieuw '.$section['one']"
      description="Kies een klant, de startdatum en hoe je het schema wilt maken. De klant ziet het pas na jouw controle, en niet voor de startdatum." />
    <div class="max-w-xl">
      <x-plans.member-picker :action="$section['href'].'/nieuw'" :groups="$groups" :selected="$member?->id" />
    </div>
    @if ($lid && ! $member)
      <p class="mt-4 text-sm text-danger">Deze klant bestaat niet (meer).</p>
    @endif

    @if ($member)
      @php
          $row = $details['row'];
          $today = $details['today'];
      @endphp
      <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="grid min-w-0 grid-cols-1 content-start gap-5">
          {{-- Waar staat deze klant? --}}
          <section class="card p-5 sm:p-6" aria-label="Huidige situatie">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
              <a href="/admin/leden/{{ $member->id }}" class="text-lg font-semibold hover:underline">{{ $member->fullName() }}</a>
              @if ($row)
                <x-plans.stage-badge :stage="$row['stage']" :coaching-status="$member->coaching_status" />
              @endif
              @if ($details['planName'])
                <span class="text-sm text-muted">Online {{ $details['planName'] }}</span>
              @endif
            </div>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-muted">Huidig {{ $section['one'] }}</dt>
                <dd class="font-medium">
                  @if ($row['current'] ?? null)
                    <a href="{{ AdminLabels::planHref($type, $row['current']['id']) }}" class="underline decoration-accent underline-offset-4">{{ $row['current']['title'] ?: 'Bekijken' }}</a>
                    <span class="block font-normal text-muted">gepubliceerd {{ Fmt::dayMonth($row['current']['publishedAt']) }}</span>
                  @else
                    Nog geen schema
                  @endif
                </dd>
              </div>
              <div>
                <dt class="text-muted">Toe aan nieuw schema</dt>
                <dd class="font-medium">
                  @if ($row['currentDueOn'] ?? null)
                    <span class="first-letter:uppercase">{{ PlanLabels::formatPlanDayLong($row['currentDueOn']) }}</span>
                    <span class="block font-normal {{ $row['currentDueOn'] <= $today && $row['stage'] !== 'pauze' ? 'text-danger' : 'text-muted' }}">{{ Pipeline::relativeDay($today, $row['currentDueOn']) }}</span>
                  @else
                    Zo snel mogelijk
                  @endif
                </dd>
              </div>
            </dl>
            @if ($row['scheduled'] ?? null)
              <p class="mt-4 flex gap-2 rounded-lg bg-[#eef0ff] p-3 text-sm">
                <x-icon name="CalendarClock" class="size-4 shrink-0 text-[#3730a3]" />
                <span>
                  Er staat al een schema ingepland vanaf {{ PlanLabels::formatPlanDayLong($row['scheduled']['startsOn']) }}.
                  <a href="{{ AdminLabels::planHref($type, $row['scheduled']['id']) }}" class="font-semibold underline">Ingepland schema openen</a>. Een nieuw schema dat je inplant, vervangt die planning.
                </span>
              </p>
            @endif
            @if ($row['open'] ?? null)
              <p class="mt-4 flex gap-2 rounded-lg bg-accent-tint p-3 text-sm">
                <x-icon name="AlertTriangle" class="size-4 shrink-0 text-accent" />
                <span>
                  Er staat al een concept klaar ({{ mb_strtolower(Pipeline::STAGES[$row['stage']]['label']) }}).
                  <a href="{{ AdminLabels::planHref($type, $row['open']['id']) }}" class="font-semibold underline">Concept openen</a>. Een nieuw concept vervangt het bestaande.
                </span>
              </p>
            @endif
          </section>

          <x-plans.new-plan-form :user-id="$member->id" :type="$type" :today="$today" :default-start="$details['defaultStart']"
            :current-ends-on="$row['currentDueOn'] ?? null" :has-current="$details['hasCurrent']" :ai="$details['ai']" :english="$member->locale === 'en'" />
        </div>

        <aside class="card self-start p-6 xl:sticky xl:top-20" aria-labelledby="intake">
          <h2 id="intake" class="text-lg font-semibold">Intake</h2>
          <div class="mt-4">
            @if ($details['intake'])
              <x-plans.intake-panel :intake="$details['intake']" :updated-at="$details['intakeUpdatedAt']" />
            @else
              <p class="text-sm text-muted">Nog geen intake ingevuld.</p>
            @endif
          </div>
        </aside>
      </div>
    @endif
  </x-admin.page>
</x-layouts.admin>
