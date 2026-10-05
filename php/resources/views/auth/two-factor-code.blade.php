@php
    $error = session('error');
    // Tussenstap verlopen of mislukt: alleen nog opnieuw inloggen (vlag van TwoFactorController, of de Nederlandse melding).
    $expired = $error && (session('relogin') || str_contains($error, 'opnieuw in'));
@endphp
<x-layouts.site :title="__('Inlogcode')" :noindex="true">
  <x-auth-shell :title="__('Vul je inlogcode in')" :aside="['title' => __('Extra beveiligd'), 'items' => [__('Je wachtwoord alleen is niet genoeg om in te loggen'), __('De code staat alleen op jouw telefoon'), __('Telefoon kwijt? Gebruik een herstelcode')]]">
    <x-slot:intro><p>{{ __('Open je authenticator-app en vul de 6 cijfers bij SteynPT in. De code wisselt elke 30 seconden.') }}</p></x-slot:intro>
    {{-- Tweede stap bij het inloggen: code uit de app, of een herstelcode als de telefoon er niet is. --}}
    <form method="post" action="/inloggen/verificatie" class="grid gap-5" novalidate x-data="{ recovery: @js((bool) session('recovery')) }">
      @csrf
      <x-form.alert :error="$error" />
      @if ($expired)
        <a href="/inloggen" class="btn btn-primary w-full">{{ __('Opnieuw inloggen') }}</a>
      @else
        <input type="hidden" name="recovery" :value="recovery ? 1 : 0" value="0">
        <template x-if="recovery">
          <x-form.field :label="__('Herstelcode')" name="code" :old="false" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="abcde-fghij"
            class="input font-mono text-lg tracking-wider" required autofocus :hint="__('Elke herstelcode werkt één keer.')" />
        </template>
        <template x-if="!recovery">
          <x-form.field :label="__('Code uit je authenticator-app')" name="code" :old="false" required autofocus inputmode="numeric" autocomplete="one-time-code"
            pattern="[0-9 ]*" maxlength="7" placeholder="123 456" class="input text-center font-mono text-2xl tracking-[0.3em]" />
        </template>
        <x-form.submit :pending-text="__('Controleren…')">{{ __('Inloggen') }}</x-form.submit>
        <button type="button" @click="recovery = !recovery" class="text-sm font-semibold text-ink underline decoration-accent underline-offset-4"
          x-text="recovery ? @js(__('Toch de code uit de app gebruiken')) : @js(__('Telefoon niet bij de hand? Gebruik een herstelcode'))">{{ __('Telefoon niet bij de hand? Gebruik een herstelcode') }}</button>
        <p class="text-center text-xs text-muted">{!! __('Geen toegang meer tot je app en herstelcodes? :link, dan zet Steyn je tweestapsverificatie uit, zodat je hem opnieuw kunt instellen.', ['link' => '<a href="'.e(\App\Site\Locale::path('/contact')).'" class="underline">'.e(__('Neem contact op')).'</a>']) !!}</p>
      @endif
    </form>
  </x-auth-shell>
</x-layouts.site>
