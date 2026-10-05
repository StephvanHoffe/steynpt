@php
    $regenCodes = session('regen_codes');
@endphp
<x-layouts.account title="Mijn profiel">
  <div class="container-site max-w-3xl py-10 lg:py-14">
    <h1 class="display display-lg">Mijn profiel</h1>

    <section class="card mt-8 p-6 sm:p-8" aria-labelledby="gegevens">
      <h2 id="gegevens" class="display text-2xl">Gegevens</h2>
      <div class="mt-6">
        <form method="post" action="/account/profiel" class="grid gap-5" novalidate>
          @csrf
          <x-form.alert :success="session('profile_success')" />
          <div class="grid gap-5 sm:grid-cols-2">
            <x-form.field label="Voornaam" name="firstName" autocomplete="given-name" :value="$user->first_name" bag="profile" />
            <x-form.field label="Achternaam" name="lastName" autocomplete="family-name" :value="$user->last_name" bag="profile" />
          </div>
          <x-form.field label="E-mailadres" name="email-readonly" :value="$user->email" :old="false" disabled hint="Wil je je e-mailadres wijzigen? Neem contact op." />
          <div class="grid gap-5 sm:grid-cols-2">
            <x-form.field label="Telefoonnummer" name="phone" type="tel" autocomplete="tel" :value="$user->phone" bag="profile" />
            <x-form.select label="Je belangrijkste doel" name="goal" :options="\App\Site\Site::GOALS" placeholder="Kies je doel" :value="$user->goal" bag="profile" />
          </div>
          <label class="flex gap-3 text-sm">
            <input type="checkbox" name="marketing" @checked($errors->profile->any() ? old('marketing') === 'on' : $user->marketing_opt_in) class="mt-0.5 size-4 shrink-0 accent-ink">
            Houd me op de hoogte van acties, tips en nieuwe sessies.
          </label>
          <x-form.submit class="btn btn-ink w-full sm:w-auto" pending-text="Opslaan…">Opslaan</x-form.submit>
        </form>
      </div>
    </section>

    <section class="card mt-6 p-6 sm:p-8" aria-labelledby="wachtwoord">
      <h2 id="wachtwoord" class="display text-2xl">Wachtwoord</h2>
      <p class="mt-2 text-sm {{ $daysLeft <= 7 ? 'font-semibold text-danger' : 'text-muted' }}">Om de 8 weken kies je een nieuw wachtwoord. Je huidige wachtwoord verloopt op {{ \App\View\Fmt::date($expires) }}{{ $daysLeft <= 7 ? ' (over '.$daysLeft.' '.($daysLeft === 1 ? 'dag' : 'dagen').')' : '' }}.</p>
      <div class="mt-6">
        <form method="post" action="/account/profiel/wachtwoord" class="grid gap-5" novalidate>
          @csrf
          <x-form.alert :success="session('password_success')" />
          <x-form.field label="Huidig wachtwoord" name="current" type="password" autocomplete="current-password" :old="false" bag="password" />
          <div class="grid gap-5 sm:grid-cols-2">
            <x-form.field label="Nieuw wachtwoord" name="password" type="password" autocomplete="new-password" minlength="8" :old="false" bag="password" hint="Minimaal 8 tekens" />
            <x-form.field label="Herhaal nieuw wachtwoord" name="confirm" type="password" autocomplete="new-password" :old="false" bag="password" />
          </div>
          <x-form.submit class="btn btn-ink w-full sm:w-auto" pending-text="Wijzigen…">Wachtwoord wijzigen</x-form.submit>
        </form>
      </div>
    </section>

    <section class="card mt-6 p-6 sm:p-8" aria-labelledby="tweestaps">
      <h2 id="tweestaps" class="display text-2xl">Tweestapsverificatie</h2>
      <p class="mt-2 flex items-start gap-2 text-sm">
        <x-icon name="ShieldCheck" class="size-5 shrink-0 text-success" />
        <span>Staat aan sinds {{ \App\View\Fmt::date($user->totp_enabled_at) }}. Je hebt nog <strong>{{ $codesLeft }}</strong> ongebruikte {{ $codesLeft === 1 ? 'herstelcode' : 'herstelcodes' }}.</span>
      </p>
      <div class="mt-6 grid gap-8">
        <div>
          <h3 class="font-semibold">Nieuwe herstelcodes</h3>
          <p class="mb-3 mt-1 text-sm text-muted">Herstelcodes kwijt of bijna op? Maak nieuwe; de oude werken dan niet meer.</p>
          @if ($regenCodes)
            <div class="grid gap-4">
              <x-form.alert :success="session('regen_success')" />
              <x-recovery-codes :codes="$regenCodes" />
            </div>
          @else
            <form method="post" action="/account/profiel/herstelcodes" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end" novalidate>
              @csrf
              <div class="sm:col-span-2"><x-form.alert :error="session('regen_error')" /></div>
              <x-form.field label="Code uit je app" name="code" id="regen-code" :old="false" bag="regen" required inputmode="numeric" autocomplete="one-time-code"
                pattern="[0-9 ]*" maxlength="7" placeholder="123 456" class="input font-mono tracking-[0.2em]" />
              <x-form.submit class="btn btn-outline" pending-text="…">Nieuwe herstelcodes</x-form.submit>
            </form>
          @endif
        </div>
        <div>
          <h3 class="font-semibold">Nieuwe telefoon</h3>
          <p class="mb-3 mt-1 text-sm text-muted">Hiermee ontkoppel je de huidige app en log je uit. Bij het inloggen koppel je daarna je nieuwe telefoon.</p>
          <form method="post" action="/account/profiel/tweestaps-resetten" class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end" novalidate>
            @csrf
            <div class="sm:col-span-2"><x-form.alert :error="session('reset_error')" /></div>
            <x-form.field label="Code uit je app of een herstelcode" name="code" id="reset-code" :old="false" bag="reset" required autocomplete="one-time-code" class="input font-mono tracking-wider" />
            <x-form.submit class="btn btn-outline" pending-text="…">Opnieuw koppelen</x-form.submit>
          </form>
        </div>
      </div>
    </section>

    <section class="mt-6 rounded-xl border border-danger/30 p-6 sm:p-8" aria-labelledby="verwijderen">
      <h2 id="verwijderen" class="display text-2xl">Account verwijderen</h2>
      <p class="mt-2 text-sm text-muted">Hiermee verwijder je je account en al je gegevens definitief, inclusief je afspraken, metingen en schema's.</p>
      <div class="mt-6">
        <form method="post" action="/account/profiel/verwijderen" class="grid gap-4" novalidate>
          @csrf
          <x-form.alert :error="session('delete_error')" />
          <x-form.field id="delete-password" label="Wachtwoord ter bevestiging" name="password" type="password" autocomplete="current-password" :old="false" bag="delete" />
          <label class="flex gap-3 text-sm">
            <input type="checkbox" name="confirm" class="mt-0.5 size-4 shrink-0 accent-danger">
            Ik begrijp dat mijn account, afspraken, metingen, check-ins, intake en schema's definitief worden verwijderd.
          </label>
          @if ($errors->delete->has('confirm'))<p class="field-error">{{ $errors->delete->first('confirm') }}</p>@endif
          <x-form.submit class="btn btn-sm w-full border-[1.5px] border-danger text-danger hover:bg-danger hover:text-white sm:w-auto" pending-text="Verwijderen…">Account verwijderen</x-form.submit>
        </form>
      </div>
    </section>
  </div>
</x-layouts.account>
