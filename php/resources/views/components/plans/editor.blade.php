@props([
    'plan',
    'content',
    'today',
    // Dag waarop het schema voor de klant ingaat.
    'startsOn',
    // Dag waarop de klant toe is aan een nieuw schema.
    'renewOn',
    // Nog geen vaste datum: volg de startdatum en de duur van het schema tot Steyn zelf een datum kiest.
    'followDuration',
    // Gevalideerde intake (voor de allergenencontrole) of null.
    'intake' => null,
    'error' => null,
    'success' => null,
])
{{-- Bewerkscherm voor Steyn (Alpine-component planEditor in resources/js/components/plan-editor.js).
     De inhoud gaat als JSON in het veld "content" naar de server en wordt daar opnieuw gecontroleerd. --}}
@php
    use App\Services\AdminLabels;
    use App\Services\Plans\PlanView;
    use App\Support\Agenda;
    use App\Support\Plans\Allergens;
    use App\Support\Plans\Pipeline;
    use App\Support\Plans\PlanSchema;

    $status = $plan->status;
    $live = $status === 'gepubliceerd';
    $isTraining = $plan->type === 'training';
    $href = AdminLabels::planHref($plan->type, $plan->id);
    $checkAllergens = ! $isTraining && $intake !== null;

    // Na een fout op de server: de ingevulde (nog niet opgeslagen) inhoud en datums terugzetten.
    $old = null;
    if (session()->hasOldInput('content')) {
        $posted = json_decode((string) old('content'), true);
        [$oldContent] = is_array($posted) ? PlanSchema::parse($plan->type, $posted) : [null];
        $old = ['content' => $oldContent, 'startsOn' => old('startsOn'), 'renewOn' => old('renewOn')];
    }
    $start = $old['startsOn'] ?? $startsOn;
    $later = ! $live && $start > $today;
    $publishLabel = $live ? 'Opnieuw publiceren' : ($later ? ($status === 'gepland' ? 'Opnieuw inplannen' : 'Goedkeuren & inplannen') : ($status === 'gepland' ? 'Nu publiceren' : 'Goedkeuren & publiceren'));

    $config = [
        'initial' => $content,
        'status' => $status,
        'today' => $today,
        'startsOn' => $startsOn,
        'renewOn' => $renewOn,
        'followDuration' => $followDuration,
        'renewWeeks' => ['training' => Pipeline::defaultRenewWeeks('training'), 'voeding' => Pipeline::defaultRenewWeeks('voeding')],
        'warnings' => $checkAllergens ? Allergens::findAllergenWarnings($content, $intake) : [],
        'allergenCheck' => $checkAllergens ? PlanView::allergenCheck($intake) : null,
        'empty' => [
            'day' => PlanSchema::emptyTrainingDay(1),
            'exercise' => PlanSchema::emptyExercise(),
            'meal' => PlanSchema::emptyMeal(),
            'option' => PlanSchema::emptyMealOption(),
        ],
        'previewUrl' => $href.'/voorbeeld',
        'old' => $old,
    ];
    $input = 'input min-h-10 py-2 text-sm';
    $area = 'input min-h-0 py-2 text-sm';
    $removeClass = 'grid size-10 shrink-0 place-items-center rounded-lg text-muted transition-colors hover:bg-accent-tint hover:text-accent';
    $addClass = 'btn btn-sm btn-outline bg-white';
@endphp
<form method="post" action="{{ $href }}" class="grid gap-6" x-data="planEditor(@js($config))" @submit="onSubmit($event)">
  @csrf
  <input type="hidden" name="planId" value="{{ $plan->id }}">
  <input type="hidden" name="content" :value="json">

  <template x-if="warnings.length > 0">
    <div role="alert" class="rounded-xl border border-danger/30 bg-danger/5 p-4 text-sm">
      <p class="flex items-center gap-2 font-semibold text-danger">
        <x-icon name="AlertTriangle" class="size-4" /> Controleer op allergieën en eetstijl
      </p>
      <ul class="mt-2 space-y-1">
        <template x-for="(w, i) in warnings" :key="i">
          <li><strong x-text="w.where"></strong><span x-text="`: “${w.term}” (${w.reason})`"></span></li>
        </template>
      </ul>
      <p class="mt-2 text-xs text-muted">Automatische controle op trefwoorden; niet volledig, dus controleer het schema ook zelf.</p>
    </div>
  </template>

  <div class="flex gap-1 rounded-full bg-surface p-1 text-sm font-semibold" role="tablist" aria-label="Weergave">
    @foreach ([['bewerken', 'Bewerken', 'Pencil'], ['voorbeeld', 'Voorbeeld voor de klant', 'Eye']] as [$id, $label, $icon])
      <button type="button" role="tab" aria-selected="{{ $id === 'bewerken' ? 'true' : 'false' }}" :aria-selected="tab === @js($id)"
        @click="{{ $id === 'voorbeeld' ? 'showPreview()' : "tab = 'bewerken'" }}"
        class="flex flex-1 items-center justify-center gap-2 rounded-full px-4 py-2 transition-colors {{ $id === 'bewerken' ? 'bg-white shadow-sm' : 'text-muted hover:text-ink' }}"
        :class="{ 'bg-white shadow-sm': tab === @js($id), 'text-muted hover:text-ink': tab !== @js($id) }">
        <x-icon :name="$icon" class="size-4" /> {{ $label }}
      </button>
    @endforeach
  </div>

  {{-- Alleen in de pagina zolang je bewerkt (zoals in het origineel); de inhoud zelf staat in de Alpine-gegevens. --}}
  <template x-if="tab === 'bewerken'">
  <div>
    @if ($isTraining)
      <div class="grid gap-6">
        <div class="grid gap-4 sm:grid-cols-[1fr_10rem]">
          <label class="block"><span class="label">Titel</span><input class="{{ $input }}" x-model="plan.title"></label>
          <label class="block">
            <span class="label">Duur<span class="font-normal text-muted"> (weken)</span></span>
            <input type="number" inputmode="decimal" class="{{ $input }}" :value="numValue($el, plan.durationWeeks)" @input="plan.durationWeeks = toNumber($event.target.value)">
          </label>
        </div>
        <label class="block"><span class="label">Toelichting voor de klant</span><textarea class="{{ $area }}" rows="4" x-model="plan.summary" x-text="plan.summary"></textarea></label>

        <template x-for="(day, i) in plan.days" :key="i">
          <fieldset class="rounded-xl border border-line bg-white p-4 sm:p-5">
            <legend class="px-1 text-sm font-semibold text-accent" x-text="`Trainingsdag ${i + 1}`"></legend>
            <div class="grid gap-4">
              <div class="flex items-end gap-2">
                <div class="grid flex-1 gap-4 sm:grid-cols-2">
                  <label class="block"><span class="label">Naam</span><input class="{{ $input }}" x-model="day.name"></label>
                  <label class="block"><span class="label">Focus</span><input class="{{ $input }}" x-model="day.focus"></label>
                </div>
                <button type="button" @click="removeAt(plan.days, i)" :aria-label="`Dag ${i + 1} verwijderen`" :title="`Dag ${i + 1} verwijderen`" class="{{ $removeClass }}">
                  <x-icon name="Trash2" class="size-4" />
                </button>
              </div>
              <label class="block"><span class="label">Warming-up</span><textarea class="{{ $area }}" rows="2" x-model="day.warmup" x-text="day.warmup"></textarea></label>

              <div class="grid gap-2">
                <p class="label mb-0">Oefeningen</p>
                <template x-for="(ex, j) in day.exercises" :key="j">
                  <div class="grid gap-2 rounded-lg bg-paper p-3 sm:grid-cols-[2fr_4rem_6rem_6rem_2.5rem]">
                    <label class="block"><span class="sr-only" x-text="`Oefening ${j + 1}`"></span><input class="{{ $input }}" x-model="ex.name"></label>
                    <label class="block"><span class="sr-only">Sets</span><input class="{{ $input }}" x-model="ex.sets"></label>
                    <label class="block"><span class="sr-only">Herhalingen</span><input class="{{ $input }}" x-model="ex.reps"></label>
                    <label class="block"><span class="sr-only">Rust</span><input class="{{ $input }}" x-model="ex.rest"></label>
                    <button type="button" @click="removeAt(day.exercises, j)" :aria-label="`Oefening ${ex.name || j + 1} verwijderen`" :title="`Oefening ${ex.name || j + 1} verwijderen`" class="{{ $removeClass }}">
                      <x-icon name="Trash2" class="size-4" />
                    </button>
                    <label class="block sm:col-span-5"><span class="sr-only">Toelichting</span><input class="{{ $input }}" x-model="ex.notes"></label>
                  </div>
                </template>
                <p class="text-xs text-muted">Per oefening: naam · sets · herhalingen · rust, met daaronder de toelichting.</p>
                <div>
                  <button type="button" @click="addExercise(day)" class="{{ $addClass }}"><x-icon name="Plus" class="size-4" /> Oefening</button>
                </div>
              </div>
              <label class="block"><span class="label">Cooling-down</span><textarea class="{{ $area }}" rows="2" x-model="day.cooldown" x-text="day.cooldown"></textarea></label>
            </div>
          </fieldset>
        </template>
        <div>
          <button type="button" @click="addDay()" class="{{ $addClass }}"><x-icon name="Plus" class="size-4" /> Trainingsdag</button>
        </div>
        <label class="block"><span class="label">Progressie</span><textarea class="{{ $area }}" rows="3" x-model="plan.progression" x-text="plan.progression"></textarea></label>
        <label class="block">
          <span class="label">Tips</span>
          <textarea class="{{ $area }}" :rows="Math.max(3, plan.tips.length + 1)" :value="lines(plan.tips)" x-text="lines(plan.tips)" @input="plan.tips = splitLines($event.target.value)"></textarea>
          <span class="mt-1 block text-xs text-muted">Eén per regel.</span>
        </label>
      </div>
    @else
      <div class="grid gap-6">
        <label class="block"><span class="label">Titel</span><input class="{{ $input }}" x-model="plan.title"></label>
        <label class="block"><span class="label">Toelichting voor de klant</span><textarea class="{{ $area }}" rows="4" x-model="plan.summary" x-text="plan.summary"></textarea></label>

        <fieldset class="rounded-xl border border-line bg-white p-4 sm:p-5">
          <legend class="px-1 text-sm font-semibold text-accent">Richtwaarden per dag</legend>
          <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
            @foreach ([['Energie', 'kcal', 'calories'], ['Eiwit', 'g', 'protein'], ['Koolh.', 'g', 'carbs'], ['Vet', 'g', 'fat']] as [$label, $suffix, $key])
              <label class="block">
                <span class="label">{{ $label }}<span class="font-normal text-muted"> ({{ $suffix }})</span></span>
                <input type="number" inputmode="decimal" class="{{ $input }}" :value="numValue($el, plan.targets.{{ $key }})" @input="plan.targets.{{ $key }} = toNumber($event.target.value)">
              </label>
            @endforeach
            <label class="block"><span class="label">Water</span><input class="{{ $input }}" x-model="plan.targets.water"></label>
          </div>
          <p class="mt-3 text-xs" :class="{ 'font-semibold text-danger': Math.abs(macroKcal - plan.targets.calories) > 100, 'text-muted': Math.abs(macroKcal - plan.targets.calories) <= 100 }"
            x-text="`Macro's samen: ${macroKcal} kcal${Math.abs(macroKcal - plan.targets.calories) > 100 ? ' — wijkt af van de energie-richtwaarde' : ''}`"></p>
        </fieldset>

        <label class="block">
          <span class="label">Vermijden (o.a. allergieën)</span>
          <textarea class="{{ $area }}" :rows="Math.max(3, plan.avoid.length + 1)" :value="lines(plan.avoid)" x-text="lines(plan.avoid)" @input="plan.avoid = splitLines($event.target.value)"></textarea>
          <span class="mt-1 block text-xs text-muted">Eén per regel.</span>
        </label>

        <template x-for="(meal, i) in plan.meals" :key="i">
          <fieldset class="rounded-xl border border-line bg-white p-4 sm:p-5">
            <legend class="px-1 text-sm font-semibold text-accent" x-text="`Eetmoment ${i + 1}`"></legend>
            <div class="grid gap-4">
              <div class="flex items-end gap-2">
                <div class="grid flex-1 gap-4 sm:grid-cols-2">
                  <label class="block"><span class="label">Naam</span><input class="{{ $input }}" x-model="meal.name"></label>
                  <label class="block"><span class="label">Tijd</span><input class="{{ $input }}" x-model="meal.time"></label>
                </div>
                <button type="button" @click="removeAt(plan.meals, i)" :aria-label="`${meal.name || `Eetmoment ${i + 1}`} verwijderen`" :title="`${meal.name || `Eetmoment ${i + 1}`} verwijderen`" class="{{ $removeClass }}">
                  <x-icon name="Trash2" class="size-4" />
                </button>
              </div>
              <template x-for="(option, j) in meal.options" :key="j">
                <div class="grid gap-3 rounded-lg bg-paper p-3">
                  <div class="flex items-end gap-2">
                    <div class="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-[2fr_7rem_7rem]">
                      <label class="block col-span-2 sm:col-span-1"><span class="label" x-text="`Optie ${j + 1}`"></span><input class="{{ $input }}" x-model="option.title"></label>
                      <label class="block">
                        <span class="label">kcal</span>
                        <input type="number" inputmode="decimal" class="{{ $input }}" :value="numValue($el, option.kcal)" @input="option.kcal = toNumber($event.target.value)">
                      </label>
                      <label class="block">
                        <span class="label">Eiwit<span class="font-normal text-muted"> (g)</span></span>
                        <input type="number" inputmode="decimal" class="{{ $input }}" :value="numValue($el, option.protein)" @input="option.protein = toNumber($event.target.value)">
                      </label>
                    </div>
                    <button type="button" @click="removeAt(meal.options, j)" :aria-label="`Optie ${option.title || j + 1} verwijderen`" :title="`Optie ${option.title || j + 1} verwijderen`" class="{{ $removeClass }}">
                      <x-icon name="Trash2" class="size-4" />
                    </button>
                  </div>
                  <label class="block"><span class="label">Ingrediënten en hoeveelheden</span><textarea class="{{ $area }}" rows="2" x-model="option.ingredients" x-text="option.ingredients"></textarea></label>
                </div>
              </template>
              <div>
                <button type="button" @click="addOption(meal)" class="{{ $addClass }}"><x-icon name="Plus" class="size-4" /> Optie</button>
              </div>
            </div>
          </fieldset>
        </template>
        <div>
          <button type="button" @click="addMeal()" class="{{ $addClass }}"><x-icon name="Plus" class="size-4" /> Eetmoment</button>
        </div>
        <label class="block">
          <span class="label">Tips</span>
          <textarea class="{{ $area }}" :rows="Math.max(3, plan.tips.length + 1)" :value="lines(plan.tips)" x-text="lines(plan.tips)" @input="plan.tips = splitLines($event.target.value)"></textarea>
          <span class="mt-1 block text-xs text-muted">Eén per regel.</span>
        </label>
      </div>
    @endif
  </div>
  </template>

  <div x-show="tab === 'voorbeeld'" style="display: none" class="rounded-xl border border-line bg-paper p-5 sm:p-8">
    <p x-show="preview.loading" class="flex items-center gap-2 text-sm text-muted"><x-icon name="LoaderCircle" class="size-4 animate-spin" /> Voorbeeld laden…</p>
    <p x-show="!preview.loading && preview.error" class="text-sm text-danger" x-text="preview.error"></p>
    <div x-show="!preview.loading" x-html="preview.html"></div>
  </div>

  <div class="sticky bottom-0 z-10 -mx-4 border-t border-line bg-paper/95 px-4 py-4 backdrop-blur sm:mx-0 sm:rounded-xl sm:border sm:px-5">
    <x-form.alert :error="$error" />
    @if ($success && ! $error)
      <div role="status" x-show="!dirty" class="flex gap-3 rounded-xl border p-4 text-sm border-success/30 bg-success/5 text-success">
        <x-icon name="CircleCheck" class="size-5 shrink-0" />
        <p>{{ $success }}</p>
      </div>
    @endif
    <div class="mt-3 grid gap-2 text-sm first:mt-0">
      @unless ($live)
        <label class="flex flex-wrap items-center gap-x-3 gap-y-1">
          <span class="w-32 font-semibold">Start op</span>
          <input type="date" name="startsOn" required value="{{ $start < $today ? $today : $start }}" :value="start < today ? today : start"
            min="{{ $today }}" max="{{ Agenda::addDays($today, 366) }}" @input="changeStart($event.target.value)" class="input h-9 w-auto min-h-0 bg-white py-1 text-sm">
          <span class="text-muted" x-text="startHint"></span>
        </label>
      @endunless
      <label class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <span class="w-32 font-semibold">Nieuw schema op</span>
        <input type="date" name="renewOn" required value="{{ $old['renewOn'] ?? $renewOn }}" :value="renewOn" :min="minRenewAttr" :max="maxRenew"
          @input="pickRenewOn($event.target.value)" class="input h-9 w-auto min-h-0 bg-white py-1 text-sm">
        <span :class="{ 'font-semibold text-danger': renewOn <= today, 'text-muted': renewOn > today }" x-text="renewHint"></span>
      </label>
    </div>
    <div class="mt-3 flex flex-wrap items-center gap-3">
      <button type="submit" name="intent" value="opslaan" class="btn btn-outline bg-white" :disabled="!dirty">{{ $status === 'concept' ? 'Concept opslaan' : 'Wijzigingen opslaan' }}</button>
      <button type="submit" name="intent" value="publiceren" class="btn btn-primary" x-text="publishLabel">{{ $publishLabel }}</button>
      <span class="text-sm text-muted" aria-live="polite" x-text="dirty ? 'Niet-opgeslagen wijzigingen' : 'Alles opgeslagen'">{{ $old ? 'Niet-opgeslagen wijzigingen' : 'Alles opgeslagen' }}</span>
    </div>
  </div>
</form>
