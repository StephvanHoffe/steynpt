@php
    $error = session('error');
    $expired = $error && str_contains($error, 'opnieuw in');
@endphp
<x-layouts.site title="Inlogcode" :noindex="true">
  <x-auth-shell title="Vul je inlogcode in" :aside="['title' => 'Extra beveiligd', 'items' => ['Je wachtwoord alleen is niet genoeg om in te loggen', 'De code staat alleen op jouw telefoon', 'Telefoon kwijt? Gebruik een herstelcode']]">
    <x-slot:intro><p>Open je authenticator-app en vul de 6 cijfers bij SteynPT in. De code wisselt elke 30 seconden.</p></x-slot:intro>
    {{-- Tweede stap bij het inloggen: code uit de app, of een herstelcode als de telefoon er niet is. --}}
    <form method="post" action="/inloggen/verificatie" class="grid gap-5" novalidate x-data="{ recovery: @js((bool) session('recovery')) }">
      @csrf
      <x-form.alert :error="$error" />
      @if ($expired)
        <a href="/inloggen" class="btn btn-primary w-full">Opnieuw inloggen</a>
      @else
        <input type="hidden" name="recovery" :value="recovery ? 1 : 0" value="0">
        <template x-if="recovery">
          <x-form.field label="Herstelcode" name="code" :old="false" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="abcde-fghij"
            class="input font-mono text-lg tracking-wider" required autofocus hint="Elke herstelcode werkt één keer." />
        </template>
        <template x-if="!recovery">
          <x-form.field label="Code uit je authenticator-app" name="code" :old="false" required autofocus inputmode="numeric" autocomplete="one-time-code"
            pattern="[0-9 ]*" maxlength="7" placeholder="123 456" class="input text-center font-mono text-2xl tracking-[0.3em]" />
        </template>
        <x-form.submit pending-text="Controleren…">Inloggen</x-form.submit>
        <button type="button" @click="recovery = !recovery" class="text-sm font-semibold text-ink underline decoration-accent underline-offset-4"
          x-text="recovery ? 'Toch de code uit de app gebruiken' : 'Telefoon niet bij de hand? Gebruik een herstelcode'">Telefoon niet bij de hand? Gebruik een herstelcode</button>
        <p class="text-center text-xs text-muted">Geen toegang meer tot je app en herstelcodes? <a href="/contact" class="underline">Neem contact op</a>, dan zet Steyn de tweestapsverificatie voor je terug.</p>
      @endif
    </form>
  </x-auth-shell>
</x-layouts.site>
