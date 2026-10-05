<x-layouts.site title="Inlogcode" :noindex="true">
  <x-auth-shell title="Beveilig je account" :aside="['title' => 'Eenmalig instellen', 'items' => ['Werkt met elke authenticator-app', 'Daarna bij het inloggen een code van 6 cijfers', 'Herstelcodes voor als je je telefoon kwijt bent']]">
    <x-slot:intro><p>Bij SteynPT log je in met je wachtwoord én een code uit een app op je telefoon (tweestapsverificatie). Zo blijven je gegevens, schema's en metingen ook veilig als iemand je wachtwoord weet. Het instellen duurt een minuut.</p></x-slot:intro>

    @isset($codes)
      {{-- Ingesteld: herstelcodes bewaren, daarna inloggen. --}}
      <div class="grid gap-5" x-data="{ saved: false }">
        <x-form.alert success="Tweestapsverificatie staat aan. Voortaan vul je bij het inloggen ook de code uit de app in." />
        @if ($codes)
          <x-recovery-codes :codes="$codes" />
        @else
          <p class="rounded-xl border border-line bg-surface p-4 text-sm">Je herstelcodes zijn al getoond. Kwijt? Maak nieuwe in je profiel zodra je bent ingelogd.</p>
        @endif
        <label class="flex items-start gap-3 text-sm">
          <input type="checkbox" x-model="saved" class="mt-0.5 size-4 accent-ink">
          Ik heb mijn herstelcodes bewaard
        </label>
        <form method="post" action="/inloggen/verificatie/klaar">
          @csrf
          <x-form.submit class="btn btn-primary w-full disabled:cursor-not-allowed disabled:opacity-50" pending-text="Inloggen…" disabled-when="!saved">Verder</x-form.submit>
        </form>
      </div>
    @else
      {{-- Instellen: QR-code scannen en de eerste code invullen. --}}
      <form method="post" action="/inloggen/verificatie/instellen" class="grid gap-6" novalidate>
        @csrf
        <x-form.alert :error="session('error')" />
        <ol class="grid gap-6">
          <li class="flex gap-4">
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-ink text-sm font-semibold text-white">1</span>
            <div>
              <p class="font-semibold">Installeer een authenticator-app</p>
              <p class="mt-1 text-sm text-muted">Bijvoorbeeld Google Authenticator, Microsoft Authenticator, de Wachtwoorden-app op je iPhone of 1Password.</p>
            </div>
          </li>
          <li class="flex gap-4">
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-ink text-sm font-semibold text-white">2</span>
            <div class="min-w-0">
              <p class="font-semibold">Scan deze QR-code met de app</p>
              <img src="{{ $qr }}" alt="QR-code om SteynPT toe te voegen aan je authenticator-app" width="176" height="176" class="mt-3 rounded-lg border border-line bg-white p-2">
              <details class="mt-3 text-sm">
                <summary class="cursor-pointer font-medium text-ink underline decoration-accent underline-offset-4">
                  <x-icon name="Smartphone" class="mr-1 inline size-4" />
                  Op deze telefoon? Voer de sleutel handmatig in
                </summary>
                <p class="mt-2 text-muted">Kies in de app voor ‘Sleutel invoeren’ en gebruik als naam SteynPT:</p>
                <code class="mt-2 block break-all rounded-md bg-surface px-3 py-2 font-mono text-sm tracking-wider" data-totp-secret="{{ $rawSecret }}">{{ $secret }}</code>
              </details>
            </div>
          </li>
          <li class="flex gap-4">
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-ink text-sm font-semibold text-white">3</span>
            <div class="min-w-0 flex-1">
              <x-form.field label="Vul de 6 cijfers in die de app toont" name="code" :old="false" required inputmode="numeric" autocomplete="one-time-code"
                pattern="[0-9 ]*" maxlength="7" placeholder="123 456" class="input text-center font-mono text-2xl tracking-[0.3em]" />
            </div>
          </li>
        </ol>
        <x-form.submit pending-text="Controleren…">Tweestapsverificatie aanzetten</x-form.submit>
      </form>
    @endisset
  </x-auth-shell>
</x-layouts.site>
