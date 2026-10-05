@props(['title', 'description' => null, 'back' => null])
{{-- Kop van een beheerpagina. description en actions kunnen ook als <x-slot:…> worden meegegeven; back = ['href' => …, 'label' => …]. --}}
<header class="mb-6 flex flex-wrap items-end justify-between gap-4">
  <div class="min-w-0">
    @if ($back)
      <a href="{{ $back['href'] }}" class="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-ink">
        <x-icon name="ArrowLeft" class="size-4" /> {{ $back['label'] }}
      </a>
    @endif
    <h1 class="display text-3xl sm:text-4xl">{{ $title }}</h1>
    @if ($description !== null && (string) $description !== '')
      <div class="mt-1.5 text-sm text-muted">{{ $description }}</div>
    @endif
  </div>
  @if (isset($actions) && $actions->isNotEmpty())
    <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
  @endif
</header>
