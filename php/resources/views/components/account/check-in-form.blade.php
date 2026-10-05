@props(['done'])
@php
    $scales = [
        ['name' => 'energy', 'label' => 'Energie', 'low' => 'Leeg', 'high' => 'Topfit'],
        ['name' => 'sleep', 'label' => 'Slaap', 'low' => 'Slecht', 'high' => 'Uitstekend'],
        ['name' => 'nutrition', 'label' => 'Voeding', 'low' => 'Lastig', 'high' => 'Volgens plan'],
    ];
    $e = $errors->checkin;
@endphp
@if (session('checkin_success'))
  <x-form.alert :success="session('checkin_success')" />
@elseif (! $done || session('checkin_error'))
  <form method="post" action="/account/check-in" class="grid gap-5" novalidate>
    @csrf
    <x-form.alert :error="session('checkin_error')" />
    @foreach ($scales as $scale)
      <fieldset>
        <legend class="label">{{ $scale['label'] }}</legend>
        <div class="grid grid-cols-5 gap-1.5">
          @foreach ([1, 2, 3, 4, 5] as $n)
            <label class="grid h-11 cursor-pointer place-items-center rounded-lg border-[1.5px] border-line bg-white text-sm font-semibold transition-colors hover:border-ink/40 has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent">
              <input type="radio" name="{{ $scale['name'] }}" value="{{ $n }}" @checked(old($scale['name']) === (string) $n) class="sr-only">
              {{ $n }}
            </label>
          @endforeach
        </div>
        <div class="mt-1 flex justify-between text-xs text-muted">
          <span>{{ $scale['low'] }}</span>
          <span>{{ $scale['high'] }}</span>
        </div>
        @if ($e->has($scale['name']))<p class="field-error">{{ $e->first($scale['name']) }}</p>@endif
      </fieldset>
    @endforeach
    <div class="grid gap-5 sm:grid-cols-2">
      <div>
        <label for="ci-workouts" class="label">Trainingen deze week</label>
        <input id="ci-workouts" name="workouts" type="number" min="0" max="21" inputmode="numeric" value="{{ old('workouts', '3') }}" class="input" @if ($e->has('workouts')) aria-invalid="true" @endif>
        @if ($e->has('workouts'))<p class="field-error">{{ $e->first('workouts') }}</p>@endif
      </div>
      <div>
        <label for="ci-weight" class="label">Gewicht in kg <span class="font-normal text-muted">(optioneel)</span></label>
        <input id="ci-weight" name="weight" inputmode="decimal" placeholder="Bijv. 74,5" value="{{ old('weight') }}" class="input" @if ($e->has('weight')) aria-invalid="true" @endif>
        @if ($e->has('weight'))<p class="field-error">{{ $e->first('weight') }}</p>@endif
      </div>
    </div>
    <div>
      <label for="ci-note" class="label">Hoe ging je week? <span class="font-normal text-muted">(optioneel)</span></label>
      <textarea id="ci-note" name="note" class="input min-h-24" placeholder="Wat ging goed, waar liep je tegenaan?">{{ old('note') }}</textarea>
      @if ($e->has('note'))<p class="field-error">{{ $e->first('note') }}</p>@endif
    </div>
    <x-form.submit pending-text="Opslaan…">Check-in versturen</x-form.submit>
  </form>
@endif
