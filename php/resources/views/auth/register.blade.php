@php
    use App\Site\Locale;
    $selectedPlan = (string) old('plan', $plan);
    $options = [...array_map(fn ($p) => ['id' => $p['id'], 'title' => $p['name'], 'sub' => __('€ :price per maand', ['price' => Locale::price($p['price'])])], $plans), ['id' => '', 'title' => __('Nog niet'), 'sub' => __('Eerst rondkijken')]];
    $goals = array_map(fn ($g) => [...$g, 'label' => __($g['label'])], \App\Site\Site::GOALS);
@endphp
<x-layouts.site :title="__('Account aanmaken')" :noindex="true" :description="__('Maak je gratis SteynPT-account aan voor online coaching, je schema\'s, voortgang en afspraken.')">
  <x-auth-shell :title="__('Maak je account aan')" :aside="['title' => __('Alles voor jouw doel op één plek'), 'items' => [
      __('Je online coaching, schema en feedback van Steyn'),
      __('Je metingen en voortgang in één overzicht'),
      __('Zelf afspraken inplannen in de agenda van Steyn'),
      __('Je persoonlijke link om vrienden uit te nodigen'),
      __('Steyn neemt binnen 24 uur contact op voor je intake'),
  ]]">
    <x-slot:intro><p>{{ __('Gratis en in twee minuten geregeld.') }}@if ($invitation) {!! __('Omdat :name je uitnodigde, krijg je :reward.', ['name' => e($invitation['firstName']), 'reward' => '<strong class="text-ink">'.e($friendReward).'</strong>']) !!}@endif</p></x-slot:intro>

    <form method="post" action="/registreren" class="grid gap-5" novalidate>
      @csrf
      <x-form.alert :error="session('error')" />

      <fieldset>
        <legend class="label">{{ __('Kies je pakket voor online coaching') }}</legend>
        <div class="grid gap-2.5 sm:grid-cols-2">
          @foreach ($options as $option)
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border-[1.5px] border-line bg-white p-4 transition-colors has-[:checked]:border-ink has-[:checked]:bg-surface has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent">
              <input type="radio" name="plan" value="{{ $option['id'] }}" @checked($selectedPlan === $option['id']) class="size-4 accent-ink">
              <span>
                <span class="block font-semibold">{{ $option['title'] }}</span>
                <span class="block text-sm text-muted">{{ $option['sub'] }}</span>
              </span>
            </label>
          @endforeach
        </div>
      </fieldset>

      <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field :label="__('Voornaam')" name="firstName" autocomplete="given-name" required />
        <x-form.field :label="__('Achternaam')" name="lastName" autocomplete="family-name" required />
      </div>
      <x-form.field :label="__('E-mailadres')" name="email" type="email" autocomplete="email" required />
      <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field :label="__('Telefoonnummer')" name="phone" type="tel" autocomplete="tel" :hint="__('Optioneel, handig voor het plannen van je intake')" />
        <x-form.select :label="__('Je belangrijkste doel')" name="goal" :options="$goals" :placeholder="__('Kies je doel')" required />
      </div>
      <x-form.field :label="__('Wachtwoord')" name="password" type="password" autocomplete="new-password" minlength="8" :old="false" required :hint="__('Minimaal 8 tekens')" />
      <x-form.field :label="__('Uitnodigingscode')" name="referralCode" :value="$invitation['code'] ?? null" :placeholder="__('Bijv. LISA-7K2Q')" autocapitalize="characters"
        :hint="$invitation ? __('Uitgenodigd door :name: je krijgt :reward', ['name' => $invitation['firstName'], 'reward' => $friendReward]) : __('Optioneel, van een vriend die al traint bij SteynPT')" />

      <div class="space-y-3 rounded-xl bg-surface p-4 text-sm">
        <label class="flex gap-3">
          <input type="checkbox" name="terms" @checked(old('terms') === 'on') class="mt-0.5 size-4 shrink-0 accent-ink" required>
          <span>{!! __('Ik heb de :link gelezen en geef toestemming om mijn check-ins en metingen (zoals mijn gewicht) te gebruiken voor mijn coaching.', ['link' => '<a href="'.e(Locale::path('/privacy')).'" target="_blank" class="font-semibold underline">'.e(__('privacyverklaring')).'</a>']) !!}</span>
        </label>
        @error('terms')<p class="field-error">{{ $message }}</p>@enderror
        <label class="flex gap-3">
          <input type="checkbox" name="marketing" @checked(old('marketing') === 'on') class="mt-0.5 size-4 shrink-0 accent-ink">
          <span>{{ __('Houd me op de hoogte van acties, tips en nieuwe sessies (optioneel).') }}</span>
        </label>
      </div>

      <x-form.submit :pending-text="__('Account aanmaken…')">{{ __('Account aanmaken') }} <x-icon name="ArrowRight" class="size-4" /></x-form.submit>
      <p class="text-center text-sm text-muted">{{ __('Heb je al een account?') }} <a href="/inloggen" class="font-semibold text-ink underline">{{ __('Log in') }}</a></p>
    </form>
  </x-auth-shell>
</x-layouts.site>
