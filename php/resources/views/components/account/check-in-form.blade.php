@props(['done'])
@php
    $scales = [
        ['name' => 'energy', 'label' => __('Energie'), 'low' => __('Leeg'), 'high' => __('Topfit')],
        ['name' => 'sleep', 'label' => __('Slaap'), 'low' => __('Slecht'), 'high' => __('Uitstekend')],
        ['name' => 'nutrition', 'label' => __('Voeding'), 'low' => __('Lastig'), 'high' => __('Volgens plan')],
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
        <label for="ci-workouts" class="label">{{ __('Trainingen deze week') }}</label>
        <input id="ci-workouts" name="workouts" type="number" min="0" max="21" inputmode="numeric" value="{{ old('workouts', '3') }}" class="input" @if ($e->has('workouts')) aria-invalid="true" @endif>
        @if ($e->has('workouts'))<p class="field-error">{{ $e->first('workouts') }}</p>@endif
      </div>
      <div>
        <label for="ci-weight" class="label">{{ __('Gewicht in kg') }} <span class="font-normal text-muted">{{ __('(optioneel)') }}</span></label>
        <input id="ci-weight" name="weight" inputmode="decimal" placeholder="{{ __('Bijv. 74,5') }}" value="{{ old('weight') }}" class="input" @if ($e->has('weight')) aria-invalid="true" @endif>
        @if ($e->has('weight'))<p class="field-error">{{ $e->first('weight') }}</p>@endif
      </div>
    </div>
    <div>
      <label for="ci-note" class="label">{{ __('Hoe ging je week?') }} <span class="font-normal text-muted">{{ __('(optioneel)') }}</span></label>
      <textarea id="ci-note" name="note" class="input min-h-24" placeholder="{{ __('Wat ging goed, waar liep je tegenaan?') }}">{{ old('note') }}</textarea>
      @if ($e->has('note'))<p class="field-error">{{ $e->first('note') }}</p>@endif
    </div>
    <x-form.submit :pending-text="__('Opslaan…')">{{ __('Check-in versturen') }}</x-form.submit>
  </form>
@endif
