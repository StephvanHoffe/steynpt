@php
    use App\Support\Intake as I;

    $e = $errors->intake;
    // Na een fout tonen we wat de klant net invulde, anders de opgeslagen intake.
    $hasOld = session()->hasOldInput();
    $str = fn ($v) => $v === null ? null : (is_int($v) || is_float($v) ? \App\Support\Js::numberToString($v) : (string) $v);
    $val = fn (string $key) => $hasOld ? old($key) : $str($initial[$key] ?? null);
    $list = fn (string $key) => $hasOld ? (array) (old($key) ?? []) : ($initial[$key] ?? ($key === 'wants' ? ['training', 'voeding'] : []));
    $one = fn (string $key) => ($x = $val($key)) !== null && $x !== '' ? [$x] : [];
    $asOptions = fn (array $values, string $suffix = '') => array_map(fn ($v) => ['id' => (string) $v, 'label' => $v.$suffix], $values);
    // De keuzes staan in het Nederlands in App\Support\Intake; hier in de taal van het lid.
    $tr = fn (array $options) => array_map(fn (array $o) => [...$o, 'label' => __($o['label']), 'hint' => isset($o['hint']) ? __($o['hint']) : null], $options);
@endphp
<x-layouts.account :title="__('Mijn intake')">
  <div class="container-site max-w-3xl py-10 lg:py-14">
    <p class="eyebrow text-accent">{{ __('Mijn omgeving') }}</p>
    <h1 class="display display-lg mt-3">{{ $exists ? __('Intake bijwerken') : __('Jouw intake') }}</h1>
    <p class="lead mt-4 text-muted">{{ __('Vertel ons over je doel, je training en je voeding. Op basis hiervan maken we je persoonlijke trainings- en/of voedingsschema. Steyn controleert het altijd voordat je het te zien krijgt.') }}</p>
    <div class="mt-10">
      <form method="post" action="/account/intake" class="grid gap-6" novalidate>
        @csrf
        <x-form.alert :error="session('intake_error')" />

        <x-intake.section :step="1" :title="__('Wat wil je bereiken?')">
          <x-intake.choices name="wants" :legend="__('Waarvoor wil je een schema?')" :options="$tr(I::PLAN_WANTS)" :multiple="true" :selected="$list('wants')" :error="$e->first('wants') ?: null" columns="sm:grid-cols-2" />
          <x-form.select :label="__('Je belangrijkste doel')" name="goal" :options="$tr(\App\Site\Site::GOALS)" :placeholder="__('Kies je doel')" :value="$val('goal') ?? $user->goal ?? ''" bag="intake" />
          <x-intake.textarea name="goalDetails" :label="__('Vertel iets meer over je doel')" :value="$val('goalDetails')" :error="$e->first('goalDetails') ?: null" :placeholder="__('Bijv. \'Ik wil in juni een halve marathon lopen\' of \'5 kilo afvallen en meer energie\'')" />
        </x-intake.section>

        <x-intake.section :step="2" :title="__('Over jou')" :intro="__('Hiermee berekenen we onder andere je energiebehoefte.')">
          <x-intake.choices name="sex" :legend="__('Geslacht')" :options="$tr(I::SEXES)" :selected="$one('sex')" :error="$e->first('sex') ?: null" />
          <div class="grid gap-5 sm:grid-cols-3">
            <x-form.field :label="__('Geboortejaar')" name="birthYear" inputmode="numeric" :placeholder="__('Bijv. 1995')" :value="$val('birthYear')" :old="false" bag="intake" />
            <x-form.field :label="__('Lengte (cm)')" name="heightCm" inputmode="numeric" :placeholder="__('Bijv. 178')" :value="$val('heightCm')" :old="false" bag="intake" />
            <x-form.field :label="__('Gewicht (kg)')" name="weightKg" inputmode="decimal" :placeholder="__('Bijv. 74,5')" :value="$val('weightKg')" :old="false" bag="intake" />
          </div>
          <x-form.field :label="__('Streefgewicht (kg)')" name="targetWeightKg" inputmode="decimal" :placeholder="__('Optioneel')" :value="$val('targetWeightKg')" :old="false" bag="intake" />
          <x-intake.choices name="activityLevel" :legend="__('Hoe actief ben je overdag (naast het sporten)?')" :options="$tr(I::ACTIVITY_LEVELS)" :selected="$one('activityLevel')" :error="$e->first('activityLevel') ?: null" columns="sm:grid-cols-4" />
          <x-intake.textarea name="medical" :label="__('Medische aandachtspunten of medicatie')" :value="$val('medical')" :error="$e->first('medical') ?: null"
            :hint="__('Bijv. hoge bloeddruk, diabetes, zwangerschap of medicijngebruik. Steyn houdt hier rekening mee.')" />
        </x-intake.section>

        <x-intake.section :step="3" :title="__('Training')">
          <x-intake.choices name="experience" :legend="__('Hoeveel trainingservaring heb je?')" :options="$tr(I::EXPERIENCE)" :selected="$one('experience')" :error="$e->first('experience') ?: null" />
          <x-intake.choices name="trainingDays" :legend="__('Hoe vaak per week wil je trainen?')" :options="$asOptions([1, 2, 3, 4, 5, 6, 7], '×')" :selected="$one('trainingDays')" :error="$e->first('trainingDays') ?: null" columns="grid-cols-4 sm:grid-cols-7" />
          <x-intake.choices name="sessionMinutes" :legend="__('Hoe lang mag een training duren?')" :options="$asOptions(I::SESSION_MINUTES, ' min')" :selected="$one('sessionMinutes')" :error="$e->first('sessionMinutes') ?: null" columns="sm:grid-cols-5" />
          <x-intake.choices name="location" :legend="__('Waar train je meestal?')" :options="$tr(I::LOCATIONS)" :selected="$one('location')" :error="$e->first('location') ?: null" columns="sm:grid-cols-4" />
          <x-intake.textarea name="equipment" :label="__('Welk materiaal heb je?')" :value="$val('equipment')" :error="$e->first('equipment') ?: null" :placeholder="__('Bijv. dumbbells tot 20 kg, weerstandsbanden, een bankje')" />
          <x-form.field :label="__('Beoefen je een sport? (optioneel)')" name="sport" :value="$val('sport')" :old="false" bag="intake" :placeholder="__('Bijv. hockey, hardlopen, voetbal')" />
          <x-intake.textarea name="injuries" :label="__('Blessures of fysieke beperkingen')" :value="$val('injuries')" :error="$e->first('injuries') ?: null" :placeholder="__('Bijv. last van je onderrug of een oude knieblessure')" />
        </x-intake.section>

        <x-intake.section :step="4" :title="__('Voeding')">
          <x-intake.choices name="diet" :legend="__('Wat is je eetstijl?')" :options="$tr(I::DIETS)" :selected="$one('diet')" :error="$e->first('diet') ?: null" />
          <x-intake.choices name="allergies" :legend="__('Allergieën en intoleranties')" :options="$tr(I::ALLERGIES)" :multiple="true" :selected="$list('allergies')" :error="$e->first('allergies') ?: null" columns="sm:grid-cols-4" />
          <x-form.field :label="__('Andere allergieën of intoleranties (optioneel)')" name="allergiesOther" :value="$val('allergiesOther')" :old="false" bag="intake" />
          <x-intake.textarea name="dislikes" :label="__('Wat lust je niet?')" :value="$val('dislikes')" :error="$e->first('dislikes') ?: null" :placeholder="__('Bijv. champignons, koriander')" />
          <x-intake.choices name="mealsPerDay" :legend="__('Hoeveel eetmomenten per dag passen bij jou?')" :options="$asOptions([2, 3, 4, 5, 6])" :selected="$one('mealsPerDay')" :error="$e->first('mealsPerDay') ?: null" columns="grid-cols-5" />
        </x-intake.section>

        <div class="rounded-xl bg-accent-tint p-6">
          <label class="flex gap-3 text-sm">
            <input type="checkbox" name="consent" class="mt-0.5 size-4 shrink-0 accent-ink" @checked(old('consent') === 'on')>
            <span>{{ __('Ik geef toestemming om deze gegevens, waaronder gezondheidsgegevens, te gebruiken voor mijn schema. Een eerste opzet wordt gemaakt met behulp van AI, zonder mijn naam of contactgegevens; Steyn controleert en past het schema aan voordat ik het te zien krijg. Lees de') }} <a href="{{ \App\Site\Locale::path('/privacy') }}" target="_blank" class="font-semibold underline">{{ __('privacyverklaring') }}</a>.</span>
          </label>
          @if ($e->has('consent'))<p class="field-error">{{ $e->first('consent') }}</p>@endif
          <x-form.submit class="btn btn-primary mt-5 w-full sm:w-auto" :pending-text="__('Opslaan…')">{{ __('Intake opslaan') }} <x-icon name="ArrowRight" class="size-4" /></x-form.submit>
        </div>
      </form>
    </div>
  </div>
</x-layouts.account>
