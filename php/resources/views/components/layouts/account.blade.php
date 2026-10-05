@props(['title' => null])
{{-- Mijn omgeving: de gewone site met een balk voor de onderdelen van het account. --}}
@php
    $user = auth()->user();
    $links = [
        ['href' => '/account', 'icon' => 'LayoutDashboard', 'label' => 'Dashboard'],
        ['href' => '/account/agenda', 'icon' => 'CalendarDays', 'label' => 'Agenda'],
        ['href' => '/account/voortgang', 'icon' => 'LineChart', 'label' => 'Voortgang'],
        ['href' => '/account/profiel', 'icon' => 'UserRound', 'label' => 'Profiel'],
    ];
    if ($user->isAdmin()) {
        $links[] = ['href' => '/admin', 'icon' => 'Shield', 'label' => 'Beheer'];
    }
@endphp
<x-layouts.site :title="$title ?? 'Mijn omgeving'" :noindex="true">
  <div class="bg-paper">
    <div class="border-b border-line bg-white print:hidden">
      <div class="container-site flex items-center justify-between gap-4 overflow-x-auto py-2">
        <nav aria-label="Account" class="flex gap-1">
          @foreach ($links as $link)
            <a href="{{ $link['href'] }}" class="inline-flex items-center gap-2 whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium hover:bg-surface">
              <x-icon :name="$link['icon']" class="size-4" /> {{ $link['label'] }}
            </a>
          @endforeach
        </nav>
        <form action="/uitloggen" method="post">
          @csrf
          <button type="submit" class="inline-flex items-center gap-2 whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium text-muted hover:bg-surface hover:text-ink">
            <x-icon name="LogOut" class="size-4" /> Uitloggen
          </button>
        </form>
      </div>
    </div>
    <x-password-reminder :user="$user" next="/account" />
    {{ $slot }}
  </div>
</x-layouts.site>
