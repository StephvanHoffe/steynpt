<x-layouts.site title="Nieuw wachtwoord" :noindex="true">
  <x-auth-shell :title="$expired ? 'Tijd voor een nieuw wachtwoord' : 'Nieuw wachtwoord kiezen'" :aside="['title' => 'Een sterk wachtwoord', 'items' => ['Minimaal 8 tekens, liever een zin van een paar woorden', 'Niet hetzelfde als je vorige wachtwoord', 'Gebruik het nergens anders']]">
    <x-slot:intro><p>{{ $expired
        ? "Voor je veiligheid vraagt SteynPT om de {$weeks} weken een nieuw wachtwoord. Je vorige wachtwoord is van ".\App\View\Fmt::date($changed).'.'
        : 'Je huidige wachtwoord verloopt op '.\App\View\Fmt::date($expires).'. Je kunt het nu al vernieuwen.' }}</p></x-slot:intro>
    <form method="post" action="/wachtwoord-vernieuwen" class="grid gap-5" novalidate>
      @csrf
      <x-form.alert :error="session('error')" />
      <input type="hidden" name="next" value="{{ $next }}">
      <x-form.field label="Huidig wachtwoord" name="current" type="password" autocomplete="current-password" :old="false" required />
      <x-form.field label="Nieuw wachtwoord" name="password" type="password" autocomplete="new-password" :old="false" required minlength="8" hint="Minimaal 8 tekens." />
      <x-form.field label="Herhaal nieuw wachtwoord" name="confirm" type="password" autocomplete="new-password" :old="false" required />
      <x-form.submit pending-text="Opslaan…">Wachtwoord opslaan</x-form.submit>
      <p class="text-center text-xs text-muted">Andere apparaten waarop je bent ingelogd, worden daarna uitgelogd.</p>
    </form>
  </x-auth-shell>
</x-layouts.site>
