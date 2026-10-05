@php
    $regenCodes = session('regen_codes');
    $goals = array_map(fn ($g) => [...$g, 'label' => __($g['label'])], \App\Site\Site::GOALS);
    $languages = [['id' => 'nl', 'label' => 'Nederlands'], ['id' => 'en', 'label' => 'English']];
@endphp
<x-layouts.account :title="__('Mijn profiel')">
  <div class="container-site max-w-3xl py-10 lg:py-14">
    <h1 class="display display-lg">{{ __('Mijn profiel') }}</h1>

    <section class="card mt-8 p-6 sm:p-8" aria-labelledby="gegevens">
      <h2 id="gegevens" class="display text-2xl">{{ __('Gegevens') }}</h2>
      <div class="mt-6">
        <form method="post" action="/account/profiel" class="grid gap-5" novalidate>
          @csrf
          <x-form.alert :success="session('profile_success')" />
          <div class="grid gap-5 sm:grid-cols-2">
            <x-form.field :label="__('Voornaam')" name="firstName" autocomplete="given-name" :value="$user->first_name" bag="profile" />
            <x-form.field :label="__('Achternaam')" name="lastName" autocomplete="family-name" :value="$user->last_name" bag="profile" />
          </div>
          <x-form.field :label="__('E-mailadres')" name="email-readonly" :value="$user->email" :old="false" disabled :hint="__('Wil je je e-mailadres wijzigen? Neem contact op.')" />
          <div class="grid gap-5 sm:grid-cols-2">
            <x-form.field :label="__('Telefoonnummer')" name="phone" type="tel" autocomplete="tel" :value="$user->phone" bag="profile" />
            <x-form.select :label="__('Je belangrijkste doel')" name="goal" :options="$goals" :placeholder="__('Kies je doel')" :value="$user->goal" bag="profile" />
          </div>
          {{-- Taal van Mijn omgeving (users.locale); de namen van de talen staan altijd in hun eigen taal. --}}
          <div class="grid gap-5 sm:grid-cols-2">
            <x-form.select :label="__('Taal')" name="locale" :options="$languages" :value="$user->locale ?: 'nl'" bag="profile" />
          </div>
          <label class="flex gap-3 text-sm">
            <input type="checkbox" name="marketing" @checked($errors->profile->any() ? old('marketing') === 'on' : $user->marketing_opt_in) class="mt-0.5 size-4 shrink-0 accent-ink">
            {{ __('Houd me op de hoogte van acties, tips en nieuwe sessies.') }}
          </label>
          <x-form.submit class="btn btn-ink w-full sm:w-auto" :pending-text="__('Opslaan…')">{{ __('Opslaan') }}</x-form.submit>
        </form>
      </div>
    </section>

    <section class="card mt-6 p-6 sm:p-8" aria-labelledby="wachtwoord">
      <h2 id="wachtwoord" class="display text-2xl">{{ __('Wachtwoord') }}</h2>
      <p class="mt-2 text-sm {{ $daysLeft <= 7 ? 'font-semibold text-danger' : 'text-muted' }}">{{ __('Om de 8 weken kies je een nieuw wachtwoord.') }} {{ match (true) {
          $daysLeft > 7 => __('Je huidige wachtwoord verloopt op :date.', ['date' => \App\View\Fmt::date($expires)]),
          $daysLeft === 1 => __('Je huidige wachtwoord verloopt op :date (over 1 dag).', ['date' => \App\View\Fmt::date($expires)]),
          default => __('Je huidige wachtwoord verloopt op :date (over :days dagen).', ['date' => \App\View\Fmt::date($expires), 'days' => $daysLeft]),
      } }}</p>
      <div class="mt-6">
        <form method="post" action="/account/profiel/wachtwoord" class="grid gap-5" novalidate>
          @csrf
          <x-form.alert :success="session('password_success')" />
          <x-form.field :label="__('Huidig wachtwoord')" name="current" type="password" autocomplete="current-password" :old="false" bag="password" />
          <div class="grid gap-5 sm:grid-cols-2">
            <x-form.field :label="__('Nieuw wachtwoord')" name="password" type="password" autocomplete="new-password" minlength="8" :old="false" bag="password" :hint="__('Minimaal 8 tekens')" />
            <x-form.field :label="__('Herhaal nieuw wachtwoord')" name="confirm" type="password" autocomplete="new-password" :old="false" bag="password" />
          </div>
          <x-form.submit class="btn btn-ink w-full sm:w-auto" :pending-text="__('Wijzigen…')">{{ __('Wachtwoord wijzigen') }}</x-form.submit>
        </form>
      </div>
    </section>

    <section class="card mt-6 p-6 sm:p-8" aria-labelledby="tweestaps">
      <h2 id="tweestaps" class="display text-2xl">{{ __('Tweestapsverificatie') }}</h2>
      <p class="mt-2 flex items-start gap-2 text-sm">
        <x-icon name="ShieldCheck" class="size-5 shrink-0 text-success" />
        <span>{{ __('Staat aan sinds :date.', ['date' => \App\View\Fmt::date($user->totp_enabled_at)]) }} {!! __($codesLeft === 1 ? 'Je hebt nog :count ongebruikte herstelcode.' : 'Je hebt nog :count ongebruikte herstelcodes.', ['count' => '<strong>'.e($codesLeft).'</strong>']) !!}</span>
      </p>
      <div class="mt-6 grid gap-8">
        <div>
          <h3 class="font-semibold">{{ __('Nieuwe herstelcodes') }}</h3>
          <p class="mb-3 mt-1 text-sm text-muted">{{ __('Herstelcodes kwijt of bijna op? Maak nieuwe; de oude werken dan niet meer.') }}</p>
          @if ($regenCodes)
            <div class="grid gap-4">
              <x-form.alert :success="session('regen_success')" />
              <x-recovery-codes :codes="$regenCodes" />
            </div>
          @else
            <form method="post" action="/account/profiel/herstelcodes" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end" novalidate>
              @csrf
              <div class="sm:col-span-2"><x-form.alert :error="session('regen_error')" /></div>
              <x-form.field :label="__('Code uit je app')" name="code" id="regen-code" :old="false" bag="regen" required inputmode="numeric" autocomplete="one-time-code"
                pattern="[0-9 ]*" maxlength="7" placeholder="123 456" class="input font-mono tracking-[0.2em]" />
              <x-form.submit class="btn btn-outline" pending-text="…">{{ __('Nieuwe herstelcodes') }}</x-form.submit>
            </form>
          @endif
        </div>
        <div>
          <h3 class="font-semibold">{{ __('Nieuwe telefoon') }}</h3>
          <p class="mb-3 mt-1 text-sm text-muted">{{ __('Hiermee ontkoppel je de huidige app en log je uit. Bij het inloggen koppel je daarna je nieuwe telefoon.') }}</p>
          <form method="post" action="/account/profiel/tweestaps-resetten" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end" novalidate>
            @csrf
            <div class="sm:col-span-2"><x-form.alert :error="session('reset_error')" /></div>
            <x-form.field :label="__('Code uit je app of een herstelcode')" name="code" id="reset-code" :old="false" bag="reset" required autocomplete="one-time-code" class="input font-mono tracking-wider" />
            <x-form.submit class="btn btn-outline" pending-text="…">{{ __('Opnieuw koppelen') }}</x-form.submit>
          </form>
        </div>
      </div>
    </section>

    <section class="mt-6 rounded-xl border border-danger/30 p-6 sm:p-8" aria-labelledby="verwijderen">
      <h2 id="verwijderen" class="display text-2xl">{{ __('Account verwijderen') }}</h2>
      <p class="mt-2 text-sm text-muted">{{ __("Hiermee verwijder je je account en al je gegevens definitief, inclusief je afspraken, metingen en schema's.") }}</p>
      <div class="mt-6">
        <form method="post" action="/account/profiel/verwijderen" class="grid gap-4" novalidate>
          @csrf
          <x-form.alert :error="session('delete_error')" />
          <x-form.field id="delete-password" :label="__('Wachtwoord ter bevestiging')" name="password" type="password" autocomplete="current-password" :old="false" bag="delete" />
          <label class="flex gap-3 text-sm">
            <input type="checkbox" name="confirm" class="mt-0.5 size-4 shrink-0 accent-danger">
            {{ __("Ik begrijp dat mijn account, afspraken, metingen, check-ins, intake en schema's definitief worden verwijderd.") }}
          </label>
          @if ($errors->delete->has('confirm'))<p class="field-error">{{ $errors->delete->first('confirm') }}</p>@endif
          <x-form.submit class="btn btn-sm w-full border-[1.5px] border-danger text-danger hover:bg-danger hover:text-white sm:w-auto" :pending-text="__('Verwijderen…')">{{ __('Account verwijderen') }}</x-form.submit>
        </form>
      </div>
    </section>
  </div>
</x-layouts.account>
