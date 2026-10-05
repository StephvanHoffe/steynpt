@props(['title' => null, 'titleTemplate' => '%s · Beheer SteynPT'])
{{-- Eigen opmaak voor het beheer: bovenbalk en zijbalk in plaats van de websitekop. --}}
<x-layouts.base :title="$title ?? 'Beheer'" :title-template="$title ? $titleTemplate : '%s · SteynPT'" :noindex="true">
  <div class="flex flex-1 flex-col bg-surface">
    <header class="sticky top-0 z-40 flex h-14 items-center justify-between gap-4 border-b border-line bg-white px-4 sm:px-6 print:hidden">
      <a href="/admin" class="flex items-center gap-2.5">
        <x-logo-mark class="h-7 w-auto" />
        <span class="font-display text-[15px] font-semibold tracking-wide">SteynPT <span class="font-normal text-muted">Beheer</span></span>
      </a>
      <div class="flex items-center gap-1 text-sm">
        <a href="/" class="hidden items-center gap-1.5 rounded-md px-3 py-2 font-medium text-muted hover:bg-surface hover:text-ink sm:inline-flex">
          Naar website <x-icon name="ExternalLink" class="size-3.5" />
        </a>
        <form action="/uitloggen" method="post">
          @csrf
          <button type="submit" class="inline-flex items-center gap-1.5 rounded-md px-3 py-2 font-medium text-muted hover:bg-surface hover:text-ink">
            <x-icon name="LogOut" class="size-4" /> <span class="hidden sm:inline">Uitloggen</span>
          </button>
        </form>
      </div>
    </header>
    <x-password-reminder :user="auth()->user()" next="/admin" />
    <div class="flex flex-1 flex-col lg:flex-row">
      <x-admin.nav :counts="\App\Services\AdminCounts::get()" />
      <main id="inhoud" class="min-w-0 flex-1">
        {{ $slot }}
      </main>
    </div>
  </div>
</x-layouts.base>
