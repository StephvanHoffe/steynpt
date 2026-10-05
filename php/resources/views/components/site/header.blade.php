@php
    $announcement = \App\Site\Texts::announcement();
    $path = '/'.ltrim(request()->path(), '/');
    $nav = \App\Site\Site::NAV;
@endphp
<header class="sticky top-0 z-50 print:hidden" x-data="{ open: false }" x-effect="document.body.style.overflow = open ? 'hidden' : ''" @keydown.escape.window="open = false">
  @if ($announcement)
    <a href="{{ $announcement['href'] }}" class="group block bg-ink px-4 py-2 text-center text-[13px] font-medium text-white">
      @if ($announcement['label'] !== '')<span class="mr-2 rounded bg-accent px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-white">{{ $announcement['label'] }}</span>@endif{{ $announcement['text'] }}<x-icon name="ArrowRight" class="ml-1 inline size-3.5 transition-transform group-hover:translate-x-0.5" />
    </a>
  @endif
  <div class="border-b border-line bg-white text-ink">
    <div class="container-site flex h-[72px] items-center justify-between gap-6">
      <a href="/" class="shrink-0" aria-label="SteynPT home">
        <x-logo class="h-11 w-auto" :priority="true" />
      </a>

      <nav aria-label="Hoofdmenu" class="hidden xl:block">
        <ul class="flex items-center gap-1">
          @foreach ($nav as $item)
            @continue(($item['desktop'] ?? true) === false)
            @php $active = $path === $item['href']; @endphp
            <li>
              <a href="{{ $item['href'] }}" @if ($active) aria-current="page" @endif
                class="relative whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium transition-colors {{ $active ? 'text-ink' : 'text-ink/70 hover:text-ink' }}">
                {{ $item['label'] }}
                @if ($item['highlight'] ?? false)
                  <span class="absolute right-1 top-1.5 size-1.5 rounded-full bg-ink"></span>
                @endif
                @if ($active)
                  <span class="absolute inset-x-3 -bottom-0.5 h-0.5 rounded bg-ink"></span>
                @endif
              </a>
            </li>
          @endforeach
        </ul>
      </nav>

      <div class="flex items-center gap-2">
        <a href="/account" class="hidden items-center gap-2 whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium text-ink/80 transition-colors hover:text-ink sm:inline-flex" aria-label="Mijn omgeving">
          <x-icon name="User" class="size-4" />
          <span class="xl:hidden 2xl:inline">Mijn omgeving</span>
        </a>
        <a href="/online-coaching" class="btn btn-primary btn-sm hidden sm:inline-flex">Start online coaching</a>
        <button type="button" @click="open = true" class="grid size-11 place-items-center rounded-full border border-line bg-white xl:hidden"
          :aria-expanded="open.toString()" aria-expanded="false" aria-controls="mobile-menu" aria-label="Menu openen">
          <x-icon name="Menu" class="size-5" />
        </button>
      </div>
    </div>
  </div>

  {{-- Pas in de pagina zodra het menu opengaat (zoals in de React-versie). --}}
  <template x-if="open">
  <div id="mobile-menu" role="dialog" aria-modal="true" aria-label="Menu"
    class="fixed inset-0 z-[60] overflow-y-auto bg-paper text-ink xl:hidden" @click="if ($event.target.closest('a')) open = false">
    <div class="container-site flex h-[72px] items-center justify-between border-b border-ink/10">
      <a href="/" aria-label="SteynPT home">
        <x-logo class="h-11 w-auto" />
      </a>
      <button type="button" @click="open = false" class="grid size-11 place-items-center rounded-full border border-ink/15" aria-label="Menu sluiten">
        <x-icon name="X" class="size-5" />
      </button>
    </div>
    <nav aria-label="Mobiel menu" class="container-site pb-10 pt-4">
      <ul class="divide-y divide-ink/10">
        @foreach ([...$nav, ['href' => '/contact', 'label' => 'Contact']] as $item)
          <li>
            <a href="{{ $item['href'] }}" class="flex items-center justify-between py-4">
              <span class="display text-2xl">{{ $item['label'] }}</span>
              @if ($item['highlight'] ?? false)
                <span class="rounded bg-accent px-1.5 py-0.5 text-xs font-semibold text-white">Nieuw</span>
              @else
                <x-icon name="ArrowRight" class="size-5 text-ink/40" />
              @endif
            </a>
          </li>
        @endforeach
      </ul>
      <div class="mt-8 grid gap-3">
        <a href="/online-coaching" class="btn btn-primary">Start online coaching</a>
        <a href="/account" class="btn btn-outline">
          <x-icon name="User" class="size-4" /> Mijn omgeving
        </a>
      </div>
    </nav>
  </div>
  </template>
</header>
