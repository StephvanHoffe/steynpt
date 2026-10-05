<x-layouts.site :title="__('Inloggen')" :noindex="true">
  <x-auth-shell :title="__('Welkom terug')" :aside="['title' => __('Blijf in beweging'), 'items' => [__('Plan je volgende training in de agenda'), __('Bekijk je voortgang en de feedback van Steyn'), __('Nodig een vriend uit en krijg samen korting')]]">
    <x-slot:intro><p>{{ __("Log in voor je afspraken, schema's, voortgang en check-ins.") }}</p></x-slot:intro>
    @if ($melding)<p class="mb-6 rounded-xl border border-line bg-surface p-4 text-sm">{{ $melding }}</p>@endif
    <form method="post" action="/inloggen" class="grid gap-5" novalidate>
      @csrf
      <x-form.alert :error="session('error')" />
      @if ($next)<input type="hidden" name="next" value="{{ $next }}">@endif
      <x-form.field :label="__('E-mailadres')" name="email" type="email" autocomplete="email" required />
      <x-form.field :label="__('Wachtwoord')" name="password" type="password" autocomplete="current-password" :old="false" required />
      <x-form.submit :pending-text="__('Inloggen…')">{{ __('Inloggen') }}</x-form.submit>
      <p class="text-center text-sm text-muted">{{ __('Nog geen account?') }} <a href="/registreren" class="font-semibold text-ink underline">{{ __('Maak er gratis een aan') }}</a></p>
      <p class="text-center text-xs text-muted">{!! __('Wachtwoord vergeten? :link, dan helpen we je verder.', ['link' => '<a href="'.e(\App\Site\Locale::path('/contact')).'" class="underline">'.e(__('Neem contact op')).'</a>']) !!}</p>
    </form>
  </x-auth-shell>
</x-layouts.site>
