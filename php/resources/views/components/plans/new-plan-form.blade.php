@props(['userId', 'type', 'today', 'defaultStart', 'currentEndsOn' => null, 'ai', 'hasCurrent'])
{{-- Eén formulier voor een nieuw schema: startdatum en hoe je wilt beginnen.
     ai: ['available' => true] of ['available' => false, 'reason' => …] --}}
@php
    use App\Support\Agenda;
    use App\View\PlanLabels;

    $defaultMethod = $ai['available'] ? 'ai' : ($hasCurrent ? 'huidig' : 'leeg');
    $options = [[
        'id' => 'ai',
        'icon' => 'Sparkles',
        'title' => 'Laat de AI een concept maken',
        'text' => 'Op basis van de intake, eventueel met een instructie.',
        'disabled' => $ai['available'] ? null : $ai['reason'],
    ]];
    if ($hasCurrent) {
        $options[] = ['id' => 'huidig', 'icon' => 'CopyPlus', 'title' => 'Verder met het huidige schema', 'text' => 'Een kopie als concept: pas aan wat er verandert.', 'disabled' => null];
    }
    $options[] = [
        'id' => 'leeg',
        'icon' => 'FilePlus2',
        'title' => 'Zelf een leeg schema opstellen',
        'text' => $type === 'training' ? 'Met het aantal trainingsdagen uit de intake al klaargezet.' : 'Met de richtwaarden uit de intake al ingevuld.',
        'disabled' => null,
    ];
    $submitLabels = ['ai' => 'Concept laten maken', 'huidig' => 'Kopie als concept', 'leeg' => 'Leeg schema starten'];
    $quick = [['day' => $today, 'label' => 'Vandaag']];
    if ($currentEndsOn && $currentEndsOn > $today) {
        $quick[] = ['day' => $currentEndsOn, 'label' => 'Als het huidige schema afloopt'];
    }
    $quick[] = ['day' => Agenda::addDays($today, 7), 'label' => 'Over een week'];
    $later = $defaultStart > $today;
    $config = ['today' => $today, 'defaultStart' => $defaultStart, 'defaultMethod' => $defaultMethod, 'submitLabels' => $submitLabels];
@endphp
<form action="{{ \App\Services\AdminLabels::PLAN_SECTION[$type]['href'] }}/nieuw" method="post" class="card grid gap-7 p-5 sm:p-6" x-data="newPlanForm(@js($config))">
  @csrf
  <input type="hidden" name="userId" value="{{ $userId }}">
  <input type="hidden" name="type" value="{{ $type }}">

  <fieldset>
    <legend class="text-lg font-semibold">1. Wanneer start het schema?</legend>
    <div class="mt-3 flex flex-wrap items-center gap-2">
      <label class="sr-only" for="startsOn">Startdatum</label>
      <input id="startsOn" type="date" name="startsOn" required min="{{ $today }}" max="{{ Agenda::addDays($today, 366) }}" value="{{ $defaultStart }}"
        :value="start" @input="setStart($event.target.value)" class="input h-10 w-auto min-h-0 py-1">
      @foreach ($quick as $q)
        <button type="button" @click="start = @js($q['day'])" :aria-pressed="(start === @js($q['day'])).toString()" aria-pressed="{{ $defaultStart === $q['day'] ? 'true' : 'false' }}"
          class="rounded-full border px-3 py-1.5 text-sm font-medium {{ $defaultStart === $q['day'] ? 'border-ink bg-ink text-white' : 'border-line bg-white hover:border-ink' }}"
          :class="{ 'border-ink bg-ink text-white': start === @js($q['day']), 'border-line bg-white hover:border-ink': start !== @js($q['day']) }">{{ $q['label'] }}</button>
      @endforeach
    </div>
    <p class="mt-2 text-sm text-muted" aria-live="polite">
      <span x-show="later" @unless ($later) style="display: none" @endunless>
        De klant ziet het schema vanaf <strong class="font-semibold text-ink" x-text="startLabel">{{ PlanLabels::formatPlanDayLong($defaultStart) }}</strong> in Mijn omgeving.{{ $hasCurrent ? ' Tot die dag blijft het huidige schema zichtbaar.' : '' }}
      </span>
      <span x-show="!later" @if ($later) style="display: none" @endif>Het schema staat voor de klant klaar zodra je het hebt gecontroleerd en gepubliceerd.</span>
    </p>
  </fieldset>

  <fieldset>
    <legend class="text-lg font-semibold">2. Hoe wil je beginnen?</legend>
    <div class="mt-3 grid gap-2">
      @foreach ($options as $o)
        @php
            $base = 'flex gap-3 rounded-xl border p-4';
            $selectedClass = 'cursor-pointer border-ink bg-white ring-1 ring-ink';
            $idleClass = 'cursor-pointer border-line bg-white hover:border-ink/40';
        @endphp
        @if ($o['disabled'])
          <label class="{{ $base }} cursor-not-allowed border-line bg-surface/60">
        @else
          <label class="{{ $base }} {{ $defaultMethod === $o['id'] ? $selectedClass : $idleClass }}" :class="{ [@js($idleClass)]: method !== @js($o['id']), [@js($selectedClass)]: method === @js($o['id']) }">
        @endif
            <input type="radio" name="method" value="{{ $o['id'] }}" x-model="method" @checked($defaultMethod === $o['id']) @disabled($o['disabled']) class="mt-1 accent-ink">
            <span class="min-w-0">
              <span class="flex items-center gap-2 font-semibold">
                <x-icon :name="$o['icon']" class="size-4 text-accent" /> {{ $o['title'] }}
              </span>
              <span class="mt-0.5 block text-sm text-muted">{{ $o['disabled'] ?? $o['text'] }}</span>
            </span>
          </label>
      @endforeach
    </div>
    <label class="mt-4 block" x-show="method === 'ai'" @if ($defaultMethod !== 'ai') style="display: none" @endif>
      <span class="label">Instructie voor de AI (optioneel)</span>
      <textarea name="instruction" rows="3" maxlength="1500" class="input min-h-0 py-2 text-sm" :disabled="method !== 'ai'"
        placeholder="{{ $type === 'training' ? "Bijv. 'volgende fase: meer kracht, 4 dagen, geen squats vanwege de knie'" : "Bijv. 'calorieën 100 kcal omlaag, meer warme lunches'" }}"></textarea>
    </label>
  </fieldset>

  <div class="flex flex-wrap items-center gap-3 border-t border-line pt-5">
    <x-form.submit class="btn btn-primary" pending-text="Bezig…"><span x-text="submitLabel">{{ $submitLabels[$defaultMethod] }}</span></x-form.submit>
    <p class="text-sm text-muted">Je controleert het concept altijd eerst; de klant ziet niets tot je het goedkeurt.</p>
  </div>
</form>
