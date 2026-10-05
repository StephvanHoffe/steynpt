<x-layouts.site title="Inloggen" :noindex="true">
  <x-auth-shell title="Welkom terug" :aside="['title' => 'Blijf in beweging', 'items' => ['Plan je volgende training in de agenda', 'Bekijk je voortgang en de feedback van Steyn', 'Nodig een vriend uit en krijg samen korting']]">
    <x-slot:intro><p>Log in voor je afspraken, schema's, voortgang en check-ins.</p></x-slot:intro>
    @if ($melding)<p class="mb-6 rounded-xl border border-line bg-surface p-4 text-sm">{{ $melding }}</p>@endif
    <form method="post" action="/inloggen" class="grid gap-5" novalidate>
      @csrf
      <x-form.alert :error="session('error')" />
      @if ($next)<input type="hidden" name="next" value="{{ $next }}">@endif
      <x-form.field label="E-mailadres" name="email" type="email" autocomplete="email" required />
      <x-form.field label="Wachtwoord" name="password" type="password" autocomplete="current-password" :old="false" required />
      <x-form.submit pending-text="Inloggen…">Inloggen</x-form.submit>
      <p class="text-center text-sm text-muted">Nog geen account? <a href="/registreren" class="font-semibold text-ink underline">Maak er gratis een aan</a></p>
      <p class="text-center text-xs text-muted">Wachtwoord vergeten? <a href="/contact" class="underline">Neem contact op</a>, dan helpen we je verder.</p>
    </form>
  </x-auth-shell>
</x-layouts.site>
