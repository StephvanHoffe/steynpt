@php
    $selectedPlan = (string) old('plan', $plan);
    $options = [...array_map(fn ($p) => ['id' => $p['id'], 'title' => $p['name'], 'sub' => "€ {$p['price']} per maand"], $plans), ['id' => '', 'title' => 'Nog niet', 'sub' => 'Eerst rondkijken']];
@endphp
<x-layouts.site title="Account aanmaken" description="Maak je gratis SteynPT-account aan voor online coaching, je schema's, voortgang en afspraken.">
  <x-auth-shell title="Maak je account aan" :aside="['title' => 'Alles voor jouw doel op één plek', 'items' => [
      'Je online coaching, schema en feedback van Steyn',
      'Je metingen en voortgang in één overzicht',
      'Zelf afspraken inplannen in de agenda van Steyn',
      'Je persoonlijke link om vrienden uit te nodigen',
      'Steyn neemt binnen 24 uur contact op voor je intake',
  ]]">
    <x-slot:intro><p>Gratis en in twee minuten geregeld.@if ($invitation) Omdat {{ $invitation['firstName'] }} je uitnodigde, krijg je <strong class="text-ink">{{ $friendReward }}</strong>.@endif</p></x-slot:intro>

    <form method="post" action="/registreren" class="grid gap-5" novalidate>
      @csrf
      <x-form.alert :error="session('error')" />

      <fieldset>
        <legend class="label">Kies je online coaching pakket</legend>
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
        <x-form.field label="Voornaam" name="firstName" autocomplete="given-name" required />
        <x-form.field label="Achternaam" name="lastName" autocomplete="family-name" required />
      </div>
      <x-form.field label="E-mailadres" name="email" type="email" autocomplete="email" required />
      <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field label="Telefoonnummer" name="phone" type="tel" autocomplete="tel" hint="Optioneel, handig voor het plannen van je intake" />
        <x-form.select label="Je belangrijkste doel" name="goal" :options="\App\Site\Site::GOALS" placeholder="Kies je doel" required />
      </div>
      <x-form.field label="Wachtwoord" name="password" type="password" autocomplete="new-password" minlength="8" :old="false" required hint="Minimaal 8 tekens" />
      <x-form.field label="Uitnodigingscode" name="referralCode" :value="$invitation['code'] ?? null" placeholder="Bijv. LISA-7K2Q" autocapitalize="characters"
        :hint="$invitation ? 'Uitgenodigd door '.$invitation['firstName'].': je krijgt '.$friendReward : 'Optioneel, van een vriend die al traint bij SteynPT'" />

      <div class="space-y-3 rounded-xl bg-surface p-4 text-sm">
        <label class="flex gap-3">
          <input type="checkbox" name="terms" @checked(old('terms') === 'on') class="mt-0.5 size-4 shrink-0 accent-ink" required>
          <span>Ik ga akkoord met de <a href="/privacy" target="_blank" class="font-semibold underline">privacyverklaring</a> en geef toestemming om mijn check-in gegevens (zoals gewicht) te gebruiken voor mijn coaching.</span>
        </label>
        @error('terms')<p class="field-error">{{ $message }}</p>@enderror
        <label class="flex gap-3">
          <input type="checkbox" name="marketing" @checked(old('marketing') === 'on') class="mt-0.5 size-4 shrink-0 accent-ink">
          <span>Houd me op de hoogte van acties, tips en nieuwe sessies (optioneel).</span>
        </label>
      </div>

      <x-form.submit pending-text="Account aanmaken…">Account aanmaken <x-icon name="ArrowRight" class="size-4" /></x-form.submit>
      <p class="text-center text-sm text-muted">Heb je al een account? <a href="/inloggen" class="font-semibold text-ink underline">Log in</a></p>
    </form>
  </x-auth-shell>
</x-layouts.site>
