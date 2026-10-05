<x-layouts.site :title="__('Nieuw wachtwoord')" :noindex="true">
  <x-auth-shell :title="$expired ? __('Tijd voor een nieuw wachtwoord') : __('Nieuw wachtwoord kiezen')" :aside="['title' => __('Een sterk wachtwoord'), 'items' => [__('Minimaal 8 tekens, liever een zin van een paar woorden'), __('Niet hetzelfde als je vorige wachtwoord'), __('Gebruik het nergens anders')]]">
    <x-slot:intro><p>{{ $expired
        ? __('Voor je veiligheid vraagt SteynPT om de :weeks weken een nieuw wachtwoord. Je vorige wachtwoord is van :date.', ['weeks' => $weeks, 'date' => \App\View\Fmt::date($changed)])
        : __('Je huidige wachtwoord verloopt op :date. Je kunt het nu al vernieuwen.', ['date' => \App\View\Fmt::date($expires)]) }}</p></x-slot:intro>
    <form method="post" action="/wachtwoord-vernieuwen" class="grid gap-5" novalidate>
      @csrf
      <x-form.alert :error="session('error')" />
      <input type="hidden" name="next" value="{{ $next }}">
      <x-form.field :label="__('Huidig wachtwoord')" name="current" type="password" autocomplete="current-password" :old="false" required />
      <x-form.field :label="__('Nieuw wachtwoord')" name="password" type="password" autocomplete="new-password" :old="false" required minlength="8" :hint="__('Minimaal 8 tekens.')" />
      <x-form.field :label="__('Herhaal nieuw wachtwoord')" name="confirm" type="password" autocomplete="new-password" :old="false" required />
      <x-form.submit :pending-text="__('Opslaan…')">{{ __('Wachtwoord opslaan') }}</x-form.submit>
      <p class="text-center text-xs text-muted">{{ __('Andere apparaten waarop je bent ingelogd, worden daarna uitgelogd.') }}</p>
    </form>
  </x-auth-shell>
</x-layouts.site>
