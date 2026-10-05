{{-- Controleren, bewerken en publiceren van één schema, met planning en eerdere versies ernaast. --}}
@php
    use App\Services\AdminLabels;
    use App\Services\Plans\PlanView;
    use App\Support\Plans\Pipeline;
    use App\View\PlanLabels;

    $hasIntake = $intake !== null;
@endphp
<x-layouts.admin :title="PlanLabels::TYPE_LABEL[$type]">
  <x-admin.page>
    @if ($plan->status === 'genereren' && ! $stuck)
      <x-plans.auto-refresh />
    @endif
    <x-admin.page-header :back="['href' => $section['href'], 'label' => $section['title']]">
      <x-slot:title>{{ PlanLabels::TYPE_LABEL[$type] }} · <a href="/admin/leden/{{ $member->id }}" class="hover:underline">{{ $member->first_name }} {{ $member->last_name }}</a></x-slot:title>
      <x-slot:description>
        <span class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
          <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $status['tone'] }}">{{ $status['label'] }}</span>
          @if ($edited)
            <span class="rounded-full bg-surface px-2.5 py-0.5 text-xs font-semibold text-ink">Aangepast door Steyn</span>
          @endif
          <span>Versie #{{ $plan->id }} · {{ $plan->source === 'ai' ? 'AI-concept ('.($plan->model ?? 'AI').')' : 'handmatig' }} · gemaakt {{ PlanView::dateTime($plan->created_at) }}{{ $plan->published_at ? ' · gepubliceerd '.PlanView::dateTime($plan->published_at) : '' }}{{ ! $live && $futureStart ? ' · start '.PlanLabels::formatPlanDay($futureStart) : '' }}</span>
          @if ($plan->instruction)
            <span>Instructie: &ldquo;{{ $plan->instruction }}&rdquo;</span>
          @endif
        </span>
      </x-slot:description>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
      <div class="grid min-w-0 grid-cols-1 content-start gap-6">
        @if ($plan->status === 'gepland' && $futureStart)
          <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#c7cbf5] bg-[#eef0ff] p-4 text-sm">
            <p class="flex gap-2">
              <x-icon name="CalendarClock" class="size-5 shrink-0 text-[#3730a3]" />
              <span>
                <strong class="font-semibold">Ingepland.</strong> De klant ziet dit schema vanaf {{ PlanLabels::formatPlanDayLong($futureStart) }} ({{ Pipeline::relativeDay($today, $futureStart) }}).{{ ($row['current'] ?? null) ? ' Tot dan blijft het huidige schema zichtbaar.' : '' }} Wijzigen kan nog.
              </span>
            </p>
            <form action="{{ AdminLabels::planHref($type, $plan->id) }}/terugzetten" method="post">
              @csrf
              <input type="hidden" name="planId" value="{{ $plan->id }}">
              <button type="submit" class="btn btn-sm btn-outline bg-white">Terugzetten naar concept</button>
            </form>
          </div>
        @endif

        @if ($intakeChanged)
          <p class="flex gap-2 rounded-xl border border-accent/30 bg-accent-tint p-4 text-sm">
            <x-icon name="AlertTriangle" class="size-5 shrink-0 text-accent" />
            De klant heeft de intake gewijzigd nadat dit concept is gemaakt. Controleer extra goed of laat een nieuw concept maken.
          </p>
        @endif

        @if ($plan->status === 'genereren' && ! $stuck)
          <div class="card flex items-center gap-4 p-8">
            <x-icon name="LoaderCircle" class="size-8 animate-spin text-accent" />
            <div>
              <p class="font-semibold">De AI maakt het concept…</p>
              <p class="text-sm text-muted">Dit duurt meestal één à twee minuten. De pagina ververst vanzelf.</p>
            </div>
          </div>
        @endif

        @if ($plan->status === 'fout' || $stuck)
          <div class="card p-6">
            <p class="flex items-center gap-2 font-semibold text-danger">
              <x-icon name="AlertTriangle" class="size-5" /> Het concept kon niet gemaakt worden
            </p>
            <p class="mt-1 text-sm text-muted">{{ $stuck ? 'De generatie is niet afgerond (mogelijk door een herstart van de server).' : $plan->error }}</p>
            <div class="mt-5">
              <x-plans.generate-forms :user-id="$member->id" :type="$plan->type" :ai-enabled="$aiEnabled" :has-intake="$hasIntake" :starts-on="$futureStart" />
            </div>
          </div>
        @endif

        @if ($plan->status === 'vervangen' && $content)
          <p class="rounded-xl bg-surface p-4 text-sm">
            Dit is een oudere versie en is niet meer bewerkbaar. De actuele versie vind je rechts onder Versies.
          </p>
          <div class="card p-6">
            @if ($type === 'training')
              <x-plans.training-view :plan="$content" />
            @else
              <x-plans.nutrition-view :plan="$content" />
            @endif
          </div>
        @endif

        @if ($editable)
          <x-plans.editor :plan="$plan" :content="$content" :today="$today" :starts-on="$startsOn" :renew-on="$renewOn"
            :follow-duration="$plan->renew_on === null && ! $live" :intake="$intake"
            :error="session('plan_error')" :success="session('plan_success')" />
          <details class="card p-6">
            <summary class="cursor-pointer font-semibold">Nieuw concept laten maken</summary>
            <div class="mt-4">
              <x-plans.generate-forms :user-id="$member->id" :type="$plan->type" :ai-enabled="$aiEnabled" :has-intake="$hasIntake" :starts-on="$live ? null : $futureStart" :regenerate="true" />
            </div>
          </details>
        @endif
      </div>

      <aside class="grid min-w-0 grid-cols-1 content-start gap-6">
        <section class="card p-6" aria-labelledby="planning">
          <div class="flex items-center justify-between gap-3">
            <h2 id="planning" class="text-lg font-semibold">Planning</h2>
            @if ($row)
              <x-plans.stage-badge :stage="$row['stage']" :coaching-status="$member->coaching_status" />
            @endif
          </div>
          <dl class="mt-3 grid gap-2 text-sm">
            <div class="flex justify-between gap-3">
              <dt class="text-muted">Huidig schema</dt>
              <dd class="text-right font-medium">{{ ($row['current'] ?? null) ? 'sinds '.PlanView::shortDate($row['current']['publishedAt']) : 'nog geen' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-muted">Nieuw schema nodig</dt>
              <dd class="text-right font-medium">
                @if ($row['currentDueOn'] ?? null)
                  <span class="first-letter:uppercase">{{ PlanLabels::formatPlanDayLong($row['currentDueOn']) }}</span>
                  <span class="block font-normal {{ $row['currentDueOn'] <= $today && $row['stage'] !== 'pauze' && $row['stage'] !== 'gepland' ? 'text-danger' : 'text-muted' }}">{{ Pipeline::relativeDay($today, $row['currentDueOn']) }}</span>
                @else
                  zo snel mogelijk
                @endif
              </dd>
            </div>
            @if ($row['scheduled'] ?? null)
              <div class="flex justify-between gap-3">
                <dt class="text-muted">Volgend schema</dt>
                <dd class="text-right font-medium">
                  @if ($row['scheduled']['id'] === $plan->id)
                    dit schema
                  @else
                    <a href="{{ AdminLabels::planHref($type, $row['scheduled']['id']) }}" class="underline decoration-accent underline-offset-4">ingepland</a>
                  @endif
                  <span class="block font-normal text-muted">start {{ PlanLabels::formatPlanDayLong($row['scheduled']['startsOn']) }}</span>
                </dd>
              </div>
            @endif
          </dl>
          @if ($plan->status === 'gepubliceerd')
            <p class="mt-3 text-xs text-muted">De datum pas je aan onder het schema, bij &ldquo;Nieuw schema op&rdquo;.</p>
          @endif
          @if ($plan->status !== 'concept' && $plan->status !== 'genereren' && ! ($row['open'] ?? null))
            <a href="{{ AdminLabels::newPlanHref($type, $member->id) }}" class="btn btn-sm btn-outline mt-4 w-full">Nieuw {{ $section['one'] }} maken</a>
          @endif
        </section>

        <section class="card p-6" aria-labelledby="versies">
          <h2 id="versies" class="text-lg font-semibold">Versies</h2>
          <ul class="mt-2 divide-y divide-line text-sm">
            @foreach ($versions as $v)
              @php
                  $s = PlanLabels::isStuck($v->status, $v->updated_at) ? ['label' => 'Vastgelopen', 'tone' => 'bg-danger/10 text-danger'] : PlanLabels::STATUS[$v->status];
                  $here = $v->id === $plan->id;
              @endphp
              <li>
                <a href="{{ AdminLabels::planHref($type, $v->id) }}" @if ($here) aria-current="page" @endif
                  class="-mx-2 flex items-center justify-between gap-3 rounded-md px-2 py-2 {{ $here ? 'bg-surface' : 'hover:bg-surface' }}">
                  <span>
                    <span class="font-semibold">#{{ $v->id }}</span>
                    <span class="ml-2 text-muted">{{ $v->status === 'gepland' && $v->starts_on ? 'start '.PlanLabels::formatPlanDay($v->starts_on) : PlanView::shortDate($v->published_at ?? $v->created_at) }} · {{ $v->source === 'ai' ? 'AI' : 'handmatig' }}</span>
                  </span>
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $s['tone'] }}">{{ $s['label'] }}</span>
                </a>
              </li>
            @endforeach
          </ul>
        </section>

        <section class="card p-6" aria-labelledby="intake">
          <h2 id="intake" class="text-lg font-semibold">Intake</h2>
          <div class="mt-4">
            @if ($intake)
              <x-plans.intake-panel :intake="$intake" :updated-at="$intakeUpdatedAt" />
            @else
              <p class="text-sm text-muted">Geen intake ingevuld.</p>
            @endif
          </div>
        </section>
      </aside>
    </div>
  </x-admin.page>
</x-layouts.admin>
